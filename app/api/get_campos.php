<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../../php/config.php';

// --- Validação do Token (AGORA COM O MÉTODO CORRETO E ROBUSTO) ---
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
// --------------------------------------------------------------------

// Pega o ID do questionário que o app está pedindo
$id_questionario = $_GET['id_questionario'] ?? 0;
if (!$id_questionario) {
    echo json_encode(['success' => false, 'message' => 'ID do questionário não fornecido.']);
    exit;
}

// --- Verificação de Permissão ---
// Checa se o usuário logado tem permissão para ESTE questionário específico.
$stmt_perm = $conn->prepare("SELECT COUNT(*) FROM questionario_permissoes WHERE id_questionario = ? AND id_usuario = ?");
$stmt_perm->bind_param("ii", $id_questionario, $id_usuario_logado);
$stmt_perm->execute();
$stmt_perm->bind_result($count);
$stmt_perm->fetch();
$stmt_perm->close();

if ($count == 0) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado a este questionário.']);
    exit;
}

// Se a permissão for válida, busca os campos do questionário
$query = "SELECT * FROM campos_questionario WHERE id_questionario = ? ORDER BY id_campo ASC";
$stmt_campos = $conn->prepare($query);
$stmt_campos->bind_param('i', $id_questionario);
$stmt_campos->execute();
$result_campos = $stmt_campos->get_result();
$campos = $result_campos->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'campos' => $campos]);
?>