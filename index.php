<?php
session_start();
include 'php/config.php'; // Conexão ao banco de dados

// Verificar se existe um cookie de "remember_me" para autenticar automaticamente
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE['remember_me'])) {
    $remember_token = $_COOKIE['remember_me'];
    $query = "SELECT id, username, nome, role FROM usuarios WHERE remember_token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $remember_token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['role'] = $usuario['role'];
    }
}

// Exibir mensagem de erro, se houver
$erro = isset($_SESSION['erro']) ? $_SESSION['erro'] : '';
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Vita Andradas - Login</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
</head>

<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <img src="img/logo.png" alt="Logo da Empresa" class="mb-4" style="max-width: 150px;">
                        <h2 class="card-title mb-4">Faça seu Login</h2>
                        <form action="php/login.php" method="POST" autocomplete="off">
                            <div class="mb-3 text-start">
                                <label for="username" class="form-label">Usuário:</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3 text-start">
                                <label for="password" class="form-label">Senha:</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control" required>
                                    <button class="btn border-0 bg-transparent" type="button" id="togglePassword">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>

                            </div>
                            <div class="mb-3 form-check text-start">
                                <input type="checkbox" id="remember-me" name="remember-me" class="form-check-input">
                                <label for="remember-me" class="form-check-label">Continuar Conectado</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Entrar</button>
                            <?php if ($erro) { ?>
                                <div class="alert alert-danger mt-3" role="alert">
                                    <?php echo $erro; ?>
                                </div>
                            <?php } ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

</body>

</html>