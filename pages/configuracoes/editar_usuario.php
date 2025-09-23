<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}
require_once '../../php/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}
$id_usuario_para_editar = (int)$_GET['id'];

$mensagem = "";
$roles = ['usuario', 'gerente', 'admin', 'qualidade', 'rh', 'manutencao', 'estoque', 'produção'];
$status_options = ['ATIVO', 'DESATIVADO'];
$sitios = $conn->query("SELECT id_sitio, nome_sitio FROM sitios ORDER BY nome_sitio")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $sitio_id = (int)$_POST['sitio'];
    $status = $_POST['status'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($nome) || empty($username) || empty($role) || empty($sitio_id) || empty($status)) {
        $mensagem = '<div class="alert alert-warning">Todos os campos com (*) são obrigatórios.</div>';
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $stmt_check->bind_param('si', $username, $id_usuario_para_editar);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $mensagem = '<div class="alert alert-danger">Este Username já está em uso por outro usuário.</div>';
        } elseif (!empty($password) && $password !== $password_confirm) {
            $mensagem = '<div class="alert alert-warning">As novas senhas não coincidem.</div>';
        } else {
            // --- LÓGICA DE ATUALIZAÇÃO CORRIGIDA ---
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                // Consulta UPDATE usando a coluna 'nome_sitio'
                $stmt_update = $conn->prepare("UPDATE usuarios SET nome = ?, username = ?, role = ?, nome_sitio = ?, status = ?, password = ? WHERE id = ?");
                // String de tipos corrigida: sssissi (7 parâmetros)
                $stmt_update->bind_param('sssissi', $nome, $username, $role, $sitio_id, $status, $password_hash, $id_usuario_para_editar);
            } else {
                // Consulta UPDATE usando a coluna 'nome_sitio'
                $stmt_update = $conn->prepare("UPDATE usuarios SET nome = ?, username = ?, role = ?, nome_sitio = ?, status = ? WHERE id = ?");
                // String de tipos corrigida: sssisi (6 parâmetros)
                $stmt_update->bind_param('sssisi', $nome, $username, $role, $sitio_id, $status, $id_usuario_para_editar);
            }

            if ($stmt_update->execute()) {
                $mensagem = '<div class="alert alert-success">Usuário atualizado com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao atualizar o usuário.</div>';
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

// Busca os dados atuais do usuário para preencher o formulário, usando a coluna 'nome_sitio'
$stmt_user = $conn->prepare("SELECT nome, username, role, nome_sitio, status FROM usuarios WHERE id = ?");
$stmt_user->bind_param('i', $id_usuario_para_editar);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$usuario = $result_user->fetch_assoc();

if (!$usuario) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - La Vita Andradas</title>
    <link rel="icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">
            <h1><i class="bi bi-pencil-square me-2"></i>Editar Usuário</h1>
            <hr>
            <?= $mensagem ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="editar_usuario.php?id=<?= $id_usuario_para_editar ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="nome" class="form-label">Nome Completo *</label><input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required></div>
                            <div class="col-md-6 mb-3"><label for="username" class="form-label">Username (para login) *</label><input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="role" class="form-label">Cargo (Role) *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <?php foreach ($roles as $role_option): ?>
                                        <option value="<?= $role_option ?>" <?= $usuario['role'] === $role_option ? 'selected' : '' ?>><?= ucfirst($role_option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="sitio" class="form-label">Sítio *</label>
                                <select class="form-select" id="sitio" name="sitio" required>
                                    <?php foreach ($sitios as $sitio): ?>
                                        <option value="<?= $sitio['id_sitio'] ?>" <?= $usuario['nome_sitio'] == $sitio['id_sitio'] ? 'selected' : '' ?>><?= htmlspecialchars($sitio['nome_sitio']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <?php foreach ($status_options as $option): ?>
                                        <option value="<?= $option ?>" <?= $usuario['status'] === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <hr>
                        <h5 class="mt-4 mb-3">Alterar Senha (Opcional)</h5>
                        <p class="text-muted small">Preencha os campos abaixo somente se desejar alterar a senha do usuário.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="password" class="form-label">Nova Senha</label><input type="password" class="form-control" id="password" name="password"></div>
                            <div class="col-md-6 mb-3"><label for="password_confirm" class="form-label">Confirmar Nova Senha</label><input type="password" class="form-control" id="password_confirm" name="password_confirm"></div>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Salvar Alterações</button>
                        <a href="index.php" class="btn btn-secondary">Voltar</a>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>