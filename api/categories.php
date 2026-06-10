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
        $result = $conn->query("SELECT id, name FROM categories ORDER BY id DESC");
        if (!$result) {
            json_response(['success' => false, 'error' => 'Erro ao buscar categorias'], 500);
        }

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        json_response(['success' => true, 'data' => $categories]);
    }
    elseif ($action === 'detail') {
        // Obter detalhes de uma categoria pelo ID
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            json_response(['success' => false, 'error' => 'ID é necessário'], 400);
        }

        $stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        $stmt->close();

        if ($category) {
            json_response(['success' => true, 'data' => $category]);
        } else {
            json_response(['success' => false, 'error' => 'Categoria não encontrada'], 404);
        }
    }
    else {
        json_response(['success' => false, 'error' => 'Ação não reconhecida'], 400);
    }
} else {
    json_response(['success' => false, 'error' => 'Método não permitido'], 405);
}

$conn->close();
?>
