<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../../php/config.php';

// Bloco de validação de token (não muda)
$token = null;
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
if ($auth_header === null) {
    $all_headers = getallheaders();
    $auth_header = $all_headers['Authorization'] ?? null;
}
if ($auth_header && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $token = $matches[1];
}
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado: token não fornecido.']);
    exit;
}
$stmt_user = $conn->prepare("SELECT id FROM usuarios WHERE TRIM(auth_token) = TRIM(?) AND token_expires_at > NOW()");
$stmt_user->bind_param("s", $token);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado: token inválido ou expirado.']);
    exit;
}
$user = $result_user->fetch_assoc();
$id_usuario_logado = $user['id'];
$stmt_user->close();

try {
    // Consulta simplificada com LIMIT 10, sem paginação
    $query = "
        SELECT DISTINCT r.id_lancamento, r.criado_em, q.nome_questionario
        FROM respostas_questionario AS r
        JOIN questionarios AS q ON r.id_questionario = q.id_questionario
        WHERE r.id_usuario = ?
        ORDER BY r.criado_em DESC
        LIMIT 10
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_usuario_logado);
    $stmt->execute();
    $result = $stmt->get_result();
    $lancamentos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Resposta simples, sem 'total_items'
    echo json_encode(['success' => true, 'lancamentos' => $lancamentos]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}

$conn->close();
?>