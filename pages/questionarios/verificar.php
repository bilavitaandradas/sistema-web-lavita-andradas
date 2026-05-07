<?php
session_start();
require_once '../../php/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

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

/* ===================== ALTERAÇÃO AQUI ===================== */
$queryQuestionarios = "
SELECT q.id_questionario, q.nome_questionario, s.nome_sitio
FROM questionarios q
JOIN sitios s ON q.id_sitio = s.id_sitio
ORDER BY s.nome_sitio, q.nome_questionario
";
$result = $conn->query($queryQuestionarios);
$questionarios = $result->fetch_all(MYSQLI_ASSOC);
/* ========================================================= */

$dados = [];
$colunas = [];
$nomeQuestionarioSelecionado = "";
$nomeSitioSelecionado = "";
$idQuestionario = null;
$usuario_tem_permissao = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['questionario'])) {
    $idQuestionario = intval($_POST['questionario']);
} elseif (isset($_GET['questionario'])) {
    $idQuestionario = intval($_GET['questionario']);
}

if ($idQuestionario) {

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

    $stmt = $conn->prepare("
        SELECT q.nome_questionario, s.nome_sitio
        FROM questionarios q
        JOIN sitios s ON q.id_sitio = s.id_sitio
        WHERE q.id_questionario = ?
    ");
    $stmt->bind_param('i', $idQuestionario);
    $stmt->execute();
    $stmt->bind_result($nomeQuestionarioSelecionado, $nomeSitioSelecionado);
    $stmt->fetch();
    $stmt->close();

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- SELECT2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
</head>

<body>
<?php include '../../php/header.php'; ?>
<?php include '../../php/sidebar.php'; ?>

<div class="container mt-5" style="margin-left: 250px;">
    <h1>Verificar Respostas</h1>

    <form method="POST" class="mb-4">
        <label for="questionario" class="form-label">Selecione o Questionário:</label>

        <select name="questionario" id="questionario" class="form-select" required>
            <option value="">Selecione ou pesquise...</option>
            <?php foreach ($questionarios as $q): ?>
                <option value="<?= $q['id_questionario'] ?>" <?= (isset($idQuestionario) && $idQuestionario == $q['id_questionario']) ? 'selected' : '' ?>>
                    [<?= htmlspecialchars($q['nome_sitio']) ?>] - <?= htmlspecialchars($q['nome_questionario']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary mt-2">Carregar</button>
    </form>

    <?php if (!empty($dados)): ?>
        <h3>Últimos 30 lançamentos de: <?= htmlspecialchars($nomeQuestionarioSelecionado) ?> (Sítio: <?= htmlspecialchars($nomeSitioSelecionado) ?>)</h3>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Data/Hora</th>
                    <th>Sítio</th>
                    <?php foreach ($colunas as $campoInfo): ?>
                        <th><?= htmlspecialchars($campoInfo['nome']) ?></th>
                    <?php endforeach; ?>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dados as $dado): ?>
                    <tr>
                        <td><?= $dado['id_lancamento'] ?></td>
                        <td><?= $dado['usuario'] ?></td>
                        <td><?= $dado['criado_em'] ?></td>
                        <td><?= $nomeSitioSelecionado ?></td>

                        <?php foreach ($colunas as $idCampo => $campoInfo): ?>
                            <td><?= formatarValor($dado['respostas'][$idCampo] ?? '-', $campoInfo['tipo']) ?></td>
                        <?php endforeach; ?>

                        <td>
                            <?php if ($usuario_tem_permissao): ?>
                                <a href="editar_lancamento.php?id=<?= $dado['id_lancamento'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="excluir_lancamento.php?id=<?= $dado['id_lancamento'] ?>&qid=<?= $idQuestionario ?>" class="btn btn-danger btn-sm" onclick="return confirm('Confirmar exclusão?')">Excluir</a>
                            <?php else: ?>
                                <span class="text-muted">Sem permissão</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#questionario').select2({
        placeholder: "Selecione ou pesquise um questionário",
        allowClear: true,
        width: '100%'
    });
});
</script>

</body>
</html>