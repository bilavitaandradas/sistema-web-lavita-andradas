<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

// Verificar se o usuário tem permissão para acessar Indicadores
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'gerente'])) {
    header('Location: /TCC/inicio.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores Gerais - La Vita Andradas</title>
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body class="pt-5"> <!-- padding-top para evitar sobreposição com header fixo -->

    <!-- Header -->
    <?php include '../../php/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../../php/sidebar.php'; ?>

    <div class="container mt-3" style="max-width: 960px;">
        <h1 class="text-center fs-3 fw-bold mb-3">Manipulação de Questionários e Dados</h1>
        <p class="text-center">Escolha uma das opções abaixo para continuar:</p>

        <div class="d-flex flex-column gap-3 mx-auto" style="max-width: 400px;">
            <a href="/TCC/pages/questionarios/lancamentos.php" class="btn btn-primary btn-lg">
                Realizar Lançamento
            </a>

            <a href="/TCC/pages/questionarios/verificar.php" class="btn btn-primary btn-lg ">
                Verificar Lançamentos
            </a>

            <a href="/TCC/pages/questionarios/configuracoes.php" class="btn btn-primary btn-lg">
                Configurações de Questionários
            </a>

            <a href="/TCC/pages/questionarios/exportar.php" class="btn btn-primary btn-lg">
                Exportar Dados
            </a>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>