<?php
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}
?>

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
</head>
<header class="bg-success text-white p-2 position-fixed w-100 border-bottom border-light border-2 border-opacity-75"
    style="top: 0; left: 0; z-index: 1000;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">

            <div>
                <h1 class="h4 mb-0 fw-normal">La Vita Andradas</h1>
            </div>

            <div class="d-flex align-items-center">
                <span class="me-3">
                    <i class="bi bi-person-circle me-2"></i>
                    Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?>!
                </span>

                <a href="/TCC/php/logout.php" class="btn btn-outline-light btn-sm" aria-label="Sair do sistema">Sair</a>
            </div>

        </div>
    </div>
</header>