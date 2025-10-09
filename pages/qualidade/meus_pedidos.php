<?php
session_start();
require_once '../../php/config.php';

// Proteção: Garante que o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];

// --- BUSCA OS PEDIDOS DO USUÁRIO LOGADO ---
// A consulta busca os pedidos e também o nome do aprovador (se houver)
$query_pedidos = "
    SELECT 
        pc.id_pedido, 
        pc.data_solicitacao, 
        pc.status_aprovacao, 
        pc.data_aprovacao,
        u_aprov.nome as nome_aprovador
    FROM pedidos_compra pc
    LEFT JOIN usuarios u_aprov ON pc.id_aprovador = u_aprov.id
    WHERE pc.id_solicitante = ?
    ORDER BY pc.data_solicitacao DESC
";

$stmt = $conn->prepare($query_pedidos);
$stmt->bind_param('i', $id_usuario_logado);
$stmt->execute();
$result = $stmt->get_result();
$pedidos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Função para definir a cor do "badge" de status
function get_status_badge_class($status) {
    switch ($status) {
        case 'Aprovado':
            return 'bg-success';
        case 'Reprovado':
            return 'bg-danger';
        case 'Entregue':
            return 'bg-info';
        case 'Pendente':
        default:
            return 'bg-warning';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos de Compra</title>
    <link rel="icon" type="image/x-icon" href="../../img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-list-check me-2"></i>Meus Pedidos de Compra</h1>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Fazer Novo Pedido
                </a>
            </div>  

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nº Pedido</th>
                                    <th>Data da Solicitação</th>
                                    <th class="text-center">Status</th>
                                    <th>Aprovado por</th>
                                    <th>Data da Aprovação</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pedidos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Você ainda não fez nenhum pedido.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pedidos as $pedido): ?>
                                        <tr>
                                            <td>#<?= $pedido['id_pedido'] ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($pedido['data_solicitacao'])) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= get_status_badge_class($pedido['status_aprovacao']) ?>">
                                                    <?= htmlspecialchars($pedido['status_aprovacao']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($pedido['nome_aprovador'] ?? '---') ?></td>
                                            <td><?= $pedido['data_aprovacao'] ? date('d/m/Y H:i', strtotime($pedido['data_aprovacao'])) : '---' ?></td>
                                            <td class="text-center">
                                                <a href="detalhes_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye-fill"></i> Detalhes
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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