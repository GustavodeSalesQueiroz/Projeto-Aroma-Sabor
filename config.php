<?php
/* Configuração do Banco de Dados MySQL */

$host = "localhost";
$usuario = "login_user";
$senha = "senha_segura_123"; // senha em branco
$banco = "aromaesabor_db";
$porta = 3306;

$conn = null; // Inicializa a conexão como nula

// Criar conexão
try {
    // Tenta estabelecer a conexão
    $temp_conn = new mysqli($host, $usuario, $senha, $banco, $porta);
    
    // Verificar se houve erro de conexão
    if ($temp_conn->connect_error) {
        // Se houver erro, $conn permanece null e o erro é logado (opcional)
        // error_log('Erro ao conectar ao MySQL: ' . $temp_conn->connect_error);
    } else {
        // Conexão bem-sucedida
        $conn = $temp_conn;
        
        // Criar banco de dados se não existir
        $sql = "CREATE DATABASE IF NOT EXISTS " . $banco;
        if (!$conn->query($sql)) {
            // Se houver erro ao criar o DB, a conexão é invalidada
            // error_log('Erro ao criar banco de dados: ' . $conn->error);
            $conn = null;   
        } else {
            // Selecionar banco de dados e definir charset
            $conn->select_db($banco);
            $conn->set_charset("utf8mb4");
        }
    }
    
} catch (Exception $e) {
    // Em caso de exceção, a conexão é invalidada
    // error_log('Erro de conexão: ' . $e->getMessage());
    $conn = null;
}


if (!function_exists('json_response')) {
    function json_response($data, $status = 200) {
        http_response_code($status );
        echo json_encode($data);
        exit; // Garante que o script pare após enviar a resposta
    }
}

// A variável $conn agora é o objeto mysqli ou null em caso de falha.
// O script principal (auth.php) deve verificar se $conn é null.

// Função para sanitizar entrada (mantida para ser usada no auth.php)
if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// FIM DO ARQUIVO - SEM ?>
