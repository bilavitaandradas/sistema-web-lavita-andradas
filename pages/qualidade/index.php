<?php
session_start();

// Verifica a sessão e permissão (código de segurança omitido para brevidade, mas deve estar no seu arquivo)

// Inclui o arquivo de conexão com o banco de dados
include '../../php/config.php';

$defensivos = [];

if (isset($conn) && $conn) {
    // Buscamos a unidade_medida junto com o nome e o ID
    $queryDefensivos = "SELECT id_defensivo, nome, unidade_medida FROM defensivos ORDER BY nome ASC";
    
    $result = $conn->query($queryDefensivos);

    if ($result) {
        $defensivos = $result->fetch_all(MYSQLI_ASSOC);
        $result->free(); 
    }
}

$mensagem = $_SESSION['mensagem_pedido'] ?? '';
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

<body>
    <?php include '../../php/header.php'; ?>

    <?php include '../../php/sidebar.php'; ?>

    <div class="main-content" style="margin-left: 250px; margin-top: 70px; padding: 20px;">
        <div class="content-wrapper">
            <h2 class="mb-4">Novo Pedido de Compra</h2>
            <div class="card p-4">
                <form action="processar_pedido.php" method="POST">
                    <div id="itens-container">
                        </div>
                    <button type="button" class="btn btn-secondary mt-3" id="adicionar-item"><i class="bi bi-plus-circle"></i> Adicionar Outro Item</button>

                    <button type="submit" class="btn btn-success mt-4 d-block w-100"><i class="bi bi-check-circle"></i> Enviar Pedido</button>
                </form>
            </div>
        </div>
        <?= $mensagem ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Transformamos a lista em um objeto Map para fácil acesso pela ID
            const defensivosMap = {};
            <?php foreach ($defensivos as $d): ?>
                defensivosMap[<?php echo $d['id_defensivo']; ?>] = "<?php echo $d['unidade_medida']; ?>";
            <?php endforeach; ?>
            
            // A lista completa para preencher o SELECT
            const defensivosList = <?php echo json_encode($defensivos); ?>;
            
            const itensContainer = document.getElementById('itens-container');
            const adicionarItemBtn = document.getElementById('adicionar-item');

            let itemId = 0;

            function atualizarUnidade(selectElement) {
                const itemId = selectElement.closest('.removable-row').dataset.itemId;
                const unidadeSpan = document.getElementById(`unidade-medida-${itemId}`);
                const selectedId = selectElement.value;
                
                // Exibe a unidade de medida com base no defensivo selecionado
                unidadeSpan.textContent = selectedId ? defensivosMap[selectedId] : '';
            }

            function adicionarItem() {
                const currentId = itemId++;
                const itemDiv = document.createElement('div');
                itemDiv.className = 'row mb-3 removable-row';
                itemDiv.dataset.itemId = currentId;

                // 1. Coluna de Seleção do Defensivo (Maior)
                const selectColumn = document.createElement('div');
                selectColumn.className = 'col-md-6';
                const selectElement = document.createElement('select');
                selectElement.name = 'defensivo_id[]';
                selectElement.className = 'form-select';
                selectElement.required = true;
                selectElement.innerHTML = '<option value="">Selecione o Defensivo</option>' + defensivosList.map(d => `<option value="${d.id_defensivo}">${d.nome}</option>`).join('');
                
                // Adiciona o evento para atualizar a unidade ao mudar a seleção
                selectElement.addEventListener('change', (e) => atualizarUnidade(e.target));
                selectColumn.appendChild(selectElement);

                // 2. Coluna de Quantidade e Unidade de Medida (Dividida em 4 e 2)
                const quantidadeGroup = document.createElement('div');
                quantidadeGroup.className = 'col-md-5 d-flex align-items-center';

                // Input de Quantidade (4 colunas)
                const quantidadeInput = document.createElement('input');
                quantidadeInput.type = 'number';
                quantidadeInput.name = 'quantidade[]';
                quantidadeInput.className = 'form-control';
                quantidadeInput.placeholder = 'Qtd.';
                quantidadeInput.step = '0.01';
                quantidadeInput.required = true;
                
                // Span para a Unidade de Medida (2 colunas)
                const unidadeSpan = document.createElement('span');
                unidadeSpan.id = `unidade-medida-${currentId}`;
                unidadeSpan.className = 'input-group-text ms-2';
                unidadeSpan.textContent = ''; // Começa vazio
                
                quantidadeGroup.appendChild(quantidadeInput);
                quantidadeGroup.appendChild(unidadeSpan);


                // 3. Coluna do Botão de Remover (Menor)
                const removerColumn = document.createElement('div');
                removerColumn.className = 'col-md-1 d-flex justify-content-center align-items-center';
                const removerBtn = document.createElement('button');
                removerBtn.type = 'button';
                removerBtn.className = 'btn btn-danger btn-sm';
                removerBtn.innerHTML = '<i class="bi bi-trash"></i>';
                removerBtn.addEventListener('click', function() {
                    itemDiv.remove();
                });
                removerColumn.appendChild(removerBtn);

                itemDiv.appendChild(selectColumn);
                itemDiv.appendChild(quantidadeGroup); // Adiciona a coluna de quantidade + unidade
                itemDiv.appendChild(removerColumn);

                itensContainer.appendChild(itemDiv);
            }

            adicionarItemBtn.addEventListener('click', adicionarItem);

            // Adiciona o primeiro item automaticamente ao carregar a página
            adicionarItem();
        });
    </script>

</body>

</html>