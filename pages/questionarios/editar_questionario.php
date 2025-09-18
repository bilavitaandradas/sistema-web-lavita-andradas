<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'gerente'])) {
    header('Location: /TCC/index.php');
    exit();
}

require_once '../../php/config.php';

if (!isset($_GET['id_questionario']) || !is_numeric($_GET['id_questionario'])) {
    die('ID do questionário inválido.');
}
$id_questionario = (int) $_GET['id_questionario'];
$mensagem = "";

// --- Carrega Sítios E Usuários ---
$sitios = $conn->query("SELECT id_sitio, nome_sitio FROM sitios ORDER BY nome_sitio")->fetch_all(MYSQLI_ASSOC);
$todos_usuarios = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

// --- Carrega as permissões atuais ---
$usuarios_permitidos_ids = [];
$stmtPerm = $conn->prepare("SELECT id_usuario FROM questionario_permissoes WHERE id_questionario = ?");
$stmtPerm->bind_param("i", $id_questionario);
$stmtPerm->execute();
$resultPerm = $stmtPerm->get_result();
while ($row = $resultPerm->fetch_assoc()) {
    $usuarios_permitidos_ids[] = $row['id_usuario'];
}
$stmtPerm->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_nome = trim($_POST['nome_questionario'] ?? '');
    $nova_desc = trim($_POST['descricao_questionario'] ?? '');
    $id_sitio = intval($_POST['id_sitio'] ?? 0); // <-- VOLTOU
    $novos_usuarios_permitidos = $_POST['usuarios_permitidos'] ?? [];

    if (empty($novo_nome) || $id_sitio === 0) {
        $mensagem = "<div class='alert alert-warning'>Nome e Sítio são obrigatórios.</div>";
    } elseif (empty($novos_usuarios_permitidos)) {
        $mensagem = "<div class='alert alert-warning'>Selecione pelo menos um usuário.</div>";
    } else {
        $conn->begin_transaction();
        try {
            // 1. Atualiza o questionário, AGORA COM O id_sitio
            $stmtUpdate = $conn->prepare("UPDATE questionarios SET nome_questionario = ?, descricao_questionario = ?, id_sitio = ?, atualizado_em = NOW() WHERE id_questionario = ?");
            $stmtUpdate->bind_param("ssii", $novo_nome, $nova_desc, $id_sitio, $id_questionario); // <-- VOLTOU
            $stmtUpdate->execute();

            // 2. Apaga permissões antigas
            $stmtDelete = $conn->prepare("DELETE FROM questionario_permissoes WHERE id_questionario = ?");
            $stmtDelete->bind_param('i', $id_questionario);
            $stmtDelete->execute();

            // 3. Insere novas permissões
            $stmtInsert = $conn->prepare("INSERT INTO questionario_permissoes (id_questionario, id_usuario) VALUES (?, ?)");
            foreach ($novos_usuarios_permitidos as $id_usuario) {
                $stmtInsert->bind_param('ii', $id_questionario, $id_usuario);
                $stmtInsert->execute();
            }

            $conn->commit();
            $mensagem = "<div class='alert alert-success'>Questionário atualizado com sucesso.</div>";
            $usuarios_permitidos_ids = $novos_usuarios_permitidos;

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $mensagem = "<div class='alert alert-danger'>Erro ao atualizar questionário.</div>";
        }
    }
}

// Consulta dados atuais para preencher o formulário
$stmt = $conn->prepare("SELECT nome_questionario, descricao_questionario, id_sitio FROM questionarios WHERE id_questionario = ?");
$stmt->bind_param("i", $id_questionario);
$stmt->execute();
$result = $stmt->get_result();
$questionario = $result->fetch_assoc();

if (!$questionario) {
    die("Questionário não encontrado.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Questionário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-5" style="margin-left: 250px;">
        <h1 class="mb-4">Editar Questionário</h1>
        <?= $mensagem ?>

        <form method="POST">
            <div class="mb-3">
                <label for="nome_questionario" class="form-label">Nome do Questionário *</label>
                <input type="text" class="form-control" name="nome_questionario" id="nome_questionario" required value="<?= htmlspecialchars($questionario['nome_questionario']) ?>">
            </div>
            <div class="mb-3">
                <label for="descricao_questionario" class="form-label">Descrição</label>
                <textarea class="form-control" name="descricao_questionario" id="descricao_questionario"><?= htmlspecialchars($questionario['descricao_questionario']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="id_sitio" class="form-label">Sítio de Referência *</label>
                <select class="form-select" name="id_sitio" id="id_sitio" required>
                    <option value="">Selecione um sítio</option>
                    <?php foreach ($sitios as $sitio): ?>
                        <option value="<?= $sitio['id_sitio'] ?>" <?= $sitio['id_sitio'] == $questionario['id_sitio'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sitio['nome_sitio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Usuários com Permissão de Acesso *</label>
                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($todos_usuarios as $usuario): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="usuarios_permitidos[]" value="<?= $usuario['id'] ?>" id="user_<?= $usuario['id'] ?>"
                                   <?= in_array($usuario['id'], $usuarios_permitidos_ids) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="user_<?= $usuario['id'] ?>">
                                <?= htmlspecialchars($usuario['nome']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="configuracoes.php" class="btn btn-secondary">Voltar</a>
        </form>
    </main>
</body>
</html>