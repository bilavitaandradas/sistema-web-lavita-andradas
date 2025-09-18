<?php
// Inicia a sessão para gerenciar o estado do usuário
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once '../../php/config.php';

// Verifica se o usuário está logado, redirecionando para a página inicial se não estiver
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

// A função formatarValor continua a mesma, sem alterações
function formatarValor($valor, $tipo)
{
    if ($valor === null || $valor === '')
        return '-';
    switch ($tipo) {
        case 'DATE':
            $d = DateTime::createFromFormat('Y-m-d', $valor);
            return $d ? $d->format('d/m/Y') : $valor;
        case 'TIME':
            $d = DateTime::createFromFormat('H:i:s', $valor);
            return $d ? $d->format('H:i') : $valor;
        case 'NUMBER':
            return number_format((float) $valor, 2, ',', '.');
        default:
            return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}

// Consulta para obter a lista de TODOS os questionários (para que todos possam visualizar)
$queryQuestionarios = "SELECT id_questionario, nome_questionario FROM questionarios ORDER BY nome_questionario";
$result = $conn->query($queryQuestionarios);
$questionarios = $result->fetch_all(MYSQLI_ASSOC);

// Inicializa arrays e variáveis
$dados = [];
$colunas = [];
$nomeQuestionarioSelecionado = "";
$nomeSitioSelecionado = ""; // <-- VARIÁVEL PARA GUARDAR O NOME DO SÍTIO
$idQuestionario = null;
$usuario_tem_permissao = false;

// 1. DETERMINAR QUAL QUESTIONÁRIO CARREGAR (VIA POST ou GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['questionario'])) {
    $idQuestionario = intval($_POST['questionario']);
} elseif (isset($_GET['questionario'])) {
    $idQuestionario = intval($_GET['questionario']);
}

// 2. BUSCAR OS DADOS SE UM QUESTIONÁRIO FOI SELECIONADO
if ($idQuestionario) {

    // VERIFICAÇÃO DE PERMISSÃO DE CRUD
    $id_usuario_logado = $_SESSION['usuario_id'];
    $stmtPerm = $conn->prepare("SELECT COUNT(*) FROM questionario_permissoes WHERE id_questionario = ? AND id_usuario = ?");
    $stmtPerm->bind_param("ii", $idQuestionario, $id_usuario_logado);
    $stmtPerm->execute();
    $stmtPerm->bind_result($count);
    $stmtPerm->fetch();
    $stmtPerm->close();
    if ($count > 0) {
        $usuario_tem_permissao = true;
    }

    // --- BUSCAR O NOME DO SÍTIO ---
    $stmt = $conn->prepare("
        SELECT q.nome_questionario, s.nome_sitio
        FROM questionarios q
        JOIN sitios s ON q.id_sitio = s.id_sitio
        WHERE q.id_questionario = ?
    ");
    $stmt->bind_param('i', $idQuestionario);
    $stmt->execute();
    // Agora temos duas variáveis para receber os resultados
    $stmt->bind_result($nomeQuestionarioSelecionado, $nomeSitioSelecionado);
    $stmt->fetch();
    $stmt->close();
    // -----------------------------------------------------

    // O restante da busca de dados continua normalmente...
    $stmtCampos = $conn->prepare("SELECT id_campo, nome_campo, tipo_campo FROM campos_questionario WHERE id_questionario = ?");
    $stmtCampos->bind_param('i', $idQuestionario);
    $stmtCampos->execute();
    $resultCampos = $stmtCampos->get_result();
    while ($campo = $resultCampos->fetch_assoc()) {
        $colunas[$campo['id_campo']] = [
            'nome' => $campo['nome_campo'],
            'tipo' => $campo['tipo_campo']
        ];
    }
    $stmtCampos->close();

    $stmtLancamentos = $conn->prepare("SELECT DISTINCT id_lancamento, criado_em, id_usuario FROM respostas_questionario WHERE id_questionario = ? ORDER BY criado_em DESC LIMIT 30");
    $stmtLancamentos->bind_param('i', $idQuestionario);
    $stmtLancamentos->execute();
    $resultLancamentos = $stmtLancamentos->get_result();
    while ($lancamento = $resultLancamentos->fetch_assoc()) {
        $idLancamento = $lancamento['id_lancamento'];
        $criadoEm = date('d/m/Y H:i:s', strtotime($lancamento['criado_em']));
        $idUsuario = $lancamento['id_usuario'];
        $stmtUser = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
        $stmtUser->bind_param('i', $idUsuario);
        $stmtUser->execute();
        $stmtUser->bind_result($nomeUsuario);
        $stmtUser->fetch();
        $stmtUser->close();
        $stmtRespostas = $conn->prepare("SELECT id_campo, valor_resposta FROM respostas_questionario WHERE id_lancamento = ?");
        $stmtRespostas->bind_param('s', $idLancamento);
        $stmtRespostas->execute();
        $resultRespostas = $stmtRespostas->get_result();
        $respostas = [];
        while ($resp = $resultRespostas->fetch_assoc()) {
            $respostas[$resp['id_campo']] = $resp['valor_resposta'];
        }
        $stmtRespostas->close();
        $dados[] = [
            'id_lancamento' => $idLancamento,
            'criado_em' => $criadoEm,
            'usuario' => $nomeUsuario,
            'respostas' => $respostas
        ];
    }
    $stmtLancamentos->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Verificar Respostas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <div class="container mt-5" style="margin-left: 250px;">
        <h1>Verificar Respostas</h1>

        <form method="POST" class="mb-4">
            <label for="questionario" class="form-label">Selecione o Questionário:</label>
            <select name="questionario" id="questionario" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($questionarios as $q): ?>
                    <option value="<?= $q['id_questionario'] ?>" <?= (isset($idQuestionario) && $idQuestionario == $q['id_questionario']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($q['nome_questionario']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary mt-2">Carregar</button>
        </form>

        <?php if (!empty($dados)): ?>
            <h3>Últimos 30 lançamentos de: <?= htmlspecialchars($nomeQuestionarioSelecionado) ?> (Sítio:
                <?= htmlspecialchars($nomeSitioSelecionado) ?>)</h3>

            <table id="tabelaRespostas" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">ID Lanç.</th>
                        <th class="text-center">Usuário</th>
                        <th class="text-center">Data/Hora</th>
                        <th class="text-center">Sítio</th>
                        <?php foreach ($colunas as $campoInfo): ?>
                            <th class="text-center"><?= htmlspecialchars($campoInfo['nome']) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados as $dado): ?>
                        <tr>
                            <td><?= htmlspecialchars($dado['id_lancamento']) ?></td>
                            <td><?= htmlspecialchars($dado['usuario']) ?></td>
                            <td><?= htmlspecialchars($dado['criado_em']) ?></td>
                            <td><?= htmlspecialchars($nomeSitioSelecionado) ?></td>
                            <?php foreach ($colunas as $idCampo => $campoInfo): ?>
                                <td><?= formatarValor($dado['respostas'][$idCampo] ?? '-', $campoInfo['tipo']) ?></td>
                            <?php endforeach; ?>

                            <td class="text-center">
                                <?php if ($usuario_tem_permissao): ?>
                                    <a href="editar_lancamento.php?id=<?= urlencode($dado['id_lancamento']) ?>"
                                        class="btn btn-sm btn-warning">Editar</a>
                                    <a href="excluir_lancamento.php?id=<?= urlencode($dado['id_lancamento']) ?>&qid=<?= urlencode($idQuestionario) ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Tem certeza que deseja excluir este lançamento?')">Excluir</a>
                                <?php else: ?>
                                    <span class="text-muted small">Sem permissão</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>