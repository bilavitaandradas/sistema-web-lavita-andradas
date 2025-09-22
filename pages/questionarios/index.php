<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

// Verificar se o usuário tem permissão para acessar a página
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
    <title>Painel de Questionários</title>
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Estilo para dar um efeito de 'levantar' no card ao passar o mouse */
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
    </style>
</head>

<body>

    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">
            
            <div class="p-5 mb-4 bg-light rounded-3">
                <div class="container-fluid py-3">
                    <h1 class="display-5 fw-bold">Painel de Questionários</h1>
                    <p class="col-md-8 fs-4">Gerencie, visualize, realize e exporte os lançamentos dos questionários do sistema.</p>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-md-6">
                    <a href="/TCC/pages/questionarios/lancamentos.php" class="text-decoration-none">
                        <div class="card h-100 text-center card-hover">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-pencil-square text-primary" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Realizar Lançamento</h5>
                                <p class="card-text text-muted">Preencha e envie um novo formulário de questionário.</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="/TCC/pages/questionarios/verificar.php" class="text-decoration-none">
                        <div class="card h-100 text-center card-hover">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-table text-info" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Verificar Lançamentos</h5>
                                <p class="card-text text-muted">Visualize, edite e exclua os lançamentos recentes.</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="/TCC/pages/questionarios/exportar.php" class="text-decoration-none">
                        <div class="card h-100 text-center card-hover">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-file-earmark-excel-fill text-success" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Exportar Dados</h5>
                                <p class="card-text text-muted">Gere relatórios em planilhas do Excel com filtros de data.</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="/TCC/pages/questionarios/configuracoes.php" class="text-decoration-none">
                        <div class="card h-100 text-center card-hover">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-sliders text-secondary" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Configurar Questionários</h5>
                                <p class="card-text text-muted">Crie novos questionários e edite os formulários existentes.</p>
                            </div>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>