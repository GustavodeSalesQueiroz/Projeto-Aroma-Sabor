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
        // Listar todas as categorias
        $result = $conn->query("SELECT * FROM categories ORDER BY created_at DESC");
        $categories = [];
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        json_response(['success' => true, 'data' => $categories]);
    }
    elseif ($action === 'detail') {
        // Obter detalhes de uma categoria
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
        
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
        } elseif ($slug) {
            $stmt = $conn->prepare("SELECT * FROM categories WHERE slug = ?");
            $stmt->bind_param("s", $slug);
        } else {
            json_response(['success' => false, 'error' => 'ID ou slug é necessário'], 400);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        
        if ($category) {
            json_response(['success' => true, 'data' => $category]);
        } else {
            json_response(['success' => false, 'error' => 'Categoria não encontrada'], 404);
        }
        
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
