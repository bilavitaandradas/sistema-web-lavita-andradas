<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once 'php/config.php';

// Redireciona se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

// PROCESSAR UM NOVO RECADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_recado'])) {
    $mensagem_recado = trim($_POST['mensagem_recado']);
    if (!empty($mensagem_recado)) {
        $stmt_recado = $conn->prepare("INSERT INTO recados (mensagem, id_usuario) VALUES (?, ?)");
        $stmt_recado->bind_param('si', $mensagem_recado, $_SESSION['usuario_id']);
        $stmt_recado->execute();
        $stmt_recado->close();
        header('Location: inicio.php');
        exit();
    }
}

// EXCLUIR UM RECADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_recado'])) {
    $id_recado_para_excluir = (int) $_POST['id_recado'];
    $id_usuario_logado = (int) $_SESSION['usuario_id'];

    // Pega o ID do autor do recado para verificar a permissão
    $stmt_check = $conn->prepare("SELECT id_usuario FROM recados WHERE id_recado = ?");
    $stmt_check->bind_param('i', $id_recado_para_excluir);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($recado_info = $result_check->fetch_assoc()) {
        // exclusão se o usuário logado for o autor OU for um admin
        if ($recado_info['id_usuario'] === $id_usuario_logado || $_SESSION['role'] === 'admin') {
            $stmt_delete = $conn->prepare("DELETE FROM recados WHERE id_recado = ?");
            $stmt_delete->bind_param('i', $id_recado_para_excluir);
            $stmt_delete->execute();
            $stmt_delete->close();
        }
    }
    $stmt_check->close();
    header('Location: inicio.php');
    exit();
}


// --- LÓGICA PARA BUSCAR OS DADOS DO DASHBOARD ---
$recados = $conn->query("SELECT r.id_recado, r.mensagem, r.criado_em, u.nome as nome_autor, r.id_usuario FROM recados r JOIN usuarios u ON r.id_usuario = u.id ORDER BY r.criado_em DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
// (O resto da sua lógica para aniversariantes vai ser adicionada aqui)
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início - La Vita Andradas</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
            padding-top: 95px;
        }

        .card .blockquote {
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <?php include 'php/header.php'; ?>
    <?php include 'php/sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Painel de Controle</h1>
                <span class="text-muted"><?= date('l, d \d\e F \d\e Y') ?></span>
            </div>

            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-pin-angle-fill me-2"></i>Mural de
                                Recados</h6>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                data-bs-target="#modalNovoRecado">
                                <i class="bi bi-plus-lg"></i> Adicionar Recado
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recados)): ?>
                                <p class="text-muted">Nenhum recado no mural ainda.</p>
                            <?php else: ?>
                                <?php foreach ($recados as $recado): ?>
                                    <div class="d-flex justify-content-between">
                                        <figure class="mb-3 flex-grow-1">
                                            <blockquote class="blockquote">
                                                <p><?= htmlspecialchars($recado['mensagem']) ?></p>
                                            </blockquote>
                                            <figcaption class="blockquote-footer mb-0">
                                                Postado por <cite><?= htmlspecialchars($recado['nome_autor']) ?></cite>
                                                em <?= date('d/m/Y \à\s H:i', strtotime($recado['criado_em'])) ?>
                                            </figcaption>
                                        </figure>
                                        <?php if ($_SESSION['usuario_id'] === $recado['id_usuario'] || $_SESSION['role'] === 'admin'): ?>
                                            <form method="POST" action="inicio.php"
                                                onsubmit="return confirm('Tem certeza que deseja excluir este recado?');">
                                                <input type="hidden" name="id_recado" value="<?= $recado['id_recado'] ?>">
                                                <button type="submit" name="excluir_recado" class="btn btn-link text-danger p-0"
                                                    title="Excluir Recado">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-people-fill me-2">

                            </i>Funcionários
                            </h6>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <p class="display-4 fw-bold text-secondary"></p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-person-plus-fill me-2"></i>Novos na
                                Empresa</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">

                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-cake2-fill me-2"></i>Aniversário do
                                Dia</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">

                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-shield-lock-fill me-2"></i>Membros da CIPA</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Nome 1</li>
                                <li class="list-group-item">Nome 2</li>
                                <li class="list-group-item">Nome 3</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalNovoRecado" tabindex="-1" aria-labelledby="modalNovoRecadoLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovoRecadoLabel">Adicionar Novo Recado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="inicio.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="mensagem_recado" class="form-label">Sua mensagem:</label>
                            <textarea class="form-control" id="mensagem_recado" name="mensagem_recado" rows="4"
                                required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="novo_recado" class="btn btn-primary">Postar Recado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>