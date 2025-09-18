<?php
// Iniciar a sessão
session_start();

// Verificar se o usuário está logado
if (isset($_SESSION['usuario_id'])) {
    // Destruir todas as variáveis de sessão
    $_SESSION = array();
    session_destroy();
}

// Remover o cookie 'remember_me', se existir
if (isset($_COOKIE['remember_me'])) {
    // Definir o cookie com valor vazio e expiração no passado
    setcookie('remember_me', '', time() - 3600, '/');
}

// Redirecionar para a página de login
header('Location: ../index.php'); // Ajuste o caminho, se necessário
exit();
