<?php
session_start();

// --- VERIFICA PERMISSÕES ---
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'gerente'])) {
    header('Location: /TCC/index.php');
    exit();
}

require_once '../../php/config.php';

$mensagem = "";

// --- PROCESSA A EXCLUSÃO DE UM QUESTIONÁRIO ---
if (isset($_POST['excluir_questionario'])) {
    $id_questionario_excluir = intval($_POST['id_questionario']);

    // Exclui os campos vinculados ao questionário
    $stmtCampos = $conn->prepare("DELETE FROM campos_questionario WHERE id_questionario = ?");
    $stmtCampos->bind_param("i", $id_questionario_excluir);
    $stmtCampos->execute();

    // Exclui o próprio questionário
    $stmtQ = $conn->prepare("DELETE FROM questionarios WHERE id_questionario = ?");
    $stmtQ->bind_param("i", $id_questionario_excluir);
    
    if ($stmtQ->execute()) {
        $mensagem = "<div class='alert alert-success'>Questionário excluído com sucesso.</div>";
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao excluir o questionário.</div>";
    }
}

// --- Recupera filtro de nome do questionário se houver ---
$filtro_nome = isset($_GET['filtro']) ? trim($_GET['filtro']) : '';

// --- Consulta SQL com filtro de nome ---
$query = "
    SELECT q.id_questionario, q.nome_questionario, q.descricao_questionario, s.nome_sitio, q.criado_em, q.atualizado_em
    FROM questionarios q
    LEFT JOIN sitios s ON q.id_sitio = s.id_sitio
    WHERE q.nome_questionario LIKE ?
    ORDER BY q.atualizado_em DESC
";

$stmt = $conn->prepare($query);
$like = "%$filtro_nome%";
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();
$questionarios = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Questionários</title>
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-5" style="margin-left: 250px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Configuração de Questionários</h2>
            <a href="criar_questionario.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Novo Questionário
            </a>
        </div>

        <!-- Exibe mensagem de feedback -->
        <?php if (!empty($mensagem)) echo $mensagem; ?>

        <form method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="filtro" class="form-control" placeholder="Buscar por nome do questionário..."
                    value="<?= htmlspecialchars($filtro_nome) ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
        </form>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Nome</th>
                    <th style="width: 500px;">Descrição</th>
                    <th style="width: 200px;">Sítio</th>
                    <th>Criado em</th>
                    <th>Última Atualização</th>
                    <th style="width: 300px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($questionarios) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhum questionário encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($questionarios as $q): ?>
                        <tr>
                            <td><?= htmlspecialchars($q['nome_questionario']) ?></td>
                            <td><?= htmlspecialchars($q['descricao_questionario']) ?></td>
                            <td><?= htmlspecialchars($q['nome_sitio'] ?? '-') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($q['criado_em'])) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($q['atualizado_em'])) ?></td>
                            <td>
                                <!-- Botão Editar Questionário -->
                                <a href="editar_questionario.php?id_questionario=<?= $q['id_questionario'] ?>"
                                    class="btn btn-primary btn-sm">
                                    Editar
                                </a>

                                <!-- Botão Gerenciar Campos -->
                                <a href="editar_campos.php?id_questionario=<?= $q['id_questionario'] ?>"
                                    class="btn btn-warning btn-sm">
                                    Campos
                                </a>

                                <!-- Botão Excluir Questionário -->
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_questionario" value="<?= $q['id_questionario'] ?>">
                                    <button type="submit" name="excluir_questionario" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Tem certeza que deseja excluir este questionário? Esta ação é irreversível.')">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
