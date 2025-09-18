<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header(header: 'Location: ../../index.php');
    exit();
}

// Verificar se o usuário tem permissão para acessar o setor de RH
if (!isset($_SESSION['role']) || !in_array(needle: $_SESSION['role'], haystack: ['admin', 'gerente', 'rh'])) {
    header(header: 'Location: ../../inicio.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RH - La Vita Andradas</title>
    <link rel="icon" type="image/x-icon" href="../../img/favicon.ico">
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <!-- Header -->
    <?php include '../../php/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../../php/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" style="margin-left: 250px; margin-top: 70px; padding: 20px;">
        <div class="content-wrapper">
            <h2 class="mb-4">Setor de Recursos Humanos</h2>
            <div class="row">
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Gerenciamento de Colaboradores</h5>
                            <p class="card-text">Adicione, edite ou remova colaboradores da empresa.</p>
                            <a href="sincronizar_colaboradores.php" class="btn btn-primary">Acessar</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Relatórios de RH</h5>
                            <p class="card-text">Visualize relatórios de desempenho e folha de pagamento.</p>
                            <a href="relatorios.php" class="btn btn-primary">Acessar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>