<aside class="bg-light border-end vh-100 position-fixed shadow" style="width: 250px; top: 0; left: 0;">
    <div class="p-3">
        <img src="/TCC/img/logo.png" alt="Logo da Empresa" class="d-block mx-auto mb-4 pt-4"
            style="width: 100px; margin-top: 20px;">
        <h4 class="text-center mb-4">Menu Inicial</h4>
        <ul class="list-group list-group-flush">
            <!-- Página de início -->
            <li class="list-group-item">
                <a href="/TCC/inicio.php" class="text-decoration-none text-dark">Início</a>
            </li>

            <!-- Página Questionários -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente'])) { ?>
                <li class="list-group-item">
                    <a href="/TCC/pages/questionarios/index.php" class="text-decoration-none text-dark">Questionários</a>
                </li>
            <?php } ?>

            <!-- Página Indicadores -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'qualidade', 'rh', 'manutencao', 'estoque' ])) { ?>
                <li class="list-group-item">
                    <a href="/TCC/pages/indicadores/index.php" class="text-decoration-none text-dark">Indicadores</a>
                </li>
            <?php } ?>

            <!-- Página Qualidade -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'qualidade'])) { ?>
                <li class="list-group-item">
                    <a href="/TCC/pages/qualidade/index.php" class="text-decoration-none text-dark">Qualidade</a>
                </li>
            <?php } ?>

            <!-- Página RH -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'rh'])) { ?>
                <li class="list-group-item">
                    <a href="/TCC/pages/rh/index.php" class="text-decoration-none text-dark">RH</a>
                </li>
            <?php } ?>

            <!-- Página Estoque -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'estoque'])) { ?>
                <li class="list-group-item">
                    <a href="/TCC/pages/estoque/index.php" class="text-decoration-none text-dark">Estoque</a>
                </li>
            <?php } ?>

            <!-- Página Manutenção -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'manutencao'])) { ?>
                <li class="list-group-item">
                    <a href="/TCC/pages/manutencao/index.php" class="text-decoration-none text-dark">Manutenção</a>
                </li>
            <?php } ?>

            <!-- Documentações -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gerente', 'qualidade', 'rh', 'manutencao', 'estoque'])) { ?>
                <li class="list-group-item">
                    <a href="/TCC/pages/documentacoes/index.php" class="text-decoration-none text-dark">Documentações</a>
                </li>
            <?php } ?>

            <!-- Configurações (somente admin) -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                <li class="list-group-item">
                    <a href="/TCC/pages/configuracoes/index.php" class="text-decoration-none text-dark">Configurações</a>
                </li>
            <?php } ?>

        </ul>
    </div>
</aside>