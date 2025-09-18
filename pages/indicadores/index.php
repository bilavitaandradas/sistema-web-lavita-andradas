<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

require_once '../../php/config.php';

// Obtem o role do usuário logado
$userRole = $_SESSION['role'];

// Busca os indicadores permitidos conforme o role
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
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <style>
        body {
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 250px;  /* Espaço do sidebar */
            padding: 40px;
        }

        #indicadorContainer {
            margin-top: 20px;
            height: calc(100vh - 230px);
            /* Altura total da tela menos header e paddings */
            display: none;
        }

        #indicadorContainer iframe {
            width: 100%;
            height: 800px;
            /* altura maior fixa */
            border: none;
        }
    </style>
</head>

<body>

    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <div class="main-content">
        <h1 class="mb-4">Indicadores</h1>

        <?php if ($userRole === 'admin'): ?>
            <a href="gerenciar.php" class="btn btn-primary mb-4">Gerenciar Indicadores</a>
        <?php endif; ?>

        <?php if (empty($indicadores)): ?>
            <div class="alert alert-info">
                <?php if ($userRole === 'admin'): ?>
                    Nenhum indicador cadastrado ainda. Clique em <strong>Gerenciar Indicadores</strong> para adicionar.
                <?php else: ?>
                    Nenhum indicador disponível para sua permissão.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="mb-3">
                <label for="indicadorSelect" class="form-label">Selecione um Indicador</label>
                <select id="indicadorSelect" class="form-select">
                    <option value="" selected>-- Escolha um indicador --</option>
                    <?php foreach ($indicadores as $indicador): ?>
                        <option value="<?= htmlspecialchars($indicador['link_indicador']) ?>"
                            data-descricao="<?= htmlspecialchars($indicador['descricao']) ?>">
                            <?= htmlspecialchars($indicador['nome_indicador']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="indicadorContainer" class="card">
                <div class="card-body p-2">
                    <h5 id="indicadorDescricao" class="mb-3"></h5>
                    <iframe id="indicadorIframe"></iframe>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Exibir o indicador selecionado no iframe
        document.getElementById('indicadorSelect').addEventListener('change', function () {
            const link = this.value;
            const descricao = this.selectedOptions[0].getAttribute('data-descricao');
            const container = document.getElementById('indicadorContainer');
            const iframe = document.getElementById('indicadorIframe');
            const descricaoElem = document.getElementById('indicadorDescricao');

            if (link) {
                iframe.src = link;
                descricaoElem.textContent = descricao;
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
                iframe.src = '';
                descricaoElem.textContent = '';
            }
        });
    </script>
</body>

</html>