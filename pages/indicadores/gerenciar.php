<?php
session_start();

// Verifica se o usuário está logado e se possui o papel 'admin'.
// Caso contrário, redireciona para a página inicial
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}

include '../../php/config.php'; // Conexão com o banco de dados

// Lógica para cadastrar um novo indicador
if (isset($_POST['cadastrar_indicador'])) {
    // Recebe os dados do formulário
    $nome = $_POST['nome_indicador'];
    $descricao = $_POST['descricao'];
    $link = $_POST['link_indicador'];
    $roles = $_POST['roles_permitidos'] ?? [];
    $usuario_id = $_SESSION['usuario_id'];

    // Insere os dados na tabela 'indicadores'
    $stmt = $conn->prepare("INSERT INTO indicadores (nome_indicador, link_indicador, descricao, criado_por) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nome, $link, $descricao, $usuario_id);
    $stmt->execute();
    $id_indicador = $stmt->insert_id; // Captura o ID do novo indicador
    $stmt->close();

    // Insere os papéis (roles) permitidos para visualizar o indicador
    $stmtRole = $conn->prepare("INSERT INTO indicador_roles (id_indicador, role) VALUES (?, ?)");
    foreach ($roles as $role) {
        $stmtRole->bind_param("is", $id_indicador, $role);
        $stmtRole->execute();
    }
    $stmtRole->close();

    // Mensagem de sucesso
    $mensagem = "Indicador cadastrado com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Indicadores - La Vita Andradas</title>
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="pt-5">

    <!-- Cabeçalho e barra lateral -->
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <div class="container mt-4">
        <h1 class="mb-4">Gerenciar Indicadores</h1>

        <!-- Mensagem de feedback -->
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-success"><?= $mensagem ?></div>
        <?php endif; ?>

        <!-- Formulário para cadastrar novo indicador -->
        <h2>Cadastrar Novo Indicador</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="nome_indicador" class="form-label">Nome do Indicador</label>
                <input type="text" class="form-control" id="nome_indicador" name="nome_indicador" required>
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao"></textarea>
            </div>

            <div class="mb-3">
                <label for="link_indicador" class="form-label">Link do Power BI (Embed ou Compartilhado)</label>
                <input type="url" class="form-control" id="link_indicador" name="link_indicador" required>
            </div>

            <div class="mb-3">
                <label for="roles_permitidos" class="form-label">Roles Permitidos</label>
                <select multiple class="form-select" id="roles_permitidos" name="roles_permitidos[]">
                    <!-- Permissões possíveis para visualizar o indicador -->
                    <option value="admin">Admin</option>
                    <option value="gerente">Gerente</option>
                    <option value="qualidade">Qualidade</option>
                    <option value="manutencao">Manutenção</option>
                    <option value="estoque">Estoque</option>
                    <option value="rh">RH/DP</option>
                </select>
            </div>

            <button type="submit" name="cadastrar_indicador" class="btn btn-primary">Cadastrar Indicador</button>
        </form>

        <hr class="my-4">

        <!-- Listagem dos indicadores cadastrados -->
        <h2>Indicadores Cadastrados</h2>

        <!-- Campo de filtro por nome -->
        <div class="mb-3">
            <input type="text" id="filtroIndicador" class="form-control" placeholder="Buscar indicador pelo nome...">
        </div>

        <!-- Tabela com os indicadores -->
        <table class="table table-bordered mt-3" id="tabelaIndicadores">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Roles Permitidos</th>
                    <th>Link</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Consulta para buscar todos os indicadores e os respectivos roles
                $sql = "SELECT i.*, GROUP_CONCAT(ir.role SEPARATOR ', ') AS roles 
                        FROM indicadores i
                        LEFT JOIN indicador_roles ir ON i.id_indicador = ir.id_indicador
                        GROUP BY i.id_indicador";

                $result = $conn->query($sql);

                // Exibição de cada indicador na tabela
                while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nome_indicador']) ?></td>
                        <td><?= htmlspecialchars($row['descricao']) ?></td>
                        <td><?= htmlspecialchars($row['roles']) ?></td>
                        <td><a href="<?= htmlspecialchars($row['link_indicador']) ?>" target="_blank">Visualizar</a></td>
                        <td>
                            <!-- Botão para editar o indicador -->
                            <a href="editar.php?id=<?= $row['id_indicador'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            
                            <!-- Botão para excluir o indicador -->
                            <a href="excluir.php?id=<?= $row['id_indicador'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Tem certeza que deseja excluir este indicador?');">
                               Excluir
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Script para filtrar indicadores digitando no input -->
        <script>
            document.getElementById('filtroIndicador').addEventListener('input', function () {
                const termo = this.value.toLowerCase();
                const linhas = document.querySelectorAll('#tabelaIndicadores tbody tr');

                linhas.forEach(function (linha) {
                    const nome = linha.cells[0].textContent.toLowerCase();
                    linha.style.display = nome.includes(termo) ? '' : 'none';
                });
            });
        </script>

    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
