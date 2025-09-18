<?php
session_start();

// Definir o fuso horário para São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    if (isset($_COOKIE['remember_me'])) {
        require_once 'php/config.php';
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
        } else {
            setcookie('remember_me', '', time() - 3600, "/");
            header('Location: index.php');
            exit();
        }
    } else {
        header('Location: index.php');
        exit();
    }
}

// Obter a data atual (apenas dia e mês)
$data_atual = date('m-d');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início - La Vita Andradas</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Ajuste da altura dos cards */
        .card-equal-height {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Ajuste da imagem no carrossel */
        .carousel-img {
            height: 650px; /* altura maior para melhor visualização */
            width: 100%;
            object-fit: contain; /* mostra a imagem inteira sem cortar */
            background-color: #f8f9fa; /* fundo neutro para áreas vazias */
        }

        /* Ajuste do iframe do Google Maps */
        .carousel-map {
            height: 650px;
            width: 100%;
            border: 0;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include 'php/header.php'; ?>

    <!-- Sidebar -->
    <?php include 'php/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" style="margin-left: 250px; margin-top: 70px; padding: 20px;">
        <div class="content-wrapper">
            <div class="row g-4">
                
                <!-- Cards lado a lado com mesma altura -->
                <div class="col-md-6 d-flex align-items-stretch">
                    <div class="card shadow-sm card-equal-height w-100">
                        <div class="card-body">
                            <h5 class="card-title">Membros da CIPA</h5>
                            <ul class="list-unstyled">
                                <li><strong>Coordenador:</strong> Gabriel Barbosa - Lopes</li>
                                <li><strong>Membro:</strong> Victor Lima - Lopes</li>
                                <li><strong>Membro:</strong> Erivelton Patrick - Lopes</li>
                                <li><strong>Membro:</strong> Natalia Santos - Lopes</li>
                                <li><strong>Membro:</strong> Cássia - Monte Alto</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 d-flex align-items-stretch">
                    <div class="card shadow-sm card-equal-height w-100">
                        <div class="card-body">
                            <h5 class="card-title">Aniversariantes do dia 🎉</h5>
                            <p class="text-muted">Nenhum aniversariante hoje.</p>
                        </div>
                    </div>
                </div>

                <!-- Carrossel -->
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Destaques</h5>
                            <div id="carouselDestaques" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    
                                    <!-- Slide 1: Localização -->
                                    <div class="carousel-item active">
                                        <iframe
                                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3699.30208358041!2d-46.53779622507858!3d-21.99973340608409!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94c9bde72e52dfe3%3A0x1b47b8a5813be424!2sLa%20Vita%20-%20Andradas!5e0!3m2!1spt-BR!2sbr!4v1743094142856!5m2!1spt-BR!2sbr"
                                            class="carousel-map rounded"
                                            allowfullscreen=""
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                                    </div>

                                    <!-- Slide 2: Imagem Institucional -->
                                    <div class="carousel-item">
                                        <img src="img/banner1.jpg" class="carousel-img rounded" alt="Banner Institucional">
                                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
                                            <h5>Bem-vindo ao Sistema La Vita Andradas</h5>
                                            <p>Gerencie suas operações com mais eficiência.</p>
                                        </div>
                                    </div>

                                    <!-- Slide 3: Conteúdo Futuro -->
                                    <div class="carousel-item">
                                        <img src="img/banner2.jpg" class="carousel-img rounded" alt="Slide Extra">
                                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
                                            <h5>Novidades em breve</h5>
                                            <p>Fique atento para futuras atualizações do sistema.</p>
                                        </div>
                                    </div>

                                </div>

                                <!-- Controles do carrossel -->
                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselDestaques" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carouselDestaques" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Próximo</span>
                                </button>

                                <!-- Indicadores -->
                                <div class="carousel-indicators">
                                    <button type="button" data-bs-target="#carouselDestaques" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                    <button type="button" data-bs-target="#carouselDestaques" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                    <button type="button" data-bs-target="#carouselDestaques" data-bs-slide-to="2" aria-label="Slide 3"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>