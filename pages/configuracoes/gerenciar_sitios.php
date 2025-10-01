<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}
require_once '../../php/config.php';

$mensagem = "";
$sitio_para_editar = ['id_sitio' => '', 'nome_sitio' => '']; // Para preencher o formulário de edição

// --- LÓGICA DE AÇÕES (POST para Criar/Atualizar, GET para Editar/Excluir) ---

// Processa a criação ou atualização de um sítio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_sitio = trim($_POST['nome_sitio']);
    $id_sitio = (int)($_POST['id_sitio'] ?? 0);

    if (empty($nome_sitio)) {
        $mensagem = '<div class="alert alert-warning">O nome do sítio não pode ser vazio.</div>';
    } else {
        if ($id_sitio > 0) { // Lógica de ATUALIZAÇÃO
            $stmt = $conn->prepare("UPDATE sitios SET nome_sitio = ? WHERE id_sitio = ?");
            $stmt->bind_param('si', $nome_sitio, $id_sitio);
            if ($stmt->execute()) {
                $mensagem = '<div class="alert alert-success">Sítio atualizado com sucesso!</div>';
            }
        } else { // Lógica de CRIAÇÃO
            $stmt = $conn->prepare("INSERT INTO sitios (nome_sitio) VALUES (?)");
            $stmt->bind_param('s', $nome_sitio);
            if ($stmt->execute()) {
                $mensagem = '<div class="alert alert-success">Sítio adicionado com sucesso!</div>';
            }
        }
        $stmt->close();
    }
}

// Processa a ação de Editar (para preencher o formulário) ou Excluir
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = (int)($_GET['id'] ?? 0);

    if ($action === 'edit' && $id > 0) {
        $stmt = $conn->prepare("SELECT id_sitio, nome_sitio FROM sitios WHERE id_sitio = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $sitio_para_editar = $result->fetch_assoc();
        }
        $stmt->close();
    }

    if ($action === 'delete' && $id > 0) {
        // VERIFICAÇÃO DE SEGURANÇA: Checa se o sítio está em uso por algum usuário ou questionário
        $stmt_check_user = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE sitio = ?");
        $stmt_check_user->bind_param('i', $id);
        $stmt_check_user->execute();
        $total_users = $stmt_check_user->get_result()->fetch_assoc()['total'];
        $stmt_check_user->close();

        $stmt_check_quest = $conn->prepare("SELECT COUNT(*) as total FROM questionarios WHERE id_sitio = ?");
        $stmt_check_quest->bind_param('i', $id);
        $stmt_check_quest->execute();
        $total_quests = $stmt_check_quest->get_result()->fetch_assoc()['total'];
        $stmt_check_quest->close();

        if ($total_users > 0 || $total_quests > 0) {
            $mensagem = '<div class="alert alert-danger">Não é possível excluir este sítio, pois ele está vinculado a usuários ou questionários.</div>';
        } else {
            // Se não estiver em uso, pode excluir
            $stmt = $conn->prepare("DELETE FROM sitios WHERE id_sitio = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $mensagem = '<div class="alert alert-success">Sítio excluído com sucesso!</div>';
            }
            $stmt->close();
        }
    }
}

// Busca todos os sítios para exibir na lista
$lista_de_sitios = $conn->query("SELECT * FROM sitios ORDER BY id_sitio ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Sítios</title>
    <link rel="icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-pin-map-fill me-2"></i>Gerenciar Sítios</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Voltar para Administração
                </a>
            </div>
            
            <?= $mensagem ?>

            <div class="row">
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5><?= !empty($sitio_para_editar['id_sitio']) ? 'Editar Sítio' : 'Adicionar Novo Sítio' ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="gerenciar_sitios.php">
                                <input type="hidden" name="id_sitio" value="<?= $sitio_para_editar['id_sitio'] ?>">
                                <div class="mb-3">
                                    <label for="nome_sitio" class="form-label">Nome do Sítio</label>
                                    <input type="text" class="form-control" id="nome_sitio" name="nome_sitio" value="<?= htmlspecialchars($sitio_para_editar['nome_sitio']) ?>" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn <?= !empty($sitio_para_editar['id_sitio']) ? 'btn-primary' : 'btn-success' ?>">
                                        <i class="bi <?= !empty($sitio_para_editar['id_sitio']) ? 'bi-check-circle' : 'bi-plus-circle' ?> me-2"></i>
                                        <?= !empty($sitio_para_editar['id_sitio']) ? 'Salvar Alterações' : 'Adicionar Sítio' ?>
                                    </button>
                                    <?php if (!empty($sitio_para_editar['id_sitio'])): ?>
                                        <a href="gerenciar_sitios.php" class="btn btn-outline-secondary">Cancelar Edição</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm">
                         <div class="card-header">
                            <h5>Sítios Cadastrados</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome do Sítio</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lista_de_sitios as $sitio): ?>
                                            <tr>
                                                <td><?= $sitio['id_sitio'] ?></td>
                                                <td><?= htmlspecialchars($sitio['nome_sitio']) ?></td>
                                                <td class="text-center">
                                                    <a href="gerenciar_sitios.php?action=edit&id=<?= $sitio['id_sitio'] ?>" class="btn btn-sm btn-warning me-2" title="Editar">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </a>
                                                    <a href="gerenciar_sitios.php?action=delete&id=<?= $sitio['id_sitio'] ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este sítio?')">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>