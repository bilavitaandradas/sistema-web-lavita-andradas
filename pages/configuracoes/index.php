<?php
session_start();

// Garante que apenas usuários com a role 'admin' possam acessar esta página.
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}

require_once '../../php/config.php';

// --- DADOS DE EXEMPLO PARA O DESIGN ---
$usuarios_exemplo = [
    ['id' => 1, 'nome' => 'Eduardo Almeida Campos Silva', 'username' => 'eduardo.silva', 'role' => 'admin', 'nome_sitio' => 'Sede'],
    ['id' => 2, 'nome' => 'Maria Oliveira', 'username' => 'maria.o', 'role' => 'gerente', 'nome_sitio' => 'Fazenda Santa Rita'],
    ['id' => 3, 'nome' => 'José Pereira', 'username' => 'jose.p', 'role' => 'usuario', 'nome_sitio' => 'Fazenda Água Limpa'],
];
$sitios_exemplo = [
    ['id_sitio' => 1, 'nome_sitio' => 'Sede'],
    ['id_sitio' => 2, 'nome_sitio' => 'Fazenda Santa Rita'],
    ['id_sitio' => 3, 'nome_sitio' => 'Fazenda Água Limpa'],
];
$total_usuarios = 3;
$total_questionarios = 4;
$total_sitios = 3;
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração do Sistema</title>
    <link rel="icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Administração do Sistema</h1>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title"><?= $total_usuarios ?></h5>
                                <p class="card-text">Usuários Cadastrados</p>
                            </div>
                            <i class="bi bi-people-fill" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title"><?= $total_questionarios ?></h5>
                                <p class="card-text">Questionários Ativos</p>
                            </div>
                            <i class="bi bi-file-earmark-text-fill" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title"><?= $total_sitios ?></h5>
                                <p class="card-text">Sítios Gerenciados</p>
                            </div>
                            <i class="bi bi-pin-map-fill" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-people-fill me-2"></i>Gerenciamento de Usuários</h5>
                    <a href="adicionar_usuario.php" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Adicionar Novo Usuário
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome Completo</th>
                                    <th>Username (Login)</th>
                                    <th>Cargo (Role)</th>
                                    <th>Sítio</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios_exemplo as $usuario): ?>
                                    <tr>
                                        <td><?= $usuario['id'] ?></td>
                                        <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                        <td><?= htmlspecialchars($usuario['username']) ?></td>
                                        <td><span
                                                class="badge bg-secondary"><?= htmlspecialchars($usuario['role']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($usuario['nome_sitio']) ?></td>
                                        <td class="text-center">
                                            <a href="editar_usuario.php?id=<?= $usuario['id'] ?>"
                                                class="btn btn-sm btn-warning me-2" title="Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="excluir_usuario.php?id=<?= $usuario['id'] ?>"
                                                class="btn btn-sm btn-danger" title="Excluir"
                                                onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>