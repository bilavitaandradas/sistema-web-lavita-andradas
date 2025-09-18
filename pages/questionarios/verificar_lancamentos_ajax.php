<?php
// Não precisa de sessão para esta verificação rápida
require_once '../../php/config.php';

// Valida os parâmetros de entrada
if (!isset($_GET['id_questionario']) || !isset($_GET['data_inicio']) || !isset($_GET['data_fim'])) {
    // Retorna um erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Parâmetros insuficientes.']);
    exit();
}

$idQuestionario = intval($_GET['id_questionario']);
$dataInicio = $_GET['data_inicio'];
$dataFim = $_GET['data_fim'];

// A mesma query de contagem que tínhamos antes
$queryCount = "SELECT COUNT(DISTINCT id_lancamento) as total FROM respostas_questionario WHERE id_questionario = ? AND DATE(criado_em) BETWEEN ? AND ?";
$stmtCount = $conn->prepare($queryCount);
$stmtCount->bind_param('iss', $idQuestionario, $dataInicio, $dataFim);
$stmtCount->execute();
$stmtCount->bind_result($totalLancamentos);
$stmtCount->fetch();
$stmtCount->close();

// Define o cabeçalho para indicar que a resposta é JSON
header('Content-Type: application/json');

// Retorna o resultado em formato JSON
echo json_encode(['total' => $totalLancamentos]);
exit();