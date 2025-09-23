<?php
// --- LÓGICA PARA MENU ATIVO (sem alterações aqui) ---
$active_section = basename(dirname($_SERVER['SCRIPT_NAME']));
$active_page = basename($_SERVER['SCRIPT_NAME']);
?>

<aside class="bg-light border-end vh-100 position-fixed shadow-sm d-flex flex-column" style="width: 250px; top: 0; left: 0; z-index: 999;">

    <div class="flex-shrink-0" style="height: 75px;"></div>

    <div class="overflow-auto p-3">

        <div class="text-center mb-4">
            <a href="/TCC/inicio.php">
                <img src="/TCC/img/logo.png" alt="Logo da Empresa" class="img-fluid" style="max-width: 100px;">
            </a>
        </div>

        <h5 class="text-dark text-uppercase fw-semibold mb-3 text-center mb-4">Menu Inicial</h5>
        
        <ul class="nav nav-pills flex-column">
            
            <li class="nav-item mb-1">
                <a href="/TCC/inicio.php" class="nav-link <?php echo ($active_page == 'inicio.php') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-house-door-fill me-2"></i>Início
                </a>
            </li>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente'])) { ?>
            <li class="nav-item mb-1">
                <a href="/TCC/pages/questionarios/index.php" class="nav-link <?php echo ($active_section == 'questionarios') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-patch-question-fill me-2"></i>Questionários
                </a>
            </li>
            <?php } ?>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'qualidade', 'rh', 'manutencao', 'estoque' ])) { ?>
            <li class="nav-item mb-1">
                <a href="/TCC/pages/indicadores/index.php" class="nav-link <?php echo ($active_section == 'indicadores') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-bar-chart-line-fill me-2"></i>Indicadores
                </a>
            </li>
            <?php } ?>
            
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'qualidade'])) { ?>
            <li class="nav-item mb-1">
                <a href="/TCC/pages/qualidade/index.php" class="nav-link <?php echo ($active_section == 'qualidade') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-check-circle-fill me-2"></i>Qualidade
                </a>
            </li>
            <?php } ?>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'rh'])) { ?>
            <li class="nav-item mb-1">
                <a href="/TCC/pages/rh/index.php" class="nav-link <?php echo ($active_section == 'rh') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-people-fill me-2"></i>RH
                </a>
            </li>
            <?php } ?>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'estoque'])) { ?>
            <li class="nav-item mb-1">
                <a href="/TCC/pages/estoque/index.php" class="nav-link <?php echo ($active_section == 'estoque') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-box-seam-fill me-2"></i>Estoque
                </a>
            </li>
            <?php } ?>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'manutencao'])) { ?>
            <li class="nav-item mb-1">
                <a href="/TCC/pages/manutencao/index.php" class="nav-link <?php echo ($active_section == 'manutencao') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-tools me-2"></i>Manutenção
                </a>
            </li>
            <?php } ?>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'qualidade', 'rh', 'manutencao', 'estoque'])) { ?>
            <li class="nav-item mb-1">
                <a href="/TCC/pages/documentacoes/index.php" class="nav-link <?php echo ($active_section == 'documentacoes') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-file-earmark-text-fill me-2"></i>Documentações
                </a>
            </li>
            <?php } ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
            <li class="nav-item mb-1">
                <a href="/TCC/pages/configuracoes/index.php" class="nav-link <?php echo ($active_section == 'configuracoes') ? 'active' : 'text-dark'; ?>">
                    <i class="bi bi-gear-fill me-2"></i>Configurações
                </a>
            </li>
            <?php } ?>

        </ul>
    </div>
</aside>