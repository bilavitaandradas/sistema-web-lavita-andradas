<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}
require_once '../../php/config.php';

$mensagem = "";
// Busca a lista de Sítios do banco de dados para o dropdown
$sitios = $conn->query("SELECT id_sitio, nome_sitio FROM sitios ORDER BY nome_sitio")->fetch_all(MYSQLI_ASSOC);
$roles = ['gerente', 'admin', 'qualidade', 'rh', 'manutencao', 'estoque','fiscal','compras','produção',];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $role = $_POST['role'];
    $sitio_id = (int) $_POST['sitio']; // Captura o ID do sítio

    // Validações
    if (empty($nome) || empty($username) || empty($password) || empty($role) || empty($sitio_id)) {
        $mensagem = '<div class="alert alert-warning">Por favor, preencha todos os campos obrigatórios (*).</div>';
    } elseif ($password !== $password_confirm) {
        $mensagem = '<div class="alert alert-warning">As senhas não coincidem.</div>';
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt_check->bind_param('s', $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $mensagem = '<div class="alert alert-danger">Este nome de usuário (username) já está em uso.</div>';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Query de inserção agora inclui o 'sitio'
            $stmt_insert = $conn->prepare("INSERT INTO usuarios (nome, username, password, role, nome_sitio) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param('ssssi', $nome, $username, $password_hash, $role, $sitio_id);

            if ($stmt_insert->execute()) {
                $mensagem = '<div class="alert alert-success">Usuário cadastrado com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao cadastrar o usuário.</div>';
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Novo Usuário - La Vita Andradas</title>
    <link rel="icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">
            <h1><i class="bi bi-person-plus-fill me-2"></i>Adicionar Novo Usuário</h1>
            <hr>
            <?= $mensagem ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="adicionar_usuario.php">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="nome" class="form-label">Nome Completo
                                    *</label><input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            <div class="col-md-6 mb-3"><label for="username" class="form-label">Username (para login)
                                    *</label><input type="text" class="form-control" id="username" name="username"
                                    required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="password" class="form-label">Senha *</label><input
                                    type="password" class="form-control" id="password" name="password" required></div>
                            <div class="col-md-6 mb-3"><label for="password_confirm" class="form-label">Confirmar Senha
                                    *</label><input type="password" class="form-control" id="password_confirm"
                                    name="password_confirm" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Cargo (Role) *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($roles as $role_option): ?>
                                        <option value="<?= $role_option ?>"><?= ucfirst($role_option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sitio" class="form-label">Sítio *</label>
                                <select class="form-select" id="sitio" name="sitio" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($sitios as $sitio): ?>
                                        <option value="<?= $sitio['id_sitio'] ?>">
                                            <?= htmlspecialchars($sitio['nome_sitio']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-2"></i>Salvar
                            Usuário</button>
                        <a href="index.php" class="btn btn-secondary">Voltar</a>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>