<?php
// Inicia a sessão para acesso às variáveis de login
session_start();

// Inclui o arquivo de configuração do banco de dados (conexão $conn)
include '../../php/config.php';

// --- VALIDAÇÃO DE PERMISSÃO ---
// Se o usuário não estiver logado OU não for 'admin', redireciona para a página inicial
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php'); // Redireciona
    exit(); // Interrompe o script
}

// Obtém o ID do indicador enviado via GET (URL), ou null se não enviado
$id_indicador = $_GET['id'] ?? null;

// Se não foi passado o ID, interrompe a execução
if (!$id_indicador) {
    die("ID do indicador não fornecido.");
}

// --- PROCESSO DE ATUALIZAÇÃO ---
// Verifica se o formulário foi enviado pelo método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Captura os valores enviados pelo formulário
    $nome = $_POST['nome_indicador']; // Nome do indicador
    $descricao = $_POST['descricao']; // Descrição
    $link = $_POST['link_indicador']; // Link do indicador
    $roles = $_POST['roles_permitidos'] ?? []; // Array com as roles selecionadas (pode estar vazio)

    // Atualiza as informações do indicador na tabela "indicadores"
    $stmt = $conn->prepare("
        UPDATE indicadores 
        SET nome_indicador = ?, link_indicador = ?, descricao = ? 
        WHERE id_indicador = ?
    ");
    $stmt->bind_param("sssi", $nome, $link, $descricao, $id_indicador);
    $stmt->execute(); // Executa o UPDATE
    $stmt->close();

    // Remove as roles já cadastradas para esse indicador
    $conn->query("DELETE FROM indicador_roles WHERE id_indicador = $id_indicador");

    // Insere as novas roles selecionadas
    $stmtRole = $conn->prepare("
        INSERT INTO indicador_roles (id_indicador, role) VALUES (?, ?)
    ");
    foreach ($roles as $role) {
        $stmtRole->bind_param("is", $id_indicador, $role);
        $stmtRole->execute();
    }
    $stmtRole->close();

    // Redireciona de volta para a página de gerenciamento após salvar
    header("Location: gerenciar.php");
    exit();
}

// --- BUSCAR DADOS DO INDICADOR ---
// Consulta para obter as informações do indicador selecionado
$stmt = $conn->prepare("SELECT * FROM indicadores WHERE id_indicador = ?");
$stmt->bind_param("i", $id_indicador);
$stmt->execute();
$result = $stmt->get_result();
$indicador = $result->fetch_assoc(); // Retorna como array associativo
$stmt->close();

// Se não encontrar o indicador, exibe mensagem de erro
if (!$indicador) {
    die("Indicador não encontrado.");
}

// --- BUSCAR AS ROLES ATUAIS VINCULADAS AO INDICADOR ---
$roles_atual = []; // Array para armazenar as roles já cadastradas
$resRoles = $conn->query("
    SELECT role FROM indicador_roles WHERE id_indicador = $id_indicador
");
while ($row = $resRoles->fetch_assoc()) {
    $roles_atual[] = $row['role']; // Adiciona cada role ao array
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Indicador</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="pt-5">

<?php 
// Inclui o cabeçalho e o menu lateral
include '../../php/header.php'; 
include '../../php/sidebar.php'; 
?>

<!-- Conteúdo principal -->
<div class="container mt-4">
    <h1>Editar Indicador</h1>

    <!-- Formulário de edição -->
    <form method="POST">
        
        <!-- Campo Nome do Indicador -->
        <div class="mb-3">
            <label for="nome_indicador" class="form-label">Nome</label>
            <input type="text" 
                   name="nome_indicador" 
                   id="nome_indicador" 
                   class="form-control"
                   value="<?= htmlspecialchars($indicador['nome_indicador']) ?>" 
                   required>
        </div>

        <!-- Campo Descrição -->
        <div class="mb-3">
            <label for="descricao" class="form-label">Descrição</label>
            <textarea name="descricao" id="descricao" class="form-control"><?= htmlspecialchars($indicador['descricao']) ?></textarea>
        </div>

        <!-- Campo Link -->
        <div class="mb-3">
            <label for="link_indicador" class="form-label">Link</label>
            <input type="url" 
                   name="link_indicador" 
                   id="link_indicador" 
                   class="form-control"
                   value="<?= htmlspecialchars($indicador['link_indicador']) ?>" 
                   required>
        </div>

        <!-- Seleção de Roles Permitidas -->
        <div class="mb-3">
            <label for="roles_permitidos" class="form-label">Roles Permitidos</label>
            <select multiple class="form-select" name="roles_permitidos[]">
                <?php
                // Lista de roles disponíveis no sistema
                $roles_disponiveis = ['admin', 'gerente', 'qualidade', 'manutencao', 'estoque', 'rh'];

                // Cria as opções no <select>, marcando as já selecionadas
                foreach ($roles_disponiveis as $role) {
                    $selected = in_array($role, $roles_atual) ? 'selected' : '';
                    echo "<option value=\"$role\" $selected>" . ucfirst($role) . "</option>";
                }
                ?>
            </select>
        </div>

        <!-- Botões -->
        <button type="submit" class="btn btn-success">Salvar Alterações</button>
        <a href="gerenciar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

</body>
</html>
