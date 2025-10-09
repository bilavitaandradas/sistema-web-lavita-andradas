<?php
session_start();
require_once '../../php/config.php';

// Proteção: Garante que o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

// Valida o ID do pedido vindo da URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redireciona se não houver um ID válido
    header('Location: meus_pedidos.php');
    exit();
}
$id_pedido = (int)$_GET['id'];

// --- 1. BUSCA OS DADOS DO CABEÇALHO DO PEDIDO ---
$query_pedido = "
    SELECT 
        pc.*, 
        u_solic.nome as nome_solicitante,
        u_aprov.nome as nome_aprovador
    FROM pedidos_compra pc
    JOIN usuarios u_solic ON pc.id_solicitante = u_solic.id
    LEFT JOIN usuarios u_aprov ON pc.id_aprovador = u_aprov.id
    WHERE pc.id_pedido = ?
";
$stmt = $conn->prepare($query_pedido);
$stmt->bind_param('i', $id_pedido);
$stmt->execute();
$result_pedido = $stmt->get_result();
$pedido = $result_pedido->fetch_assoc();
$stmt->close();

// Se o pedido não for encontrado, redireciona
if (!$pedido) {
    header('Location: meus_pedidos.php');
    exit();
}

// --- 2. BUSCA OS ITENS DO PEDIDO ---
$query_itens = "
    SELECT 
        ip.quantidade_solicitada,
        d.nome,
        d.unidade_medida
    FROM itens_pedido ip
    JOIN defensivos d ON ip.id_defensivo = d.id_defensivo
    WHERE ip.id_pedido = ?
";
$stmt_itens = $conn->prepare($query_itens);
$stmt_itens->bind_param('i', $id_pedido);
$stmt_itens->execute();
$result_itens = $stmt_itens->get_result();
$itens = $result_itens->fetch_all(MYSQLI_ASSOC);
$stmt_itens->close();

// Função para a cor do badge
function get_status_badge_class($status) {
    switch ($status) {
        case 'Aprovado': return 'bg-success';
        case 'Reprovado': return 'bg-danger';
        case 'Entregue': return 'bg-info';
        case 'Pendente': default: return 'bg-warning';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?= $id_pedido ?></title>
    <link rel="icon" type="image/x-icon" href="../../img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-file-earmark-text me-2"></i>Detalhes do Pedido #<?= $id_pedido ?></h1>
                <a href="meus_pedidos.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Voltar para Meus Pedidos
                </a>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5>Informações Gerais</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Solicitante:</strong>
                            <p><?= htmlspecialchars($pedido['nome_solicitante']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Data da Solicitação:</strong>
                            <p><?= date('d/m/Y H:i', strtotime($pedido['data_solicitacao'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Status:</strong>
                            <p><span class="badge fs-6 <?= get_status_badge_class($pedido['status_aprovacao']) ?>">
                                <?= htmlspecialchars($pedido['status_aprovacao']) ?>
                            </span></p>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-4">
                            <strong>Aprovado por:</strong>
                            <p><?= htmlspecialchars($pedido['nome_aprovador'] ?? 'Aguardando aprovação') ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Data da Aprovação:</strong>
                            <p><?= $pedido['data_aprovacao'] ? date('d/m/Y H:i', strtotime($pedido['data_aprovacao'])) : '---' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5>Itens Solicitados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto (Defensivo)</th>
                                    <th class="text-end">Quantidade Solicitada</th>
                                    <th>Unidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['nome']) ?></td>
                                        <td class="text-end"><?= number_format($item['quantidade_solicitada'], 2, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($item['unidade_medida']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>