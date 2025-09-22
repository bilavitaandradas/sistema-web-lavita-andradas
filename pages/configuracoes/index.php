<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}

require_once '../../php/config.php';

// --- BUSCA DOS TOTAIS PARA OS CARDS ---
$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'] ?? 0;
$total_questionarios = $conn->query("SELECT COUNT(*) as total FROM questionarios")->fetch_assoc()['total'] ?? 0;
$total_sitios = $conn->query("SELECT COUNT(*) as total FROM sitios")->fetch_assoc()['total'] ?? 0;

// --- NOVA LÓGICA DE BUSCA ---
// 1. Pega o termo de busca da URL (se existir)
$busca = trim($_GET['busca'] ?? '');

// 2. Monta a consulta SQL base
$query_usuarios = "
    SELECT u.id, u.nome, u.username, u.role, s.nome_sitio, u.status
    FROM usuarios u
    LEFT JOIN sitios s ON u.nome_sitio = s.id_sitio
";

// 3. Se um termo de busca foi fornecido, adiciona o filtro WHERE
if (!empty($busca)) {
    // A cláusula LIKE com '%' permite buscar por partes de um nome ou username
    $query_usuarios .= " WHERE u.nome LIKE ? OR u.username LIKE ?";
}

$query_usuarios .= " ORDER BY u.id ASC";

// 4. Prepara e executa a consulta de forma segura
$stmt = $conn->prepare($query_usuarios);

if (!empty($busca)) {
    $termo_busca = "%{$busca}%"; // Adiciona os wildcards '%' para o LIKE
    $stmt->bind_param('ss', $termo_busca, $termo_busca);
}

$stmt->execute();
$result_usuarios = $stmt->get_result();
$lista_de_usuarios = $result_usuarios->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração do Sistema</title>
    <link rel="icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main class="main-content p-4 mt-5" style="margin-left: 250px;">
        <div class="container-fluid">
            <h1>Administração do Sistema</h1>
            <div class="row mb-4">
                </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-people-fill me-2"></i>Gerenciamento de Usuários</h5>
                    <a href="adicionar_usuario.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Adicionar Novo Usuário</a>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <form method="GET" action="index.php">
                            <div class="input-group">
                                <input type="search" name="busca" class="form-control" placeholder="Buscar por nome ou username..." value="<?= htmlspecialchars($busca) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome Completo</th>
                                    <th>Username (Login)</th>
                                    <th>Cargo (Role)</th>
                                    <th>Sítio</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lista_de_usuarios)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <?php if (!empty($busca)): ?>
                                                Nenhum usuário encontrado para "<?= htmlspecialchars($busca) ?>".
                                            <?php else: ?>
                                                Nenhum usuário cadastrado.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($lista_de_usuarios as $usuario): ?>
                                        <tr>
                                            <td><?= $usuario['id'] ?></td>
                                            <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                            <td><?= htmlspecialchars($usuario['username']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($usuario['role']) ?></span></td>
                                            <td><?= htmlspecialchars($usuario['nome_sitio'] ?? 'Não definido') ?></td>
                                            <td>
                                                <?php
                                                    $badge_class = $usuario['status'] === 'ATIVO' ? 'bg-success' : 'bg-danger';
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($usuario['status']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-warning me-2" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                                <a href="desativar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-danger" title="Desativar" onclick="return confirm('Tem certeza?')"><i class="bi bi-trash-fill"></i></a>
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