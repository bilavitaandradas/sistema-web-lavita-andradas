<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../../php/config.php';

// (A validação do token continua a mesma)
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

$json_data = file_get_contents('php://input');
$lancamentos = json_decode($json_data, true);

if (empty($lancamentos) || !is_array($lancamentos)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado de lançamento recebido ou formato inválido.']);
    exit;
}

$conn->begin_transaction();
try {
    $stmt_insert = $conn->prepare(
        "INSERT INTO respostas_questionario 
        (id_questionario, id_campo, valor_resposta, id_usuario, id_lancamento, criado_em) 
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($lancamentos as $lancamento) {
        $id_lancamento = $lancamento['id_lancamento'];
        $id_questionario = $lancamento['id_questionario'];
        
        // --- PONTO DO AJUSTE ---
        $dateTimeUtc = new DateTime($lancamento['criado_em_local']);
        // Agora ele pega o fuso horário padrão definido no config.php
        $dateTimeLocal = $dateTimeUtc->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $criado_em = $dateTimeLocal->format('Y-m-d H:i:s');
        // -------------------------
        
        $respostas = json_decode($lancamento['respostas'], true);

        foreach ($respostas as $id_campo => $valor_resposta) {
            $stmt_insert->bind_param(
                "iisiss",
                $id_questionario,
                $id_campo,
                $valor_resposta,
                $id_usuario_logado,
                $id_lancamento,
                $criado_em
            );
            $stmt_insert->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Dados sincronizados com sucesso!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro durante a sincronização: ' . $e->getMessage()]);
}

if(isset($stmt_insert)) $stmt_insert->close();
$conn->close();
?>