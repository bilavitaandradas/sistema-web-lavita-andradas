<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'gerente'])) {
    header('Location: /TCC/index.php');
    exit();
}

require_once '../../php/config.php';

// Verifica se o ID do campo foi passado
if (!isset($_GET['id_campo']) || !is_numeric($_GET['id_campo'])) {
    die("ID do campo inválido.");
}

$id_campo = (int)$_GET['id_campo'];
$id_questionario = isset($_GET['id_questionario']) ? intval($_GET['id_questionario']) : 0;

// Recupera os dados do campo
$stmt = $conn->prepare("SELECT * FROM campos_questionario WHERE id_campo = ?");
$stmt->bind_param("i", $id_campo);
$stmt->execute();
$result = $stmt->get_result();
$campo = $result->fetch_assoc();

if (!$campo) {
    die("Campo não encontrado.");
}

$mensagem = "";

// Se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_campo = trim($_POST['nome_campo']);
    $tipo_campo = $_POST['tipo_campo'];
    $opcoes = $tipo_campo === 'DROPDOWN' ? json_encode(array_map('trim', explode(',', $_POST['opcoes']))) : null;

    $stmt = $conn->prepare("UPDATE campos_questionario 
                            SET nome_campo = ?, tipo_campo = ?, opcoes = ?, atualizado_em = NOW() 
                            WHERE id_campo = ?");
    $stmt->bind_param("sssi", $nome_campo, $tipo_campo, $opcoes, $id_campo);

    if ($stmt->execute()) {
        $mensagem = "<div class='alert alert-success'>Campo atualizado com sucesso.</div>";

        // Atualiza os dados para exibir na tela novamente
        $campo['nome_campo'] = $nome_campo;
        $campo['tipo_campo'] = $tipo_campo;
        $campo['opcoes'] = $opcoes;
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao atualizar o campo.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Campo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<?php include '../../php/header.php'; ?>
<?php include '../../php/sidebar.php'; ?>

<main class="main-content p-5" style="margin-left: 250px;">
    <h2>Editar Campo</h2>

    <?= $mensagem ?>

    <form method="post">
        <div class="mb-3">
            <label>Nome do Campo</label>
            <input type="text" name="nome_campo" class="form-control" 
                   value="<?= htmlspecialchars($campo['nome_campo']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Tipo do Campo</label>
            <select name="tipo_campo" class="form-select" required>
                <option value="TEXT" <?= $campo['tipo_campo'] == 'TEXT' ? 'selected' : '' ?>>Texto</option>
                <option value="NUMBER" <?= $campo['tipo_campo'] == 'NUMBER' ? 'selected' : '' ?>>Número</option>
                <option value="DATE" <?= $campo['tipo_campo'] == 'DATE' ? 'selected' : '' ?>>Data</option>
                <option value="TIME" <?= $campo['tipo_campo'] == 'TIME' ? 'selected' : '' ?>>Hora</option>
                <option value="DROPDOWN" <?= $campo['tipo_campo'] == 'DROPDOWN' ? 'selected' : '' ?>>Dropdown</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Opções (se for Dropdown, separado por vírgula)</label>
            <input type="text" name="opcoes" class="form-control"
                   value="<?= $campo['tipo_campo'] == 'DROPDOWN' ? htmlspecialchars(json_decode($campo['opcoes'], true) ? implode(',', json_decode($campo['opcoes'], true)) : '') : '' ?>">
        </div>

        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="editar_campos.php?id_questionario=<?= $campo['id_questionario'] ?>" class="btn btn-secondary">Voltar</a>
    </form>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
