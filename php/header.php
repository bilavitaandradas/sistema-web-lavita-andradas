<?php
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}
?>
<header class="bg-secondary text-white p-2 position-fixed w-100 shadow" style="top: 0; left: 0; z-index: 1000;">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h4 mb-0">La Vita Andradas</h1>
            </div>
            <div class="col-md-6 text-end">
                <span class="me-3">Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</span>
                <a href="/TCC/php/logout.php" class="btn btn-outline-light btn-sm" aria-label="Sair do sistema">Sair</a>
            </div>
        </div>
    </div>
</header>   