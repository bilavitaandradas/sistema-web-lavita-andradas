<?php
header('Content-Type: application/json');
include '../../php/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Username e password são obrigatórios.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, username, password, nome, role, nome_sitio FROM usuarios WHERE username = ? AND status = 'ATIVO' LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado ou inativo.']);
        exit;
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
        exit;
    }

    $token = bin2hex(random_bytes(32)); 
    $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt_update_token = $conn->prepare("UPDATE usuarios SET auth_token = ?, token_expires_at = ? WHERE id = ?");
    $stmt_update_token->bind_param("ssi", $token, $expiry_date, $user['id']);
    $stmt_update_token->execute();

    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'nome' => $user['nome'],
            'role' => $user['role'],
            'nome_sitio' => $user['nome_sitio']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>