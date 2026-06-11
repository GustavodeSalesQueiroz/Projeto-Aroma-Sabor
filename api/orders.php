<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

// Verificar conexão
if ($conn === null) {
    json_response(['success' => false, 'error' => 'Erro ao conectar ao banco de dados'], 500);
}

/** @var mysqli $conn */

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method === 'GET') {
    if ($action === 'list') {
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        
        if ($user_id <= 0) {
            json_response(['success' => false, 'error' => 'user_id é necessário'], 400);
        }
        
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        $stmt->close();
        json_response(['success' => true, 'orders' => $orders]);
    }
    elseif ($action === 'detail') {
        $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        
        if ($order_id <= 0 || $user_id <= 0) {
            json_response(['success' => false, 'error' => 'ID do pedido e user_id são necessários'], 400);
        }
        
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if ($order) {
            $order['items'] = json_decode($order['items'], true) ?: [];
            json_response(['success' => true, 'data' => $order]);
        } else {
            json_response(['success' => false, 'error' => 'Pedido não encontrado'], 404);
        }
    }
    else {
        json_response(['success' => false, 'error' => 'Ação não reconhecida'], 400);
    }
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'create') {
        $user_id          = isset($data['user_id'])          ? (int)$data['user_id']              : 0;
        $total_price      = isset($data['total_price'])      ? (float)$data['total_price']        : 0;
        $shipping_address = isset($data['shipping_address']) ? sanitize($data['shipping_address']) : '';
        $billing_address  = isset($data['billing_address'])  ? sanitize($data['billing_address'])  : '';
        $items_raw        = isset($data['items'])          ? $data['items']                     : [];

        // Validações
        if ($user_id <= 0) {
            json_response(['success' => false, 'error' => 'user_id inválido'], 400);
        }

        // Verificar se o usuário existe no banco
        $user_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_exists = $user_result->num_rows > 0;
        $user_stmt->close();

        if (!$user_exists) {
            json_response(['success' => false, 'error' => 'Usuário inválido. Faça login novamente.'], 401);
        }

        if ($total_price <= 0) {
            json_response(['success' => false, 'error' => 'total_price deve ser maior que 0'], 400);
        }
        if (empty($items_raw) || !is_array($items_raw)) {
            json_response(['success' => false, 'error' => 'O carrinho está vazio'], 400);
        }

        $items_json = json_encode($items_raw);

        // -------------------------------------------------------
        // Verificar estoque disponível para todos os itens
        // antes de iniciar a transação
        // -------------------------------------------------------
        foreach ($items_raw as $item) {
            $product_id  = isset($item['id'])       ? (int)$item['id']       : 0;
            $qty_needed  = isset($item['quantity'])  ? (int)$item['quantity'] : 0;
            $item_name   = isset($item['name'])      ? $item['name']          : "Produto #{$product_id}";

            if ($product_id <= 0 || $qty_needed <= 0) {
                json_response(['success' => false, 'error' => 'Item do carrinho inválido'], 400);
            }

            $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            if (!$product) {
                json_response(['success' => false, 'error' => "Produto '{$item_name}' não encontrado"], 404);
            }

            if ($product['quantity'] < $qty_needed) {
                json_response([
                    'success' => false,
                    'error'   => "Estoque insuficiente para '{$item_name}'. Disponível: {$product['quantity']}, solicitado: {$qty_needed}"
                ], 400);
            }
        }

        // -------------------------------------------------------
        // Iniciar transação: inserir pedido + itens + decrementar estoque
        // -------------------------------------------------------
        $conn->begin_transaction();

        try {
            // 1. Inserir o pedido (Incluindo o shipping_address)
            $status = 'pending';
            $stmt = $conn->prepare(
                "INSERT INTO orders (user_id, total_price, status, items, shipping_address)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("idsss", $user_id, $total_price, $status, $items_json, $shipping_address);

            if (!$stmt->execute()) {
                throw new Exception('Erro ao criar pedido: ' . $stmt->error);
            }

            $order_id = $conn->insert_id;
            $stmt->close();

            // 2. Gravar os itens individualmente na tabela relacional cart_items
            foreach ($items_raw as $item) {
                $product_id = (int)$item['id'];
                $qty        = (int)$item['quantity'];
                $price      = isset($item['price']) ? (float)$item['price'] : 0.0;

                $stmt_items = $conn->prepare(
                    "INSERT INTO cart_items (order_id, product_id, quantity, price) 
                     VALUES (?, ?, ?, ?)"
                );
                $stmt_items->bind_param("iiid", $order_id, $product_id, $qty, $price);

                if (!$stmt_items->execute()) {
                    throw new Exception('Erro ao vincular item ao pedido: ' . $stmt_items->error);
                }
                $stmt_items->close();
            }

            // 3. Decrementar estoque de cada produto
            foreach ($items_raw as $item) {
                $product_id = (int)$item['id'];
                $qty        = (int)$item['quantity'];

                $stmt = $conn->prepare(
                    "UPDATE inventory
                     SET quantity = quantity - ?
                     WHERE product_id = ? AND quantity >= ?"
                );
                $stmt->bind_param("iii", $qty, $product_id, $qty);

                if (!$stmt->execute()) {
                    throw new Exception('Erro ao atualizar estoque do produto #' . $product_id . ': ' . $stmt->error);
                }

                if ($stmt->affected_rows === 0) {
                    throw new Exception('Estoque insuficiente para o produto #' . $product_id . ' durante a atualização');
                }

                $stmt->close();
            }

            // 4. Confirmar transação no banco
            $conn->commit();

            json_response([
                'success'  => true,
                'message'  => 'Pedido criado com sucesso',
                'order_id' => $order_id
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            json_response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    else {
        json_response(['success' => false, 'error' => 'Ação não reconhecida'], 400);
    }
}
else {
    json_response(['success' => false, 'error' => 'Método não permitido'], 405);
}
?>
