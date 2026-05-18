<?php
require_once '../../php/config.php';

// Retorna sempre JSON
header('Content-Type: application/json');

// Validação básica
if (
    !isset($_GET['id_questionario']) ||
    !isset($_GET['data_inicio']) ||
    !isset($_GET['data_fim'])
) {
    echo json_encode([
        'success' => false,
        'error' => 'Parâmetros insuficientes.'
    ]);
    exit();
}

$idQuestionario = intval($_GET['id_questionario']);
$dataInicio = $_GET['data_inicio'];
$dataFim = $_GET['data_fim'];

// Validação simples das datas
if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)
) {
    echo json_encode([
        'success' => false,
        'error' => 'Formato de data inválido.'
    ]);
    exit();
}

try {

    $queryCount = "
        SELECT COUNT(DISTINCT id_lancamento) as total
        FROM respostas_questionario
        WHERE id_questionario = ?
        AND DATE(criado_em) BETWEEN ? AND ?
    ";

    $stmtCount = $conn->prepare($queryCount);

    if (!$stmtCount) {
        throw new Exception('Erro ao preparar query.');
    }

    $stmtCount->bind_param(
        'iss',
        $idQuestionario,
        $dataInicio,
        $dataFim
    );

    $stmtCount->execute();

    $stmtCount->bind_result($totalLancamentos);
    $stmtCount->fetch();

    $stmtCount->close();

    echo json_encode([
        'success' => true,
        'total' => (int)$totalLancamentos
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);

}

exit();