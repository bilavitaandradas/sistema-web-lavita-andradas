<?php
session_start();

// Verifica se o usuário está logado e se possui o papel 'admin'. (LÓGICA MANTIDA)
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}

include '../../php/config.php'; // Conexão com o banco de dados

// Lógica para cadastrar um novo indicador (LÓGICA MANTIDA)
if (isset($_POST['cadastrar_indicador'])) {
    $nome = $_POST['nome_indicador'];
    $descricao = $_POST['descricao'];
    $link = $_POST['link_indicador'];
    $roles = $_POST['roles_permitidos'] ?? [];
    $usuario_id = $_SESSION['usuario_id'];

    $stmt = $conn->prepare("INSERT INTO indicadores (nome_indicador, link_indicador, descricao, criado_por) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nome, $link, $descricao, $usuario_id);
    $stmt->execute();
    $id_indicador = $stmt->insert_id;
    $stmt->close();

    $stmtRole = $conn->prepare("INSERT INTO indicador_roles (id_indicador, role) VALUES (?, ?)");
    foreach ($roles as $role) {
        $stmtRole->bind_param("is", $id_indicador, $role);
        $stmtRole->execute();
    }
    $stmtRole->close();

    $mensagem = "Indicador cadastrado com sucesso!";
}

// Array com os papéis (roles) disponíveis no sistema para gerar os checkboxes
$available_roles = [
    'admin' => 'Admin',
    'gerente' => 'Gerente',
    'qualidade' => 'Qualidade',
    'manutencao' => 'Manutenção',
    'estoque' => 'Estoque',
    'rh' => 'RH/DP'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Indicadores - La Vita Andradas</title>
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>

    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <main style="margin-left: 250px; padding: 20px; padding-top: 95px;">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Gerenciar Indicadores</h1>
                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Voltar para Galeria</a>
            </div>

            <?php if (isset($mensagem)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $mensagem ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-3" id="gerenciarTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="cadastrar-tab" data-bs-toggle="tab" data-bs-target="#cadastrar" type="button" role="tab" aria-controls="cadastrar" aria-selected="true">
                        <i class="bi bi-plus-circle-fill me-2"></i>Cadastrar Novo
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="listar-tab" data-bs-toggle="tab" data-bs-target="#listar" type="button" role="tab" aria-controls="listar" aria-selected="false">
                        <i class="bi bi-list-ul me-2"></i>Listar Indicadores
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="gerenciarTabsContent">
                
                <div class="tab-pane fade show active" id="cadastrar" role="tabpanel" aria-labelledby="cadastrar-tab">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Cadastrar Novo Indicador</h5>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="nome_indicador" class="form-label">Nome do Indicador</label>
                                    <input type="text" class="form-control" id="nome_indicador" name="nome_indicador" required>
                                </div>
                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="link_indicador" class="form-label">Link do Indicador (Embed)</label>
                                    <input type="url" class="form-control" id="link_indicador" name="link_indicador" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label d-block">Permissões de Acesso</label>
                                    <?php foreach ($available_roles as $role_value => $role_label): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="roles_permitidos[]" value="<?= $role_value ?>" id="role_<?= $role_value ?>">
                                            <label class="form-check-label" for="role_<?= $role_value ?>"><?= $role_label ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" name="cadastrar_indicador" class="btn btn-success"><i class="bi bi-check-circle-fill me-2"></i>Cadastrar Indicador</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="listar" role="tabpanel" aria-labelledby="listar-tab">
                     <div class="card shadow-sm">
                        <div class="card-header">
                            <input type="text" id="filtroIndicador" class="form-control" placeholder="Buscar indicador pelo nome...">
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="tabelaIndicadores">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th>Permissões</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Consulta para buscar todos os indicadores e os respectivos roles (LÓGICA MANTIDA)
                                        $sql = "SELECT i.*, GROUP_CONCAT(ir.role SEPARATOR ', ') AS roles 
                                                FROM indicadores i
                                                LEFT JOIN indicador_roles ir ON i.id_indicador = ir.id_indicador
                                                GROUP BY i.id_indicador";
                                        $result = $conn->query($sql);
                                        while ($row = $result->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nome_indicador']) ?></td>
                                                <td><?= htmlspecialchars($row['descricao']) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars(str_replace(",", ", ", $row['roles'])) ?></span></td>
                                                <td class="text-center">
                                                    <a href="<?= htmlspecialchars($row['link_indicador']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Visualizar"><i class="bi bi-eye-fill"></i></a>
                                                    <a href="editar.php?id=<?= $row['id_indicador'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                                    <a href="excluir.php?id=<?= $row['id_indicador'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?');" title="Excluir"><i class="bi bi-trash-fill"></i></a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
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
    <script>
        // Script para filtrar a tabela (LÓGICA MANTIDA)
        document.getElementById('filtroIndicador').addEventListener('input', function () {
            const termo = this.value.toLowerCase();
            const linhas = document.querySelectorAll('#tabelaIndicadores tbody tr');
            linhas.forEach(function (linha) {
                const nome = linha.cells[0].textContent.toLowerCase();
                linha.style.display = nome.includes(termo) ? '' : 'none';
            });
        });
    </script>
</body>
</html>