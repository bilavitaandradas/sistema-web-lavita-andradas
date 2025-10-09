<?php
session_start();

// Verifica a sessão e permissão (código de segurança omitido para brevidade)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

include '../../php/config.php';

$defensivos = [];
if (isset($conn) && $conn) {
    $queryDefensivos = "SELECT id_defensivo, nome, unidade_medida FROM defensivos ORDER BY nome ASC";
    $result = $conn->query($queryDefensivos);
    if ($result) {
        $defensivos = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
}

// Pega a mensagem da sessão (enviada pelo processar_pedido.php)
$mensagem = $_SESSION['mensagem_pedido'] ?? '';
// Limpa a mensagem da sessão para que ela não apareça novamente ao recarregar a página
unset($_SESSION['mensagem_pedido']);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Pedido - Setor de Qualidade</title>
    <link rel="icon" type="image/x-icon" href="../../img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .removable-row {
            display: flex;
            align-items: center;
        }
    </style>
</head>

<body class="bg-light">
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Novo Pedido de Compra</h1>
                <a href="meus_pedidos.php" class="btn btn-info">
                    <i class="bi bi-list-check me-2"></i>Ver Meus Pedidos
                </a>
            </div>
            <?php if ($mensagem): ?>
                <div class="mb-3">
                    <?= $mensagem ?>
                </div>
            <?php endif; ?>

            <div class="card p-4 shadow-sm">
                <form action="processar_pedido.php" method="POST">
                    <div id="itens-container">
                    </div>
                    <button type="button" class="btn btn-secondary mt-3" id="adicionar-item"><i
                            class="bi bi-plus-circle"></i> Adicionar Outro Item</button>
                    <hr class="my-4">
                    <button type="submit" class="btn btn-success btn-lg d-block w-100"><i
                            class="bi bi-check-circle"></i> Enviar Pedido</button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const defensivosMap = {};
            <?php foreach ($defensivos as $d): ?>
                defensivosMap[<?php echo $d['id_defensivo']; ?>] = "<?php echo htmlspecialchars($d['unidade_medida'], ENT_QUOTES); ?>";
            <?php endforeach; ?>

            const defensivosList = <?php echo json_encode($defensivos); ?>;

            const itensContainer = document.getElementById('itens-container');
            const adicionarItemBtn = document.getElementById('adicionar-item');

            let itemId = 0;

            function atualizarUnidade(selectElement) {
                const itemRow = selectElement.closest('.removable-row');
                const unidadeSpan = itemRow.querySelector('.unidade-medida-span');
                const selectedId = selectElement.value;
                unidadeSpan.textContent = selectedId ? defensivosMap[selectedId] : '';
            }

            function adicionarItem() {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'row mb-3 removable-row';

                itemDiv.innerHTML = `
                    <div class="col-md-6">
                        <label class="form-label">Produto (Defensivo)</label>
                        <select name="defensivo_id[]" class="form-select" required>
                            <option value="">Selecione o Defensivo</option>
                            ${defensivosList.map(d => `<option value="${d.id_defensivo}">${d.nome}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Quantidade</label>
                        <div class="input-group">
                            <input type="number" name="quantidade[]" class="form-control" placeholder="Qtd." step="0.01" required>
                            <span class="input-group-text unidade-medida-span"></span>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remover-item" title="Remover Item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;

                itensContainer.appendChild(itemDiv);

                // Adiciona os eventos aos novos elementos
                itemDiv.querySelector('select').addEventListener('change', (e) => atualizarUnidade(e.target));
                itemDiv.querySelector('.remover-item').addEventListener('click', () => itemDiv.remove());
            }

            adicionarItemBtn.addEventListener('click', adicionarItem);

            // Adiciona o primeiro item automaticamente
            adicionarItem();
        });
    </script>
</body>

</html>