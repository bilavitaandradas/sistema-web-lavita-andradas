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
$stmtQ = $conn->prepare("SELECT nome_questionario FROM questionarios WHERE id_questionario = ?");
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
    $opcoes = $tipo_campo === 'DROPDOWN' ? json_encode(array_map('trim', explode(',', $_POST['opcoes'])), JSON_UNESCAPED_UNICODE) : null;


    if ($nome_campo && $tipo_campo) {
        $stmt = $conn->prepare("INSERT INTO campos_questionario (id_questionario, nome_campo, tipo_campo, opcoes, criado_em) 
                                VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $id_questionario, $nome_campo, $tipo_campo, $opcoes);
        if ($stmt->execute()) {
            $mensagem = "<div class='alert alert-success'>Campo adicionado com sucesso.</div>";
        }
    }
}

// --- PROCESSAMENTO PARA EXCLUIR UM CAMPO ---
if (isset($_POST['excluir_campo'])) {
    $id_campo = intval($_POST['id_campo']);
    $stmt = $conn->prepare("DELETE FROM campos_questionario WHERE id_campo = ? AND id_questionario = ?");
    $stmt->bind_param("ii", $id_campo, $id_questionario);
    if ($stmt->execute()) {
        $mensagem = "<div class='alert alert-success'>Campo excluído com sucesso.</div>";
    }
}

// --- LISTA OS CAMPOS ATUAIS DO QUESTIONÁRIO ---
$stmtCampos = $conn->prepare("SELECT * FROM campos_questionario WHERE id_questionario = ?");
$stmtCampos->bind_param("i", $id_questionario);
$stmtCampos->execute();
$resultCampos = $stmtCampos->get_result();

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Campos - <?= htmlspecialchars($questionario['nome_questionario']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
</head>

<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-5" style="margin-left: 250px;">
        <h2>Editar Campos - <?= htmlspecialchars($questionario['nome_questionario']) ?></h2>

        <?= $mensagem ?>

        <h4>Campos Cadastrados</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Opções</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($campo = $resultCampos->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($campo['nome_campo']) ?></td>
                        <td><?= htmlspecialchars($campo['tipo_campo']) ?></td>
                        <td>
                            <?php
                            if (!empty($campo['opcoes'])) {
                                $opcoes_array = json_decode($campo['opcoes'], true);
                                echo htmlspecialchars(implode(', ', $opcoes_array));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>

                        <td>
                            <!-- Botão Editar redireciona para editar_campo.php -->
                            <a href="editar_campo.php?id_campo=<?= $campo['id_campo'] ?>&id_questionario=<?= $id_questionario ?>"
                                class="btn btn-warning btn-sm">
                                Editar
                            </a>

                            <!-- Botão Excluir -->
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
                <label>Nome do Campo</label>
                <input type="text" name="nome_campo" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Tipo do Campo</label>
                <select name="tipo_campo" class="form-select" required>
                    <option value="TEXT">Texto</option>
                    <option value="NUMBER">Número</option>
                    <option value="DATE">Data</option>
                    <option value="TIME">Hora</option>
                    <option value="DROPDOWN">Dropdown</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Opções (se for Dropdown, separado por vírgula)</label>
                <input type="text" name="opcoes" class="form-control">
            </div>

            <button type="submit" name="adicionar_campo" class="btn btn-success">Adicionar Campo</button>
            <a href="configuracoes.php" class="btn btn-secondary">Voltar</a>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>