<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../../php/config.php';

// =============================
// VALIDAÇÃO DO TOKEN
// =============================
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

// =============================
// RECEBE JSON
// =============================
$json_data = file_get_contents('php://input');
$lancamentos = json_decode($json_data, true);

if (empty($lancamentos) || !is_array($lancamentos)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado de lançamento recebido ou formato inválido.']);
    exit;
}

// =============================
// FUNÇÃO DE NORMALIZAÇÃO
// =============================
function normalizarNumero($valor) {
    $valor = trim($valor);
    $valor = str_replace(',', '.', $valor);

    if (!is_numeric($valor)) {
        return null;
    }

    return $valor;
}

// =============================
// PROCESSAMENTO
// =============================
$conn->begin_transaction();

try {

    $stmt_insert = $conn->prepare(
        "INSERT INTO respostas_questionario 
        (id_questionario, id_campo, valor_resposta, id_usuario, id_lancamento, criado_em) 
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($lancamentos as $lancamento) {

        $id_lancamento   = $lancamento['id_lancamento'];
        $id_questionario = $lancamento['id_questionario'];

        // =============================
        // AJUSTE DE DATA
        // =============================
        $dateTimeUtc = new DateTime($lancamento['criado_em_local']);
        $dateTimeLocal = $dateTimeUtc->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $criado_em = $dateTimeLocal->format('Y-m-d H:i:s');

        // =============================
        // BUSCA TIPOS DOS CAMPOS
        // =============================
        $tipos_campos = [];

        $stmt_tipos = $conn->prepare("SELECT id_campo, tipo_campo FROM campos_questionario WHERE id_questionario = ?");
        $stmt_tipos->bind_param('i', $id_questionario);
        $stmt_tipos->execute();
        $result_tipos = $stmt_tipos->get_result();

        while ($row_tipo = $result_tipos->fetch_assoc()) {
            $tipos_campos[$row_tipo['id_campo']] = $row_tipo['tipo_campo'];
        }

        $stmt_tipos->close();

        // =============================
        // RESPOSTAS
        // =============================
        $respostas = json_decode($lancamento['respostas'], true);

        foreach ($respostas as $id_campo => $valor_resposta) {

            $valor_resposta = trim($valor_resposta);

            // 🔥 TRATAMENTO PARA NÚMEROS
            if (isset($tipos_campos[$id_campo]) && $tipos_campos[$id_campo] === 'NUMBER') {

                $valor_resposta = normalizarNumero($valor_resposta);

                if ($valor_resposta === null) {
                    throw new Exception("Valor inválido para campo numérico (campo ID: $id_campo)");
                }
            }

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

    echo json_encode([
        'success' => true,
        'message' => 'Dados sincronizados com sucesso!'
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Erro durante a sincronização: ' . $e->getMessage()
    ]);
}

// =============================
if (isset($stmt_insert)) $stmt_insert->close();
$conn->close();
?>