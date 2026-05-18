<?php
session_start();
require_once '../../php/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

/* =========================================================
   PROCESSAR SALVAMENTO
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idLancamento = $_POST['id_lancamento'] ?? null;
    $idQuestionario = $_POST['id_questionario'] ?? null;

    if (!$idLancamento || !$idQuestionario) {
        $_SESSION['mensagem_erro'] = "Dados inválidos.";
        header('Location: verificar.php');
        exit();
    }

    // Verifica permissão
    $id_usuario_logado = $_SESSION['usuario_id'];

    $stmtPerm = $conn->prepare("
        SELECT COUNT(*)
        FROM questionario_permissoes
        WHERE id_questionario = ?
        AND id_usuario = ?
    ");

    $stmtPerm->bind_param("ii", $idQuestionario, $id_usuario_logado);
    $stmtPerm->execute();
    $stmtPerm->bind_result($countPerm);
    $stmtPerm->fetch();
    $stmtPerm->close();

    if ($countPerm == 0) {
        $_SESSION['mensagem_erro'] = "Você não possui permissão.";
        header('Location: verificar.php');
        exit();
    }

    $conn->begin_transaction();

    try {

        $stmtUpdate = $conn->prepare("
            UPDATE respostas_questionario
            SET valor_resposta = ?
            WHERE id_resposta = ?
        ");

        foreach ($_POST['respostas'] as $idResposta => $valorResposta) {

            if (is_array($valorResposta)) {
                $valorResposta = json_encode(
                    array_values($valorResposta),
                    JSON_UNESCAPED_UNICODE
                );
            } else {
                $valorResposta = trim($valorResposta);
            }

            $stmtUpdate->bind_param(
                "si",
                $valorResposta,
                $idResposta
            );

            if (!$stmtUpdate->execute()) {
                throw new Exception("Erro ao atualizar resposta ID: " . $idResposta);
            }
        }

        $stmtUpdate->close();

        $conn->commit();

        $_SESSION['mensagem_sucesso'] = "Lançamento atualizado com sucesso.";

    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['mensagem_erro'] = "Erro ao atualizar: " . $e->getMessage();
    }

    header('Location: verificar.php?questionario=' . $idQuestionario);
    exit();
}

/* =========================================================
   EXIBIR FORMULÁRIO
========================================================= */

$idLancamento = $_GET['id'] ?? null;

if (!$idLancamento) {
    $_SESSION['mensagem_erro'] = "Lançamento inválido.";
    header('Location: verificar.php');
    exit();
}

/* =========================================================
   BUSCA RESPOSTAS
========================================================= */

$query = "
SELECT
    r.id_resposta,
    r.valor_resposta,
    r.id_questionario,
    c.nome_campo,
    c.tipo_campo,
    c.opcoes,
    q.nome_questionario
FROM respostas_questionario r
JOIN campos_questionario c
    ON r.id_campo = c.id_campo
JOIN questionarios q
    ON r.id_questionario = q.id_questionario
WHERE r.id_lancamento = ?
ORDER BY r.id_resposta ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $idLancamento);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows <= 0) {
    $_SESSION['mensagem_erro'] = "Lançamento não encontrado.";
    header('Location: verificar.php');
    exit();
}

$respostas = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$nomeQuestionario = $respostas[0]['nome_questionario'];
$idQuestionario = $respostas[0]['id_questionario'];

/* =========================================================
   VERIFICA PERMISSÃO
========================================================= */

$id_usuario_logado = $_SESSION['usuario_id'];

