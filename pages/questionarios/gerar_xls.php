<?php
// Inicia a sessão
session_start();

// Carrega o autoload do Composer
require_once '../../vendor/autoload.php';

// Importa as classes que vamos usar
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date; // Importante: para converter datas
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Importante: para formatar datas
use PhpOffice\PhpSpreadsheet\Cell\Coordinate; // Importante: para manipular colunas (A, B, C...)

// Inclui a configuração do banco
require_once '../../php/config.php';

// Função para formatar os dados para exibição no Excel
function formatarValorParaXLS($valor, $tipo)
{
    if ($valor === null || $valor === '') {
        return ''; // Retorna vazio para a planilha
    }
    switch ($tipo) {
        case 'DATE':
            // ALTERAÇÃO: Não converte para string. Converte para "Número de Excel"
            $d = DateTime::createFromFormat('Y-m-d', $valor);
            return $d ? Date::PHPToExcel($d) : $valor;
        case 'TIME':
            // Mantemos string ou podemos converter para fração de dia (Excel Time) se necessário.
            // Por simplicidade, mantemos a formatação visual aqui, mas idealmente seria igual data.
            $d = DateTime::createFromFormat('H:i:s', $valor);
            if ($d) return $d->format('H:i');
            $d = DateTime::createFromFormat('H:i', $valor);
            return $d ? $d->format('H:i') : $valor;
        case 'NUMBER':
            // Para o Excel, é melhor manter o formato com ponto decimal
            return (float) str_replace(',', '.', $valor);
        default:
            return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}

// Validação dos filtros de entrada
if (!isset($_GET['id_questionario']) || !isset($_GET['data_inicio']) || !isset($_GET['data_fim'])) {
    exit('Filtros insuficientes para gerar o relatório.');
}
$idQuestionario = intval($_GET['id_questionario']);
$dataInicio = $_GET['data_inicio'];
$dataFim = $_GET['data_fim'];

// Busca os metadados dos campos
$stmtMeta = $conn->prepare("SELECT id_campo, nome_campo, tipo_campo FROM campos_questionario WHERE id_questionario = ? ORDER BY id_campo ASC");
$stmtMeta->bind_param('i', $idQuestionario);
$stmtMeta->execute();
$resultMeta = $stmtMeta->get_result();
$colunas = [];
while ($campo = $resultMeta->fetch_assoc()) {
    $colunas[$campo['id_campo']] = [
        'nome' => $campo['nome_campo'],
        'tipo' => $campo['tipo_campo']
    ];
}
$stmtMeta->close();

// Busca nomes do questionário e sítio
$stmtNome = $conn->prepare("
    SELECT q.nome_questionario, s.nome_sitio
    FROM questionarios q
    JOIN sitios s ON q.id_sitio = s.id_sitio
    WHERE q.id_questionario = ?
");
$stmtNome->bind_param('i', $idQuestionario);
$stmtNome->execute();
$stmtNome->bind_result($nomeQuestionario, $nomeSitio);
$stmtNome->fetch();
$stmtNome->close();

// Busca os dados das respostas
$queryDados = "SELECT r.id_lancamento, r.criado_em, r.id_campo, r.valor_resposta, u.nome AS nome_usuario FROM respostas_questionario AS r JOIN usuarios AS u ON r.id_usuario = u.id WHERE r.id_questionario = ? AND DATE(r.criado_em) BETWEEN ? AND ? ORDER BY r.id_lancamento, r.id_campo";
$stmtDados = $conn->prepare($queryDados);
$stmtDados->bind_param('iss', $idQuestionario, $dataInicio, $dataFim);
$stmtDados->execute();
$resultDados = $stmtDados->get_result();

// Estrutura os dados (Pivot)
$lancamentosFormatados = [];
while ($linha = $resultDados->fetch_assoc()) {
    $idLancamento = $linha['id_lancamento'];
    if (!isset($lancamentosFormatados[$idLancamento])) {
        // ALTERAÇÃO: Convertendo a data de criação ('criado_em') para Excel Date também
        $dataCriacaoObj = new DateTime($linha['criado_em']); // Cria objeto Data
        $dataCriacaoExcel = Date::PHPToExcel($dataCriacaoObj); // Converte para número do Excel

        $lancamentosFormatados[$idLancamento] = [
            'id_lancamento' => $idLancamento, 
            'usuario' => $linha['nome_usuario'], 
            'criado_em' => $dataCriacaoExcel, // Passar o número, não a string
            'respostas' => []
        ];
    }
    $lancamentosFormatados[$idLancamento]['respostas'][$linha['id_campo']] = $linha['valor_resposta'];
}
$stmtDados->close();

// --- GERAÇÃO DO ARQUIVO XLSX ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(substr($nomeQuestionario, 0, 31));

// Monta o cabeçalho
$cabecalho = ['Data/Hora Lançamento', 'ID Lançamento', 'Usuário', 'Sítio'];
foreach ($colunas as $colunaInfo) {
    $cabecalho[] = $colunaInfo['nome'];
}
$sheet->fromArray($cabecalho, NULL, 'A1');

// Monta as linhas de dados
$linhaAtual = 2;
foreach ($lancamentosFormatados as $lancamento) {
    // Adiciona o nome do Sítio na linha
    $dadosLinha = [$lancamento['criado_em'], $lancamento['id_lancamento'], $lancamento['usuario'], $nomeSitio];

    foreach ($colunas as $idCampo => $colunaInfo) {
        $valorBruto = $lancamento['respostas'][$idCampo] ?? null;
        $dadosLinha[] = formatarValorParaXLS($valorBruto, $colunaInfo['tipo']);
    }
    $sheet->fromArray($dadosLinha, NULL, 'A' . $linhaAtual);
    $linhaAtual++;
}

// --- FORMATAÇÃO DAS CÉLULAS  ---

// 1. Descobrir até qual linha existe dados
$ultimaLinha = $linhaAtual - 1;
if ($ultimaLinha < 2) $ultimaLinha = 2; // Segurança caso não tenha dados

// 2. Formatar a Coluna A (Data de Lançamento) que é fixa
// Formatamos como Data e Hora: dd/mm/yyyy hh:mm:ss
$sheet->getStyle('A2:A' . $ultimaLinha)
      ->getNumberFormat()
      ->setFormatCode('dd/mm/yyyy hh:mm:ss');

// 3. Formatar as colunas dinâmicas (começam na coluna E, índice 5)
$colIndex = 5; // A=1, B=2, C=3, D=4, Coluna E = 5
foreach ($colunas as $colunaInfo) {
    // Se o tipo do campo for DATE, aplicar a formatação na coluna inteira
    if ($colunaInfo['tipo'] === 'DATE') {
        // Converte índice numérico (5) para letra ('E')
        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
        
        // Aplica o formato dd/mm/yyyy na coluna inteira (da linha 2 até a última)
        $sheet->getStyle($colLetter . '2:' . $colLetter . $ultimaLinha)
              ->getNumberFormat()
              ->setFormatCode('dd/mm/yyyy'); // ou NumberFormat::FORMAT_DATE_DDMMYYYY
    }
    
    $colIndex++;
}

// Ajuste automático de largura
foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$nomeArquivo = "exportacao_" . preg_replace('/[^a-z0-9_]/i', '', str_replace(' ', '_', $nomeQuestionario)) . "_" . date('Y_m_d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $nomeArquivo . '"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>