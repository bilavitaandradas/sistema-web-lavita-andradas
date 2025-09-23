<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

require_once '../../php/config.php';

// Obtem o role do usuário logado (LÓGICA MANTIDA)
$userRole = $_SESSION['role'];

// Busca os indicadores permitidos conforme o role (LÓGICA MANTIDA)
$stmt = $conn->prepare("SELECT i.id_indicador, i.nome_indicador, i.descricao, i.link_indicador
    FROM indicadores i
    JOIN indicador_roles r ON i.id_indicador = r.id_indicador
    WHERE r.role = ?");
$stmt->bind_param('s', $userRole);
$stmt->execute();
$result = $stmt->get_result();

$indicadores = [];
while ($row = $result->fetch_assoc()) {
    $indicadores[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Indicadores - La Vita Andradas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <style>
        /* Estilo para o iframe preencher todo o corpo do modal */
        .modal-body-fullscreen {
            padding: 0;
            overflow: hidden;
        }
        .modal-body-fullscreen iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>

    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main style="margin-left: 250px; padding: 20px; padding-top: 95px;">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Galeria de Indicadores</h1>
                <?php if ($userRole === 'admin'): ?>
                    <a href="gerenciar.php" class="btn btn-success"><i class="bi bi-gear-fill me-2"></i>Gerenciar Indicadores</a>
                <?php endif; ?>
            </div>

            <?php if (empty($indicadores)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <?php if ($userRole === 'admin'): ?>
                        Nenhum indicador cadastrado ainda. Clique em <strong>Gerenciar Indicadores</strong> para adicionar.
                    <?php else: ?>
                        Nenhum indicador disponível para sua permissão.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($indicadores as $indicador): ?>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-2 text-success"><?= htmlspecialchars($indicador['nome_indicador']) ?></h5>
                                    <p class="card-text text-muted flex-grow-1"><?= htmlspecialchars($indicador['descricao']) ?></p>
                                    <button type="button" class="btn btn-outline-success mt-auto" data-bs-toggle="modal" data-bs-target="#indicatorModal" data-link="<?= htmlspecialchars($indicador['link_indicador']) ?>" data-title="<?= htmlspecialchars($indicador['nome_indicador']) ?>">
                                        <i class="bi bi-bar-chart-line-fill me-2"></i>Visualizar Indicador
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
        </div>
    </main>
    <div class="modal fade" id="indicatorModal" tabindex="-1" aria-labelledby="indicatorModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="indicatorModalLabel">Carregando Indicador...</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body modal-body-fullscreen">
            <iframe id="indicatorIframe" src="" allowfullscreen></iframe>
          </div>
        </div>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- SCRIPT PARA CONTROLAR O MODAL DE INDICADORES ---

        // 1. Pega a referência do elemento Modal no HTML
        const indicatorModal = document.getElementById('indicatorModal');

        // 2. Adiciona um "escutador" de eventos. Ele será acionado TODA VEZ que o modal for ABERTO.
        indicatorModal.addEventListener('show.bs.modal', function (event) {
          
          // a. Identifica o botão que foi clicado para abrir o modal
          const button = event.relatedTarget;
          
          // b. Extrai as informações dos atributos 'data-*' do botão
          const link = button.getAttribute('data-link');
          const title = button.getAttribute('data-title');
          
          // c. Encontra o título do modal e o iframe dentro dele
          const modalTitle = indicatorModal.querySelector('.modal-title');
          const iframe = indicatorModal.querySelector('#indicatorIframe');
          
          // d. Atualiza o título do modal e o link do iframe com as informações do botão
          modalTitle.textContent = title;
          iframe.src = link;
        });

        // 3. Adiciona outro "escutador" que será acionado TODA VEZ que o modal for FECHADO.
        indicatorModal.addEventListener('hide.bs.modal', function (event) {
            
            // a. Encontra o iframe
            const iframe = indicatorModal.querySelector('#indicatorIframe');
            
            // b. Limpa o 'src' do iframe. Isso é MUITO IMPORTANTE para parar a execução do indicador em segundo plano.
            iframe.src = '';
        });
    </script>
</body>
</html>