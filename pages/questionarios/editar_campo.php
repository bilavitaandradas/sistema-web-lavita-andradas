<?php
session_start();

if (
    !isset($_SESSION['usuario_id']) ||
    !in_array($_SESSION['role'], ['admin', 'gerente'])
) {
    header('Location: /TCC/index.php');
    exit();
}

require_once '../../php/config.php';

// Verifica se o ID do campo foi passado
if (!isset($_GET['id_campo']) || !is_numeric($_GET['id_campo'])) {
    die("ID do campo inválido.");
}

$id_campo = (int) $_GET['id_campo'];

$id_questionario = isset($_GET['id_questionario'])
    ? intval($_GET['id_questionario'])
    : 0;

// Recupera os dados do campo
$stmt = $conn->prepare("
    SELECT *
    FROM campos_questionario
    WHERE id_campo = ?
");

$stmt->bind_param("i", $id_campo);

$stmt->execute();

$result = $stmt->get_result();

$campo = $result->fetch_assoc();

if (!$campo) {
    die("Campo não encontrado.");
}

$mensagem = "";

// Busca campos disponíveis para dependência
$stmtDependencias = $conn->prepare("
    SELECT id_campo, nome_campo, opcoes
    FROM campos_questionario
    WHERE id_questionario = ?
    AND id_campo <> ?
    AND tipo_campo = 'DROPDOWN'
    ORDER BY nome_campo ASC
");

$stmtDependencias->bind_param(
    "ii",
    $campo['id_questionario'],
    $id_campo
);

$stmtDependencias->execute();

$camposDependencia = $stmtDependencias->get_result();

$dependenciasJS = [];

$camposDependencia->data_seek(0);

while ($dep = $camposDependencia->fetch_assoc()) {

    $dependenciasJS[$dep['id_campo']] = [
        'nome' => $dep['nome_campo'],
        'opcoes' => json_decode($dep['opcoes'], true) ?? []
    ];
}

$camposDependencia->data_seek(0);

// Se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome_campo = trim($_POST['nome_campo']);

    $tipo_campo = $_POST['tipo_campo'];

    $opcoes = null;

    $dependente_de = !empty($_POST['dependente_de'])
        ? intval($_POST['dependente_de'])
        : null;

    if (!empty($_POST['dependente_valor'])) {

    $dependente_valor = json_encode(
        array_values($_POST['dependente_valor']),
        JSON_UNESCAPED_UNICODE
    );

} else {

    $dependente_valor = null;
}

    if (
        in_array($tipo_campo, ['DROPDOWN', 'CHECKBOX']) &&
        !empty($_POST['opcoes_lista'])
    ) {

        $opcoesArray = $_POST['opcoes_lista'];

        $opcoesArray = array_map('trim', $opcoesArray);

        $opcoesArray = array_filter($opcoesArray);

        $opcoesArray = array_unique($opcoesArray);

        natcasesort($opcoesArray);

        $opcoes = json_encode(
            array_values($opcoesArray),
            JSON_UNESCAPED_UNICODE
        );
    }

    $stmt = $conn->prepare("
        UPDATE campos_questionario
        SET
            nome_campo = ?,
            tipo_campo = ?,
            opcoes = ?,
            dependente_de = ?,
            dependente_valor = ?,
            atualizado_em = NOW()
        WHERE id_campo = ?
    ");

    $stmt->bind_param(
        "sssisi",
        $nome_campo,
        $tipo_campo,
        $opcoes,
        $dependente_de,
        $dependente_valor,
        $id_campo
    );

    if ($stmt->execute()) {

        $mensagem = "
            <div class='alert alert-success'>
                Campo atualizado com sucesso.
            </div>
        ";

        // Atualiza os dados para exibir na tela novamente
        $campo['nome_campo'] = $nome_campo;
        $campo['tipo_campo'] = $tipo_campo;
        $campo['opcoes'] = $opcoes;
        $campo['dependente_de'] = $dependente_de;
        $campo['dependente_valor'] = $dependente_valor;

        $dependenteValorSelecionado =
    json_decode($campo['dependente_valor'], true);

if (!is_array($dependenteValorSelecionado)) {
    $dependenteValorSelecionado = [];
}
    } else {

        $mensagem = "
            <div class='alert alert-danger'>
                Erro ao atualizar o campo.
            </div>
        ";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Editar Campo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">

</head>

<body>

    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-5" style="margin-left: 250px;">

        <h2>Editar Campo</h2>

        <?= $mensagem ?>

        <form method="post">

            <div class="mb-3">

                <label class="form-label">
                    Nome do Campo
                </label>

                <input type="text" name="nome_campo" class="form-control"
                    value="<?= htmlspecialchars($campo['nome_campo']) ?>" required>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Tipo do Campo
                </label>

                <select name="tipo_campo" id="tipo_campo" class="form-select" required>

                    <option value="TEXT" <?= $campo['tipo_campo'] == 'TEXT' ? 'selected' : '' ?>>
                        Texto
                    </option>

                    <option value="NUMBER" <?= $campo['tipo_campo'] == 'NUMBER' ? 'selected' : '' ?>>
                        Número
                    </option>

                    <option value="DATE" <?= $campo['tipo_campo'] == 'DATE' ? 'selected' : '' ?>>
                        Data
                    </option>

                    <option value="TIME" <?= $campo['tipo_campo'] == 'TIME' ? 'selected' : '' ?>>
                        Hora
                    </option>

                    <option value="DROPDOWN" <?= $campo['tipo_campo'] == 'DROPDOWN' ? 'selected' : '' ?>>
                        Dropdown
                    </option>

                    <option value="CHECKBOX" <?= $campo['tipo_campo'] == 'CHECKBOX' ? 'selected' : '' ?>>
                        Múltipla Escolha
                    </option>

                </select>

            </div>


            <div class="mb-3" id="container_opcoes">

                <label class="form-label">
                    Opções
                </label>

                <div class="input-group mb-2">

                    <input type="text" id="novaOpcao" class="form-control" placeholder="Digite uma opção">

                    <button type="button" class="btn btn-primary" onclick="adicionarOpcao()">
                        Adicionar
                    </button>

                </div>

                <div id="listaOpcoes" class="border rounded p-2" style="min-height:120px;"></div>

            </div>

            <hr>

            <h5>Dependência (Opcional)</h5>

            <div class="mb-3">

                <label class="form-label">
                    Depende de qual campo?
                </label>

                <select name="dependente_de" class="form-select">

                    <option value="">
                        Nenhum
                    </option>

                    <?php while ($dep = $camposDependencia->fetch_assoc()): ?>

                        <option value="<?= $dep['id_campo'] ?>" <?= $campo['dependente_de'] == $dep['id_campo'] ? 'selected' : '' ?>
                            >

                            <?= htmlspecialchars($dep['nome_campo']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="mb-3">

    <label class="form-label">
        Mostrar quando o campo pai possuir um destes valores:
    </label>

    <div
        id="container_dependente_valores"
        class="border rounded p-2"
        style="min-height:80px;">

    </div>

</div>

            <button type="submit" class="btn btn-primary">
                Salvar
            </button>

            <a href="editar_campos.php?id_questionario=<?= $campo['id_questionario'] ?>" class="btn btn-secondary">
                Voltar
            </a>

        </form>

    </main>

    <script>
        const dependencias =
         <?= json_encode(
             $dependenciasJS,
            JSON_UNESCAPED_UNICODE
         ); ?>;

        const valoresSelecionados =
        <?= json_encode(
            $dependenteValorSelecionado ?? [],
            JSON_UNESCAPED_UNICODE
        ); ?>;

        const tipoCampo =
            document.getElementById('tipo_campo');

        const containerOpcoes =
            document.getElementById('container_opcoes');

        function toggleOpcoes() {

            const mostrar =
                ['DROPDOWN', 'CHECKBOX']
                    .includes(tipoCampo.value);

            containerOpcoes.style.display =
                mostrar ? 'block' : 'none';
        }

        let opcoes = <?= json_encode(
            json_decode($campo['opcoes'] ?? '[]', true) ?: [],
            JSON_UNESCAPED_UNICODE
        ); ?>;

        function renderizarOpcoes() {

            opcoes.sort((a, b) =>
                a.localeCompare(
                    b,
                    'pt-BR',
                    { sensitivity: 'base' }
                )
            );

            let html = '';

            opcoes.forEach((opcao, index) => {

                html += `
            <div class="d-flex justify-content-between align-items-center border-bottom py-1">

                <span>${opcao}</span>

                <div>

                    <input
                        type="hidden"
                        name="opcoes_lista[]"
                        value="${opcao}"
                    >

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        onclick="removerOpcao(${index})"
                    >
                        <i class="bi bi-trash"></i>
                    </button>

                </div>

            </div>
        `;
            });

            document.getElementById('listaOpcoes').innerHTML = html;
        }

        function adicionarOpcao() {

            const campo =
                document.getElementById('novaOpcao');

            const valor =
                campo.value.trim();

            if (!valor) {
                return;
            }

            const existe = opcoes.some(
                o => o.toLowerCase() === valor.toLowerCase()
            );

            if (existe) {

                alert('Esta opção já foi adicionada.');

                return;
            }

            opcoes.push(valor);

            campo.value = '';

            renderizarOpcoes();
        }

        function removerOpcao(index) {

            opcoes.splice(index, 1);

            renderizarOpcoes();
        }

        function atualizarValoresDependencia() {

    const idPai =
        document.querySelector('[name="dependente_de"]').value;

    const container =
        document.getElementById(
            'container_dependente_valores'
        );

    container.innerHTML = '';

    if (!idPai || !dependencias[idPai]) {

        container.innerHTML =
            '<small class="text-muted">Selecione primeiro o campo pai.</small>';

        return;
    }

    dependencias[idPai].opcoes.forEach(opcao => {

        const marcado =
            valoresSelecionados.includes(opcao)
                ? 'checked'
                : '';

        container.innerHTML += `
            <div class="form-check">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="dependente_valor[]"
                    value="${opcao}"
                    ${marcado}>

                <label class="form-check-label">

                    ${opcao}

                </label>

            </div>
        `;
    });
}

        document
            .getElementById('novaOpcao')
            .addEventListener('keypress', function (e) {

                if (e.key === 'Enter') {

                    e.preventDefault();

                    adicionarOpcao();
                }
            });

        renderizarOpcoes();

        tipoCampo.addEventListener(
            'change',
            toggleOpcoes
        );

        toggleOpcoes();

        document
        .querySelector('[name="dependente_de"]')
        .addEventListener(
            'change',
            atualizarValoresDependencia
        );

        atualizarValoresDependencia();

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>