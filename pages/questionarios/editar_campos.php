<?php
session_start();

// --- VERIFICA PERMISSÕES ---
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin','qualidade', 'gerente'])) {
    header('Location: /TCC/index.php');
    exit();
}

require_once '../../php/config.php';

// --- VERIFICA SE O ID DO QUESTIONÁRIO FOI INFORMADO ---
if (!isset($_GET['id_questionario']) || !is_numeric($_GET['id_questionario'])) {
    die('ID do questionário inválido.');
}

$id_questionario = (int) $_GET['id_questionario'];
$mensagem = "";

// --- BUSCA O NOME DO QUESTIONÁRIO ---
$stmtQ = $conn->prepare("
    SELECT nome_questionario 
    FROM questionarios 
    WHERE id_questionario = ?
");
$stmtQ->bind_param("i", $id_questionario);
$stmtQ->execute();

$resultQ = $stmtQ->get_result();
$questionario = $resultQ->fetch_assoc();

if (!$questionario) {
    die("Questionário não encontrado.");
}

// --- PROCESSAMENTO PARA ADICIONAR NOVO CAMPO ---
if (isset($_POST['adicionar_campo'])) {

    $nome_campo = trim($_POST['nome_campo']);
    $tipo_campo = $_POST['tipo_campo'];

    $dependente_de = !empty($_POST['dependente_de'])
        ? intval($_POST['dependente_de'])
        : null;

    $dependente_valor = !empty($_POST['dependente_valor'])
        ? trim($_POST['dependente_valor'])
        : null;

    $opcoes = null;

    // DROPDOWN e CHECKBOX usam opções
    if (
        in_array($tipo_campo, ['DROPDOWN', 'CHECKBOX']) &&
        !empty($_POST['opcoes'])
    ) {

        $linhas = explode("\n", $_POST['opcoes']);

        $linhas = array_map('trim', $linhas);

        $linhas = array_filter($linhas);

        $opcoes = json_encode(
            array_values($linhas),
            JSON_UNESCAPED_UNICODE
        );
    }

    if ($nome_campo && $tipo_campo) {

        $stmt = $conn->prepare("
            INSERT INTO campos_questionario (
                id_questionario,
                nome_campo,
                tipo_campo,
                opcoes,
                dependente_de,
                dependente_valor,
                criado_em
            )
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "isssis",
            $id_questionario,
            $nome_campo,
            $tipo_campo,
            $opcoes,
            $dependente_de,
            $dependente_valor
        );

        if ($stmt->execute()) {

            $mensagem = "
                <div class='alert alert-success'>
                    Campo adicionado com sucesso.
                </div>
            ";
        }
    }
}

// --- PROCESSAMENTO PARA EXCLUIR UM CAMPO ---
if (isset($_POST['excluir_campo'])) {

    $id_campo = intval($_POST['id_campo']);

    $stmt = $conn->prepare("
        DELETE FROM campos_questionario
        WHERE id_campo = ?
        AND id_questionario = ?
    ");

    $stmt->bind_param("ii", $id_campo, $id_questionario);

    if ($stmt->execute()) {

        $mensagem = "
            <div class='alert alert-success'>
                Campo excluído com sucesso.
            </div>
        ";
    }
}

// --- LISTA OS CAMPOS ATUAIS ---
$stmtCampos = $conn->prepare("
    SELECT 
        c.*,
        cd.nome_campo AS nome_dependencia
    FROM campos_questionario c
    LEFT JOIN campos_questionario cd
        ON c.dependente_de = cd.id_campo
    WHERE c.id_questionario = ?
    ORDER BY c.id_campo ASC
");

$stmtCampos->bind_param("i", $id_questionario);
$stmtCampos->execute();

$resultCampos = $stmtCampos->get_result();

// --- BUSCA CAMPOS PARA DEPENDÊNCIA ---
$stmtDependencias = $conn->prepare("
    SELECT id_campo, nome_campo
    FROM campos_questionario
    WHERE id_questionario = ?
    ORDER BY nome_campo ASC
");

$stmtDependencias->bind_param("i", $id_questionario);
$stmtDependencias->execute();

$camposDependencia = $stmtDependencias->get_result();

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">

    <title>
        Editar Campos - <?= htmlspecialchars($questionario['nome_questionario']) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
</head>

<body>

<?php include '../../php/header.php'; ?>
<?php include '../../php/sidebar.php'; ?>

<main class="main-content p-5" style="margin-left: 250px;">

    <h2>
        Editar Campos -
        <?= htmlspecialchars($questionario['nome_questionario']) ?>
    </h2>

    <?= $mensagem ?>

    <hr>

    <h4>Campos Cadastrados</h4>

    <table class="table table-bordered align-middle">

        <thead>
            <tr>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Opções</th>
                <th>Dependência</th>
                <th width="180">Ações</th>
            </tr>
        </thead>

        <tbody>

        <?php while ($campo = $resultCampos->fetch_assoc()): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($campo['nome_campo']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($campo['tipo_campo']) ?>
                </td>

                <td>

                    <?php

                    if (!empty($campo['opcoes'])) {

                        $opcoes_array = json_decode(
                            $campo['opcoes'],
                            true
                        );

                        echo htmlspecialchars(
                            implode(', ', $opcoes_array)
                        );

                    } else {

                        echo '-';
                    }

                    ?>

                </td>

                <td>

                    <?php if (!empty($campo['dependente_de'])): ?>

                        Campo: <?= htmlspecialchars($campo['nome_dependencia']) ?>

                        <br>

                        Valor:
                        <?= htmlspecialchars($campo['dependente_valor']) ?>

                    <?php else: ?>

                        -

                    <?php endif; ?>

                </td>

                <td>

                    <a
                        href="editar_campo.php?id_campo=<?= $campo['id_campo'] ?>&id_questionario=<?= $id_questionario ?>"
                        class="btn btn-warning btn-sm"
                    >
                        Editar
                    </a>

                    <form method="post" class="d-inline">

                        <input
                            type="hidden"
                            name="id_campo"
                            value="<?= $campo['id_campo'] ?>"
                        >

                        <button
                            name="excluir_campo"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Deseja excluir este campo?')"
                        >
                            Excluir
                        </button>

                    </form>

                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>

    </table>

    <hr>

    <h4>Adicionar Novo Campo</h4>

    <form method="post">

        <div class="mb-3">

            <label class="form-label">
                Nome do Campo
            </label>

            <input
                type="text"
                name="nome_campo"
                class="form-control"
                required
            >

        </div>

        <div class="mb-3">

            <label class="form-label">
                Tipo do Campo
            </label>

            <select
                name="tipo_campo"
                id="tipo_campo"
                class="form-select"
                required
            >

                <option value="TEXT">Texto</option>
                <option value="NUMBER">Número</option>
                <option value="DATE">Data</option>
                <option value="TIME">Hora</option>
                <option value="DROPDOWN">Dropdown</option>
                <option value="CHECKBOX">Múltipla Escolha</option>

            </select>

        </div>

        <div class="mb-3" id="container_opcoes">

            <label class="form-label">
                Opções
            </label>

            <textarea
                name="opcoes"
                class="form-control"
                rows="5"
                placeholder="Uma opção por linha"
            ></textarea>

        </div>

        <hr>

        <h5>Dependência (Opcional)</h5>

        <div class="mb-3">

            <label class="form-label">
                Depende de qual campo?
            </label>

            <select
                name="dependente_de"
                class="form-select"
            >

                <option value="">
                    Nenhum
                </option>

                <?php while ($dep = $camposDependencia->fetch_assoc()): ?>

                    <option value="<?= $dep['id_campo'] ?>">

                        <?= htmlspecialchars($dep['nome_campo']) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>

        <div class="mb-3">

            <label class="form-label">
                Mostrar quando valor for:
            </label>

            <input
                type="text"
                name="dependente_valor"
                class="form-control"
                placeholder="Ex: Colheita"
            >

        </div>

        <button
            type="submit"
            name="adicionar_campo"
            class="btn btn-success"
        >
            Adicionar Campo
        </button>

        <a
            href="configuracoes.php"
            class="btn btn-secondary"
        >
            Voltar
        </a>

    </form>

</main>

<script>

const tipoCampo = document.getElementById('tipo_campo');

const containerOpcoes =
    document.getElementById('container_opcoes');

function toggleOpcoes() {

    const mostrar =
        ['DROPDOWN', 'CHECKBOX']
        .includes(tipoCampo.value);

    containerOpcoes.style.display =
        mostrar ? 'block' : 'none';
}

tipoCampo.addEventListener('change', toggleOpcoes);

toggleOpcoes();

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>