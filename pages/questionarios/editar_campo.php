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


// Busca todas as dependências já cadastradas para este campo
$stmtDeps = $conn->prepare("
    SELECT
        id_campo_pai,
        valor
    FROM dependencias_campos
    WHERE id_campo_filho = ?
");

$stmtDeps->bind_param("i", $id_campo);
$stmtDeps->execute();

$resultDeps = $stmtDeps->get_result();

$dependenciasSelecionadas = [];

while ($dep = $resultDeps->fetch_assoc()) {

    $idPai = (int) $dep['id_campo_pai'];

    if (!isset($dependenciasSelecionadas[$idPai])) {
        $dependenciasSelecionadas[$idPai] = [];
    }

    $dependenciasSelecionadas[$idPai][] = $dep['valor'];
}

$stmtDeps->close();

// Se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome_campo = trim($_POST['nome_campo']);

    $tipo_campo = $_POST['tipo_campo'];

    $opcoes = null;

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
            atualizado_em = NOW()
        WHERE id_campo = ?
    ");

    $stmt->bind_param(
        "sssi",
        $nome_campo,
        $tipo_campo,
        $opcoes,
        $id_campo
    );

    if ($stmt->execute()) {

        // Remove todas as dependências antigas
        $stmtDel = $conn->prepare("
        DELETE FROM dependencias_campos
        WHERE id_campo_filho = ?
    ");

        $stmtDel->bind_param(
            "i",
            $id_campo
        );

        $stmtDel->execute();
        $stmtDel->close();


        // Salva as novas dependências
        if (!empty($_POST['dependencias']) && is_array($_POST['dependencias'])) {

            $stmtDep = $conn->prepare("
            INSERT INTO dependencias_campos
            (
                id_campo_filho,
                id_campo_pai,
                valor
            )
            VALUES (?, ?, ?)
        ");

            foreach ($_POST['dependencias'] as $dependencia) {

                if (
                    empty($dependencia['campo_pai']) ||
                    empty($dependencia['valores']) ||
                    !is_array($dependencia['valores'])
                ) {
                    continue;
                }

                $idCampoPai = (int) $dependencia['campo_pai'];

                foreach ($dependencia['valores'] as $valor) {

                    $valor = trim($valor);

                    if ($valor === '') {
                        continue;
                    }

                    $stmtDep->bind_param(
                        "iis",
                        $id_campo,
                        $idCampoPai,
                        $valor
                    );

                    $stmtDep->execute();
                }
            }

            $stmtDep->close();
        }


        $mensagem = "
        <div class='alert alert-success'>
            Campo atualizado com sucesso.
        </div>
    ";

        // Atualiza os dados para exibir na tela novamente
        $campo['nome_campo'] = $nome_campo;
        $campo['tipo_campo'] = $tipo_campo;
        $campo['opcoes'] = $opcoes;

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

            <h5>Dependências (Opcional)</h5>

            <div id="listaDependencias">

            </div>

            <div class="mt-3">

                <button type="button" class="btn btn-outline-primary" id="btnAdicionarDependencia">

                    <i class="bi bi-plus-circle"></i>
                    Adicionar Dependência

                </button>

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

        // =============================
        // DADOS VINDOS DO PHP
        // =============================

        const dependencias =
            <?= json_encode(
                $dependenciasJS,
                JSON_UNESCAPED_UNICODE
            ); ?>;

        const dependenciasSelecionadas =
            <?= json_encode(
                $dependenciasSelecionadas,
                JSON_UNESCAPED_UNICODE
            ); ?>;


        // =============================
        // ELEMENTOS DA PÁGINA
        // =============================

        const tipoCampo =
            document.getElementById('tipo_campo');

        const containerOpcoes =
            document.getElementById('container_opcoes');


        // =============================
        // CONTROLE DOS ÍNDICES
        // =============================

        let indiceDependencia = 0;


        // =============================
        // CONTROLE DAS OPÇÕES DO CAMPO
        // =============================

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


        // =============================
        // CRIA BLOCO DE DEPENDÊNCIA
        // =============================

        function criarBlocoDependencia(
            idPaiSelecionado = '',
            valoresSelecionados = []
        ) {

            const container =
                document.getElementById('listaDependencias');


            // Cada bloco recebe um índice próprio
            const indice =
                indiceDependencia++;


            const bloco =
                document.createElement('div');

            bloco.className =
                'card p-3 mb-3 dependencia-item';

            bloco.dataset.indice =
                indice;


            let html = '';


            // =============================
            // CAMPO PAI
            // =============================

            html += `
            <div class="mb-3">

                <label class="form-label">
                    Campo Pai
                </label>

                <select
                    class="form-select dependencia-pai"
                    name="dependencias[${indice}][campo_pai]"
                    required>

                    <option value="">
                        Selecione...
                    </option>
        `;


            for (const id in dependencias) {

                const selecionado =
                    String(id) === String(idPaiSelecionado)
                        ? 'selected'
                        : '';


                html += `
                <option
                    value="${id}"
                    ${selecionado}>
                    ${dependencias[id].nome}
                </option>
            `;
            }


            html += `
                </select>

            </div>


            <div
                class="dependencia-valores border rounded p-2 mb-3"
                style="min-height: 80px;">

                <small class="text-muted">
                    Selecione um campo pai.
                </small>

            </div>


            <button
                type="button"
                class="btn btn-danger btn-sm remover-dependencia">

                <i class="bi bi-trash"></i>
                Remover

            </button>
        `;


            bloco.innerHTML =
                html;


            container.appendChild(
                bloco
            );


            // =============================
            // EVENTO DO CAMPO PAI
            // =============================

            const selectPai =
                bloco.querySelector(
                    '.dependencia-pai'
                );


            selectPai.addEventListener(
                'change',
                function () {

                    atualizarValoresDependencia(
                        bloco,
                        this.value
                    );

                }
            );


            // =============================
            // CARREGA VALORES EXISTENTES
            // =============================

            if (idPaiSelecionado) {

                atualizarValoresDependencia(
                    bloco,
                    idPaiSelecionado,
                    valoresSelecionados
                );
            }
        }


        // =============================
        // ATUALIZA VALORES DO CAMPO PAI
        // =============================

        function atualizarValoresDependencia(
            bloco,
            idPai,
            valoresSelecionados = []
        ) {

            const containerValores =
                bloco.querySelector(
                    '.dependencia-valores'
                );


            const indice =
                bloco.dataset.indice;


            containerValores.innerHTML =
                '';


            // =============================
            // VALIDA CAMPO PAI
            // =============================

            if (
                !idPai ||
                !dependencias[idPai]
            ) {

                containerValores.innerHTML = `
                <small class="text-muted">
                    Selecione um campo pai.
                </small>
            `;

                return;
            }


            // =============================
            // BUSCA OPÇÕES DO CAMPO PAI
            // =============================

            const opcoesPai =
                dependencias[idPai].opcoes || [];


            if (!opcoesPai.length) {

                containerValores.innerHTML = `
                <small class="text-muted">
                    Este campo não possui opções cadastradas.
                </small>
            `;

                return;
            }


            // =============================
            // CRIA CHECKBOXES
            // =============================

            opcoesPai.forEach(
                (opcao, index) => {

                    const idCheckbox =
                        `dependencia_${indice}_${index}`;


                    const marcado =
                        valoresSelecionados
                            .map(String)
                            .includes(String(opcao))
                            ? 'checked'
                            : '';


                    containerValores.innerHTML += `
                    <div class="form-check">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="dependencias[${indice}][valores][]"
                            value="${opcao}"
                            id="${idCheckbox}"
                            ${marcado}>

                        <label
                            class="form-check-label"
                            for="${idCheckbox}">

                            ${opcao}

                        </label>

                    </div>
                `;
                }
            );
        }


        // =============================
        // RENDERIZA OPÇÕES DO CAMPO
        // =============================

        function renderizarOpcoes() {

            opcoes.sort(
                (a, b) =>
                    a.localeCompare(
                        b,
                        'pt-BR',
                        {
                            sensitivity: 'base'
                        }
                    )
            );


            let html = '';


            opcoes.forEach(
                (opcao, index) => {

                    html += `
                    <div
                        class="d-flex justify-content-between align-items-center border-bottom py-1">

                        <span>
                            ${opcao}
                        </span>

                        <div>

                            <input
                                type="hidden"
                                name="opcoes_lista[]"
                                value="${opcao}">

                            <button
                                type="button"
                                class="btn btn-sm btn-danger"
                                onclick="removerOpcao(${index})">

                                <i class="bi bi-trash"></i>

                            </button>

                        </div>

                    </div>
                `;
                }
            );


            document
                .getElementById('listaOpcoes')
                .innerHTML = html;
        }


        // =============================
        // ADICIONAR OPÇÃO
        // =============================

        function adicionarOpcao() {

            const campo =
                document.getElementById(
                    'novaOpcao'
                );


            const valor =
                campo.value.trim();


            if (!valor) {
                return;
            }


            const existe =
                opcoes.some(
                    o =>
                        o.toLowerCase() ===
                        valor.toLowerCase()
                );


            if (existe) {

                alert(
                    'Esta opção já foi adicionada.'
                );

                return;
            }


            opcoes.push(
                valor
            );


            campo.value =
                '';


            renderizarOpcoes();
        }


        // =============================
        // REMOVER OPÇÃO
        // =============================

        function removerOpcao(index) {

            opcoes.splice(
                index,
                1
            );


            renderizarOpcoes();
        }


        // =============================
        // ENTER NO CAMPO DE OPÇÃO
        // =============================

        document
            .getElementById('novaOpcao')
            .addEventListener(
                'keypress',
                function (e) {

                    if (e.key === 'Enter') {

                        e.preventDefault();

                        adicionarOpcao();
                    }
                }
            );


        // =============================
        // INICIALIZA OPÇÕES
        // =============================

        renderizarOpcoes();


        // =============================
        // ALTERAÇÃO DO TIPO DO CAMPO
        // =============================

        tipoCampo.addEventListener(
            'change',
            toggleOpcoes
        );


        toggleOpcoes();


        // =============================
        // BOTÃO ADICIONAR DEPENDÊNCIA
        // =============================

        document
            .getElementById(
                'btnAdicionarDependencia'
            )
            .addEventListener(
                'click',
                function () {

                    criarBlocoDependencia();

                }
            );


        // =============================
        // CARREGA DEPENDÊNCIAS EXISTENTES
        // =============================

        for (
            const idPai in dependenciasSelecionadas
        ) {

            criarBlocoDependencia(
                idPai,
                dependenciasSelecionadas[idPai]
            );
        }


        // =============================
        // REMOVER BLOCO DE DEPENDÊNCIA
        // =============================

        document.addEventListener(
            'click',
            function (e) {

                const botao =
                    e.target.closest(
                        '.remover-dependencia'
                    );


                if (!botao) {
                    return;
                }


                const bloco =
                    botao.closest(
                        '.dependencia-item'
                    );


                if (bloco) {
                    bloco.remove();
                }

            }
        );

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>