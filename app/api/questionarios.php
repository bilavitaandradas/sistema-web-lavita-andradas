<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../../php/config.php';

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

$query = "
    SELECT q.id_questionario, q.nome_questionario, q.descricao_questionario
    FROM questionarios q
    JOIN questionario_permissoes p ON q.id_questionario = p.id_questionario
    WHERE p.id_usuario = ?
    ORDER BY q.nome_questionario
";

$stmt_q = $conn->prepare($query);
$stmt_q->bind_param('i', $id_usuario_logado);
$stmt_q->execute();
$result_q = $stmt_q->get_result();
$questionarios = $result_q->fetch_all(MYSQLI_ASSOC);
$stmt_q->close();

echo json_encode(['success' => true, 'questionarios' => $questionarios]);
?>