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

if ($method === 'GET') {
    if ($action === 'list') {
        // Listar todos os produtos
        $result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
        $products = [];
        
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        json_response(['success' => true, 'data' => $products]);
    } 
    elseif ($action === 'featured') {
        // Listar produtos em destaque
        $result = $conn->query("SELECT * FROM products WHERE featured = 1 LIMIT 6");
        
        if (!$result) {
            json_response(['success' => false, 'error' => 'Erro ao buscar produtos em destaque'], 500);
        }
        
        $products = [];
        
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        json_response(['success' => true, 'data' => $products]);
    }
    elseif ($action === 'detail') {
        // Obter detalhes de um produto
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
        
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
        } elseif ($slug) {
            $stmt = $conn->prepare("SELECT * FROM products WHERE slug = ?");
            $stmt->bind_param("s", $slug);
        } else {
            json_response(['success' => false, 'error' => 'ID ou slug é necessário'], 400);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if ($product) {
            json_response(['success' => true, 'data' => $product]);
        } else {
            json_response(['success' => false, 'error' => 'Produto não encontrado'], 404);
        }
        
        $stmt->close();
    }
    elseif ($action === 'by_category') {
        // Obter produtos por categoria
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        
        if ($category_id <= 0) {
            json_response(['success' => false, 'error' => 'category_id é necessário'], 400);
        }
        
        $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        json_response(['success' => true, 'data' => $products]);
        $stmt->close();
    }
    else {
        json_response(['success' => false, 'error' => 'Ação não reconhecida'], 400);
    }
} else {
    json_response(['success' => false, 'error' => 'Método não permitido'], 405);
}

$conn->close();
?>
