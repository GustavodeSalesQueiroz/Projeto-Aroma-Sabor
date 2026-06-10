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
// Função para retornar JSON
function json_response($data, $status = 200) {
    http_response_code($status );
    echo json_encode($data);
    exit; // Garante que o script pare após enviar a resposta
}


// Verificar conexão (agora $conn será null se a conexão falhar no config.php)
if ($conn === null) {
    json_response(['success' => false, 'error' => 'Erro interno do servidor: Falha ao conectar ao banco de dados.'], 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

if ($method === 'OPTIONS') {
    http_response_code(200 );
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'register') {
        // Lógica de Cadastro (mantida como estava, mas com a garantia de que $conn não é null)
        $name = isset($data['name']) ? sanitize($data['name']) : '';
        $email = isset($data['email']) ? sanitize($data['email']) : '';
        $password = isset($data['password']) ? $data['password'] : '';
        $confirmPassword = isset($data['confirmPassword']) ? $data['confirmPassword'] : '';
        
        // Validações
        if (!$name || !$email || !$password) {
            json_response(['success' => false, 'error' => 'Todos os campos são obrigatórios'], 400);
        }
        
        if ($password !== $confirmPassword) {
            json_response(['success' => false, 'error' => 'As senhas não coincidem'], 400);
        }
        
        if (strlen($password) < 6) {
            json_response(['success' => false, 'error' => 'A senha deve ter no mínimo 6 caracteres'], 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'error' => 'Email inválido'], 400);
        }
        
        // Verificar se email já existe
        // Simplificado para usar apenas mysqli, já que o config.php usa mysqli
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            json_response(['success' => false, 'error' => 'Este email já está cadastrado'], 400);
        }
        $check_stmt->close();
        
        // Criptografar senha
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Inserir usuário
        $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $name, $email, $hashedPassword);
        $success = $insert_stmt->execute();
        $user_id = $conn->insert_id;
        $insert_stmt->close();
        
        if ($success) {
            json_response([
                'success' => true,
                'message' => 'Cadastro realizado com sucesso!',
                'user' => [
                    'id' => $user_id,
                    'name' => $name,
                    'email' => $email,
                    'type_user' => 'user'
                ]
            ]);
        } else {
            json_response(['success' => false, 'error' => 'Erro ao criar conta'], 500);
        }
    }
    elseif ($action === 'login') {
        // Lógica de Login (mantida como estava, mas com a garantia de que $conn não é null)
        $email = isset($data['email']) ? sanitize($data['email']) : '';
        $password = isset($data['password']) ? $data['password'] : '';
        
        if (!$email || !$password) {
            json_response(['success' => false, 'error' => 'Email e senha são obrigatórios'], 400);
        }
        
        // Buscar usuário
        // Simplificado para usar apenas mysqli
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            json_response(['success' => false, 'error' => 'Email ou senha incorretos'], 401);
        }
        
        // Verificar senha
        $isPasswordValid = password_verify($password, $user['password']);

        // Compatibilidade com senhas legadas armazenadas em texto simples
        if (!$isPasswordValid && $user['password'] === $password) {
            $isPasswordValid = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newHash, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }

        if (!$isPasswordValid) {
            json_response(['success' => false, 'error' => 'Email ou senha incorretos'], 401);
        }
        
        json_response([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'type_user' => 'user'
            ]
        ]);
    }
}
elseif ($method === 'GET') {
    if ($action === 'logout') {
        // Logout
        json_response(['success' => true, 'message' => 'Logout realizado com sucesso']);
    }
}
else {
    json_response(['success' => false, 'error' => 'Método não permitido'], 405);
}

// FIM DO ARQUIVO - SEM ?>
