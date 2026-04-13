<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../../php/config.php';

//1. VALIDAÇÃO DO TOKEN DE AUTENTICAÇÃO
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
    //2. BUSCAR OS QUESTIONÁRIOS PERMITIDOS PARA O USUÁRIO
    $query_q = "
        SELECT q.id_questionario, q.nome_questionario, q.descricao_questionario
        FROM questionarios q
        JOIN questionario_permissoes p ON q.id_questionario = p.id_questionario
        WHERE p.id_usuario = ?
    ";
    $stmt_q = $conn->prepare($query_q);
    $stmt_q->bind_param('i', $id_usuario_logado);
    $stmt_q->execute();
    $result_q = $stmt_q->get_result();
    $questionarios = $result_q->fetch_all(MYSQLI_ASSOC);
    $stmt_q->close();

    //3. BUSCAR TODOS OS CAMPOS DESSES QUESTIONÁRIOS
    $campos = [];
    // Se encontramos algum questionário, buscamos os campos deles
    if (!empty($questionarios)) {
        // Pega apenas os IDs dos questionários encontrados
        $questionario_ids = array_column($questionarios, 'id_questionario');
        
        // Cria os '?' para a cláusula IN da consulta SQL
        $placeholders = implode(',', array_fill(0, count($questionario_ids), '?'));
        // Cria a string de tipos para o bind_param (ex: 'ii' se forem 2 IDs)
        $types = str_repeat('i', count($questionario_ids));

        $query_c = "
            SELECT id_campo, id_questionario, nome_campo, tipo_campo, opcoes
            FROM campos_questionario
            WHERE id_questionario IN ($placeholders)
        ";
        $stmt_c = $conn->prepare($query_c);
        // O '...' desempacota o array de IDs para o bind_param
        $stmt_c->bind_param($types, ...$questionario_ids);
        $stmt_c->execute();
        $result_c = $stmt_c->get_result();
        $campos = $result_c->fetch_all(MYSQLI_ASSOC);
        $stmt_c->close();
    }
    
    //4. RESPOSTA FINAL
    // Envia um único JSON contendo as duas listas: questionários e campos.
    echo json_encode([
        'success' => true, 
        'questionarios' => $questionarios,
        'campos' => $campos
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}

$conn->close();
?>