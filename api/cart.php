<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
        $stmt = $conn->prepare("SELECT ci.*, p.name, p.price, p.image FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.user_id = ? ORDER BY ci.created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        json_response(['success' => true, 'data' => $items]);
        $stmt->close();
    }
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'add') {
        $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
        
        if ($product_id <= 0 || $quantity <= 0) {
            json_response(['success' => false, 'error' => 'Dados inválidos'], 400);
        }
        
        // Verificar se o produto já está no carrinho
        $check_stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing = $check_result->fetch_assoc();
        
        $check_stmt->close();
        
        if ($existing) {
            // Atualizar quantidade
            $new_quantity = $existing['quantity'] + $quantity;
            $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_quantity, $existing['id']);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Inserir novo item
            $insert_stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        
        json_response(['success' => true, 'message' => 'Produto adicionado ao carrinho']);
    }
    elseif ($action === 'update') {
        $cart_item_id = isset($data['cart_item_id']) ? (int)$data['cart_item_id'] : 0;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
        
        if ($cart_item_id <= 0) {
            json_response(['success' => false, 'error' => 'ID do item do carrinho é necessário'], 400);
        }
        
        if ($quantity <= 0) {
            // Deletar item
            $delete_stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $cart_item_id, $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        } else {
            // Atualizar quantidade
            $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
            $update_stmt->bind_param("iii", $quantity, $cart_item_id, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        json_response(['success' => true, 'message' => 'Carrinho atualizado']);
    }
}
elseif ($method === 'DELETE') {
    if ($action === 'remove') {
        $cart_item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($cart_item_id <= 0) {
            json_response(['success' => false, 'error' => 'ID do item é necessário'], 400);
        }
        
        $delete_stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $cart_item_id, $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        json_response(['success' => true, 'message' => 'Item removido do carrinho']);
    }
    elseif ($action === 'clear') {
        $clear_stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        json_response(['success' => true, 'message' => 'Carrinho limpo']);
    }
}
else {
    json_response(['success' => false, 'error' => 'Método não permitido'], 405);
}

$conn->close();
?>