$stmtPerm = $conn->prepare("
    SELECT COUNT(*)
    FROM questionario_permissoes
    WHERE id_questionario = ?
    AND id_usuario = ?
");

$stmtPerm->bind_param("ii", $idQuestionario, $id_usuario_logado);
$stmtPerm->execute();
$stmtPerm->bind_result($countPerm);
$stmtPerm->fetch();
$stmtPerm->close();

if ($countPerm == 0) {
    $_SESSION['mensagem_erro'] = "Você não possui permissão.";
    header('Location: verificar.php');
    exit();
}

/* =========================================================
   AUXILIAR
========================================================= */

function getTipoInput($tipoCampo)
{
    switch ($tipoCampo) {
        case 'DATE':
            return 'date';

        case 'TIME':
            return 'time';

        case 'NUMBER':
            return 'number';

        default:
            return 'text';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <title>Editar Lançamento</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
</head>

<body>

<?php include '../../php/header.php'; ?>
<?php include '../../php/sidebar.php'; ?>

<div class="container mt-5" style="margin-left: 250px;">

    <h1>Editar Lançamento</h1>

    <h5>
        Questionário:
        <?= htmlspecialchars($nomeQuestionario) ?>
    </h5>

    <p>
        ID do lançamento:
        <?= htmlspecialchars($idLancamento) ?>
    </p>

    <form method="POST" action="editar_lancamento.php">

        <input
            type="hidden"
            name="id_lancamento"
            value="<?= htmlspecialchars($idLancamento) ?>"
        >

        <input
            type="hidden"
            name="id_questionario"
            value="<?= htmlspecialchars($idQuestionario) ?>"
        >

        <?php foreach ($respostas as $resposta): ?>

            <?php
                $tipoCampo = $resposta['tipo_campo'];
                $valor = $resposta['valor_resposta'];
                $idResposta = $resposta['id_resposta'];
            ?>

            <div class="mb-4">

                <label class="form-label">
                    <strong>
                        <?= htmlspecialchars($resposta['nome_campo']) ?>
                    </strong>
                </label>

                <?php if (
                    $tipoCampo === 'TEXT' ||
                    $tipoCampo === 'NUMBER' ||
                    $tipoCampo === 'DATE' ||
                    $tipoCampo === 'TIME'
                ): ?>

                    <?php
                        $tipoInput = getTipoInput($tipoCampo);

                        $step = ($tipoInput === 'number')
                            ? 'step="any"'
                            : '';
                    ?>

                    <input
                        type="<?= $tipoInput ?>"
                        class="form-control"
                        name="respostas[<?= $idResposta ?>]"
                        value="<?= htmlspecialchars($valor) ?>"
                        <?= $step ?>
                        required
                    >

                <?php elseif ($tipoCampo === 'DROPDOWN'): ?>

                    <?php
                        $opcoes = json_decode($resposta['opcoes'], true);
                    ?>

                    <select
                        class="form-select"
                        name="respostas[<?= $idResposta ?>]"
                        required
                    >

                        <option value="">
                            Selecione...
                        </option>

                        <?php foreach ($opcoes as $opcao): ?>

                            <option
                                value="<?= htmlspecialchars($opcao) ?>"
                                <?= ($valor == $opcao) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($opcao) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                <?php elseif ($tipoCampo === 'CHECKBOX'): ?>

                    <?php
                        $opcoes = json_decode($resposta['opcoes'], true);

                        $valoresSelecionados = json_decode($valor, true);

                        if (!is_array($valoresSelecionados)) {
                            $valoresSelecionados = [];
                        }
                    ?>

                    <div class="border rounded p-3">

                        <?php foreach ($opcoes as $opcao): ?>

                            <?php
                                $checked = in_array(
                                    $opcao,
                                    $valoresSelecionados
                                )
                                    ? 'checked'
                                    : '';
                            ?>

                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="respostas[<?= $idResposta ?>][]"
                                    value="<?= htmlspecialchars($opcao) ?>"
                                    id="resposta_<?= $idResposta ?>_<?= md5($opcao) ?>"
                                    <?= $checked ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="resposta_<?= $idResposta ?>_<?= md5($opcao) ?>"
                                >
                                    <?= htmlspecialchars($opcao) ?>
                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

        <hr>

        <button type="submit" class="btn btn-success">
            Salvar Alterações
        </button>

        <a
            href="verificar.php?questionario=<?= htmlspecialchars($idQuestionario) ?>"
            class="btn btn-secondary"
        >
            Cancelar
        </a>

    </form>

</div>

</body>
</html>