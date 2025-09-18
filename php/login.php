<?php
session_start();
include 'config.php'; // Conexão ao banco de dados

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember-me']);

    $query = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        if (password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['username'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['role'] = $usuario['role'];

            if ($remember_me) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), "/", "", true, true);

                $update_query = "UPDATE usuarios SET remember_token = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('si', $token, $usuario['id']);
                $update_stmt->execute();
            }

            header('Location: ../inicio.php');
            exit();
        } else {
            $_SESSION['erro'] = 'Usuário ou senha inválidos.';
        }
    } else {
        $_SESSION['erro'] = 'Usuário ou senha inválidos.';
    }

    header('Location: ../index.php');
    exit();
} else {
    header('Location: ../index.php');
    exit();
}
?>
