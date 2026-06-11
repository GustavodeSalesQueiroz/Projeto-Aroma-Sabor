<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

if ($conn === null) {
    json_response(['success' => false, 'error' => 'Erro de conexão com o banco de dados'], 500);
}

/** @var mysqli $conn */
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

function fetchProductsResult($result) {
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    return $products;
}

if ($method === 'GET') {
    if ($action === 'list') {
        // CORREÇÃO: SUM e GROUP BY adicionados para evitar duplicar produtos com múltiplos estoques
        $sql = "SELECT p.id, p.code, p.name, p.description, p.price, p.slug, p.featured, p.status, p.category_id, 
                       COALESCE(p.image_url, p.image_path, '') AS image, 
                       COALESCE(SUM(inv.quantity), 0) AS stock
                FROM products p
                LEFT JOIN inventory inv ON p.id = inv.product_id
                WHERE p.status = 1
                GROUP BY p.id
                ORDER BY p.id DESC";
        $result = $conn->query($sql);

        if (!$result) {
            json_response(['success' => false, 'error' => 'Erro ao buscar produtos'], 500);
        }

        json_response(['success' => true, 'data' => fetchProductsResult($result)]);
    }
    elseif ($action === 'featured') {
        // CORREÇÃO: SUM e GROUP BY adicionados aqui também
        $sql = "SELECT p.id, p.code, p.name, p.description, p.price, p.slug, p.featured, p.status, p.category_id, 
                       COALESCE(p.image_url, p.image_path, '') AS image, 
                       COALESCE(SUM(inv.quantity), 0) AS stock
                FROM products p
                LEFT JOIN inventory inv ON p.id = inv.product_id
                WHERE p.featured = 1 AND p.status = 1
                GROUP BY p.id
                LIMIT 6";
        $result = $conn->query($sql);

        if (!$result) {
            json_response(['success' => false, 'error' => 'Erro ao buscar produtos em destaque'], 500);
        }

        json_response(['success' => true, 'data' => fetchProductsResult($result)]);
    }
    elseif ($action === 'detail') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

        // CORREÇÃO: Agrupamento por ID e SUM na busca por detalhes
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT p.id, p.code, p.name, p.description, p.price, p.slug, p.featured, p.status, p.category_id, 
                                           COALESCE(p.image_url, p.image_path, '') AS image, 
                                           COALESCE(SUM(inv.quantity), 0) AS stock
                                    FROM products p
                                    LEFT JOIN inventory inv ON p.id = inv.product_id
                                    WHERE p.id = ? AND p.status = 1
                                    GROUP BY p.id");
            $stmt->bind_param("i", $id);
        } elseif ($slug) {
            $stmt = $conn->prepare("SELECT p.id, p.code, p.name, p.description, p.price, p.slug, p.featured, p.status, p.category_id, 
                                           COALESCE(p.image_url, p.image_path, '') AS image, 
                                           COALESCE(SUM(inv.quantity), 0) AS stock
                                    FROM products p
                                    LEFT JOIN inventory inv ON p.id = inv.product_id
                                    WHERE p.slug = ? AND p.status = 1
                                    GROUP BY p.id");
            $stmt->bind_param("s", $slug);
        } else {
            json_response(['success' => false, 'error' => 'ID ou slug é necessário'], 400);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product) {
            json_response(['success' => true, 'data' => $product]);
        } else {
            json_response(['success' => false, 'error' => 'Produto não encontrado'], 404);
        }
    }
    elseif ($action === 'by_category') {
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

        if ($category_id <= 0) {
            json_response(['success' => false, 'error' => 'category_id é necessário'], 400);
        }

        // CORREÇÃO: Agrupamento por ID e SUM na busca por categoria
        $stmt = $conn->prepare("SELECT p.id, p.code, p.name, p.description, p.price, p.slug, p.featured, p.status, p.category_id, 
                                       COALESCE(p.image_url, p.image_path, '') AS image, 
                                       COALESCE(SUM(inv.quantity), 0) AS stock
                                FROM products p
                                LEFT JOIN inventory inv ON p.id = inv.product_id
                                WHERE p.category_id = ? AND p.status = 1
                                GROUP BY p.id
                                ORDER BY p.id DESC");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = fetchProductsResult($result);
        $stmt->close();

        json_response(['success' => true, 'data' => $products]);
    }
    else {
        json_response(['success' => false, 'error' => 'Ação não reconhecida'], 400);
    }
} else {
    json_response(['success' => false, 'error' => 'Método não permitido'], 405);
}

$conn->close();
?>