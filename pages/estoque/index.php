<?php
session_start();

// Verificar se o usuário está logadoThe requested URL was not found on this server.
if (!isset($_SESSION['usuario_id'])) {
    header('TCC/index.php');
    exit();
}

// Verificar se o usuário tem permissão para acessar Estoque
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'gerente', 'estoque'])) {
    header('Location: ../../inicio.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - La Vita Andradas</title>
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
            <h2 class="mb-4">Setor de Estoque</h2>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>
</html>