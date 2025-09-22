<?php
session_start();

// Garante que apenas usuários com a role 'admin' possam acessar.
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}

require_once '../../php/config.php';

// Valida o ID do usuário vindo da URL.
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_para_inativar = (int)$_GET['id'];
    $id_admin_logado = (int)$_SESSION['usuario_id'];

    // Medida de segurança crucial: impede que um admin inative a si mesmo.
    if ($id_para_inativar === $id_admin_logado) {
        // Define uma mensagem de erro e redireciona.
        $_SESSION['mensagem_admin'] = '<div class="alert alert-danger">Você não pode inativar sua própria conta.</div>';
    } else {
        // Prepara e executa o UPDATE para alterar o status do usuário para 'DESLIGADO'
        $stmt = $conn->prepare("UPDATE usuarios SET status = 'DESATIVADO' WHERE id = ?");
        $stmt->bind_param('i', $id_para_inativar);

        if ($stmt->execute()) {
            $_SESSION['mensagem_admin'] = '<div class="alert alert-success">Usuário inativado com sucesso.</div>';
        } else {
            $_SESSION['mensagem_admin'] = '<div class="alert alert-danger">Erro ao inativar o usuário.</div>';
        }
        $stmt->close();
    }
} else {
    $_SESSION['mensagem_admin'] = '<div class="alert alert-warning">ID de usuário inválido.</div>';
}

// Redireciona de volta para a lista de usuários
header('Location: index.php');
exit();
?>