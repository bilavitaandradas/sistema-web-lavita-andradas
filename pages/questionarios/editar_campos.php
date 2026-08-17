<?php
session_start();

// --- VERIFICA PERMISSÕES ---
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'qualidade', 'gerente'])) {
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



    $opcoes = null;

    // DROPDOWN e CHECKBOX usam opções
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

    if ($nome_campo && $tipo_campo) {

        $stmt = $conn->prepare("
            INSERT INTO campos_questionario (
                id_questionario,
                nome_campo,
                tipo_campo,
                opcoes,
                criado_em
            )
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "isss",
            $id_questionario,
            $nome_campo,
            $tipo_campo,
            $opcoes
        );

        if ($stmt->execute()) {

            $idCampoFilho = $stmt->insert_id;

            if (!empty($_POST['dependencias'])) {

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
                        empty($dependencia['valores'])
                    ) {
                        continue;
                    }

                    $idCampoPai = intval(
                        $dependencia['campo_pai']
                    );

                    foreach ($dependencia['valores'] as $valor) {

                        $valor = trim($valor);

                        if ($valor === '') {
                            continue;
                        }

                        $stmtDep->bind_param(
                            "iis",
                            $idCampoFilho,
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

    GROUP_CONCAT(
        DISTINCT cp.nome_campo
        ORDER BY cp.nome_campo
        SEPARATOR ', '
    ) AS nome_dependencia,

    GROUP_CONCAT(
        DISTINCT dc.valor
        ORDER BY dc.valor
        SEPARATOR ', '
    ) AS valores_dependencia

FROM campos_questionario c

LEFT JOIN dependencias_campos dc
    ON dc.id_campo_filho = c.id_campo

LEFT JOIN campos_questionario cp
    ON cp.id_campo = dc.id_campo_pai

WHERE c.id_questionario = ?

GROUP BY c.id_campo

ORDER BY c.id_campo ASC
");

$stmtCampos->bind_param("i", $id_questionario);
$stmtCampos->execute();

$resultCampos = $stmtCampos->get_result();

// --- BUSCA CAMPOS PARA DEPENDÊNCIA ---
$stmtDependencias = $conn->prepare("
    SELECT id_campo, nome_campo, opcoes
    FROM campos_questionario
    WHERE id_questionario = ?
    AND tipo_campo = 'DROPDOWN'
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

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

                            <?php if (!empty($campo['nome_dependencia'])): ?>

                                <strong>Campo:</strong>
                                <?= htmlspecialchars($campo['nome_dependencia']) ?>

                                <br>

                                <strong>Valores:</strong>
                                <?= htmlspecialchars($campo['valores_dependencia']) ?>

                            <?php else: ?>

                                -

                            <?php endif; ?>

                        </td>

                        <td>

                            <a href="editar_campo.php?id_campo=<?= $campo['id_campo'] ?>&id_questionario=<?= $id_questionario ?>"
                                class="btn btn-warning btn-sm">
                                Editar
                            </a>

                            <form method="post" class="d-inline">

                                <input type="hidden" name="id_campo" value="<?= $campo['id_campo'] ?>">

                                <button name="excluir_campo" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Deseja excluir este campo?')">
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

                <input type="text" name="nome_campo" class="form-control" required>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Tipo do Campo
                </label>

                <select name="tipo_campo" id="tipo_campo" class="form-select" required>

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

            <p class="text-muted">
                Um campo pode depender de vários campos.
                Para cada campo pai, selecione os valores que permitem a exibição deste campo.
            </p>

            <div id="listaDependencias">

                <!--
        Os blocos de dependência serão criados
        dinamicamente pelo JavaScript.
    -->

            </div>

            <button type="button" id="btnAdicionarDependencia" class="btn btn-outline-primary mb-3">

                <i class="bi bi-plus-circle"></i>
                Adicionar Dependência

            </button>

            <hr>

            <button type="submit" name="adicionar_campo" class="btn btn-success">

                Adicionar Campo

            </button>

            <a href="configuracoes.php" class="btn btn-secondary">

                Voltar

            </a>
        </form>

    </main>

    <?php

    $dependenciasJS = [];

    $camposDependencia->data_seek(0);

    while ($dep = $camposDependencia->fetch_assoc()) {

        $dependenciasJS[$dep['id_campo']] = [
            'nome' => $dep['nome_campo'],
            'opcoes' => json_decode($dep['opcoes'], true) ?? []
        ];
    }

    ?>

    <script>

        const dependencias = <?= json_encode(
            $dependenciasJS,
            JSON_UNESCAPED_UNICODE
        ); ?>;
        console.log(dependencias);

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
        let opcoes = [];

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

        document
            .getElementById('novaOpcao')
            .addEventListener('keypress', function (e) {

                if (e.key === 'Enter') {

                    e.preventDefault();

                    adicionarOpcao();
                }
            });

        function criarBlocoDependencia() {

            const container =
                document.getElementById('listaDependencias');

            const bloco =
                document.createElement('div');

            const indice =
                document.querySelectorAll('.dependencia-item').length;
            bloco.dataset.indice = indice;

            bloco.className =
                'card p-3 mb-3 dependencia-item';

            bloco.innerHTML = `
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

                ${Object.entries(dependencias)
                    .map(([id, campo]) => `
                        <option value="${id}">
                            ${campo.nome}
                        </option>
                    `)
                    .join('')}

            </select>

        </div>

        <div
            class="dependencia-valores border rounded p-2 mb-3"
            style="min-height:80px;">

            <small class="text-muted">
                Selecione primeiro o campo pai.
            </small>

        </div>

        <button
            type="button"
            class="btn btn-danger btn-sm remover-dependencia">

            <i class="bi bi-trash"></i>
            Remover

        </button>
    `;

            container.appendChild(bloco);

            // Pega o select deste bloco recém-criado
            const selectPai =
                bloco.querySelector('.dependencia-pai');

            // Quando o campo pai mudar,
            // atualiza os valores disponíveis
            selectPai.addEventListener(
                'change',
                function () {

                    atualizarValoresDependencia(
                        bloco,
                        this.value
                    );

                }
            );
        }

        function atualizarValoresDependencia(bloco, idPai) {

            const containerValores =
                bloco.querySelector('.dependencia-valores');

            containerValores.innerHTML = '';

            if (!idPai || !dependencias[idPai]) {

                containerValores.innerHTML = `
            <small class="text-muted">
                Selecione primeiro o campo pai.
            </small>
        `;

                return;
            }

            const opcoes =
                dependencias[idPai].opcoes || [];

            if (!opcoes.length) {

                containerValores.innerHTML = `
            <small class="text-muted">
                Este campo não possui opções cadastradas.
            </small>
        `;

                return;
            }
            const indice =
                bloco.dataset.indice;
            opcoes.forEach((opcao, index) => {

                const idCheckbox =
                    `dependencia_${Date.now()}_${index}`;

                containerValores.innerHTML += `
            <div class="form-check">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="dependencias[${indice}][valores][]"
                    value="${opcao}"
                    id="${idCheckbox}">

                <label
                    class="form-check-label"
                    for="${idCheckbox}">

                    ${opcao}

                </label>

            </div>
        `;

            });
        }

        tipoCampo.addEventListener(
            'change',
            toggleOpcoes
        );

        document
            .getElementById('btnAdicionarDependencia')
            .addEventListener(
                'click',
                criarBlocoDependencia
            );

        document.addEventListener('click', function (e) {

            if (
                !e.target.classList.contains(
                    'remover-dependencia'
                )
            ) {
                return;
            }

            e.target
                .closest('.dependencia-item')
                .remove();
        });

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>