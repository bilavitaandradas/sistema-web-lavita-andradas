<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'gerente'])) {
    header('Location: /TCC/index.php');
    exit();
}

require_once '../../php/config.php';

$mensagem = "";

// --- Recupera Sítios E Usuários ---
$sitios = $conn->query("SELECT id_sitio, nome_sitio FROM sitios ORDER BY nome_sitio")->fetch_all(MYSQLI_ASSOC);
$usuarios = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome_questionario'] ?? '');
    $descricao = trim($_POST['descricao_questionario'] ?? '');
    $id_sitio = intval($_POST['id_sitio'] ?? 0); // <-- VOLTOU
    $usuarios_permitidos = $_POST['usuarios_permitidos'] ?? [];

    if ($nome === '' || $descricao === '' || $id_sitio === 0) {
        $mensagem = '<div class="alert alert-warning">Nome, descrição e sítio são obrigatórios.</div>';
    } elseif (empty($usuarios_permitidos)) {
        $mensagem = '<div class="alert alert-warning">Selecione pelo menos um usuário.</div>';
    } else {
        $conn->begin_transaction();
        try {
            // 1. Insere o questionário, AGORA COM O id_sitio
            $stmt = $conn->prepare("INSERT INTO questionarios (nome_questionario, descricao_questionario, id_sitio) VALUES (?, ?, ?)");
            $stmt->bind_param('ssi', $nome, $descricao, $id_sitio); // <-- VOLTOU
            $stmt->execute();

            $id_questionario_criado = $conn->insert_id;

            // 2. Insere as permissões (lógica continua a mesma)
            $stmt_perm = $conn->prepare("INSERT INTO questionario_permissoes (id_questionario, id_usuario) VALUES (?, ?)");
            foreach ($usuarios_permitidos as $id_usuario) {
                $stmt_perm->bind_param('ii', $id_questionario_criado, $id_usuario);
                $stmt_perm->execute();
            }
            
            $conn->commit();
            $mensagem = '<div class="alert alert-success">Questionário criado com sucesso!</div>';

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $mensagem = '<div class="alert alert-danger">Erro ao criar o questionário.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar Novo Questionário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
</head>
<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <div class="main-content p-5" style="margin-left: 250px;">
        <h2>Criar Novo Questionário</h2>
        <?= $mensagem ?>

        <form method="post">
            <div class="mb-3">
                <label for="nome_questionario" class="form-label">Nome do Questionário</label>
                <input type="text" name="nome_questionario" id="nome_questionario" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="descricao_questionario" class="form-label">Descrição</label>
                <textarea name="descricao_questionario" id="descricao_questionario" class="form-control" required></textarea>
            </div>

            <div class="mb-3">
                <label for="id_sitio" class="form-label">Sítio de Referência</label>
                <select name="id_sitio" id="id_sitio" class="form-select" required>
                    <option value="">Selecione um sítio...</option>
                    <?php foreach ($sitios as $sitio): ?>
                        <option value="<?= $sitio['id_sitio'] ?>"><?= htmlspecialchars($sitio['nome_sitio']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Usuários com Permissão de Acesso</label>
                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($usuarios as $usuario): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="usuarios_permitidos[]" value="<?= $usuario['id'] ?>" id="user_<?= $usuario['id'] ?>">
                            <label class="form-check-label" for="user_<?= $usuario['id'] ?>">
                                <?= htmlspecialchars($usuario['nome']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Criar Questionário</button>
            <a href="configuracoes.php" class="btn btn-secondary">Voltar</a>
        </form>
    </div>
</body>
</html>