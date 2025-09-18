<?php
// Inicia a sessão para que possamos usar variáveis como $_SESSION['usuario_id']
session_start();

// --- Verificação de Segurança ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

// Inclui o arquivo com as configurações de conexão ao banco de dados
require_once '../../php/config.php';

// --- Inicialização de Variáveis de Controle ---
$id_do_questionario = "-";
$abrir_div_perguntas = "none";
$mensagem = "";

// --- Processamento Principal ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Cenário 1: O usuário selecionou um questionário e clicou em "Preencher" ---
    if (isset($_POST["escolheu_btn"])) {
        $id_do_questionario = $_POST['questionario'];

        // --- PONTO DA CORREÇÃO ---
        // SÓ VAMOS MOSTRAR A DIV SE UM QUESTIONÁRIO VÁLIDO FOI ESCOLHIDO
        if ($id_do_questionario != "-") {
            $abrir_div_perguntas = "block";
        } else {
            // Se o usuário clicou sem escolher, damos um feedback claro
            $mensagem = "<div class='alert alert-warning'>Por favor, selecione um questionário antes de continuar.</div>";
        }
    }

    // --- Cenário 2: O usuário preencheu as respostas e clicou em "Confirmar Envio" ---
    if (isset($_POST['lancar_btn'])) {
        $id_do_questionario = $_POST['questionario'];

        if ($id_do_questionario == "-" || empty($id_do_questionario)) {
            $mensagem = "<div class='alert alert-warning'>Selecione um questionário válido antes de enviar.</div>";
        } else {
            try {
                $conn->begin_transaction();
                $id_lancamento = $_SESSION['usuario_id'] . '_' . round(microtime(true) * 1000);

                foreach ($_POST as $campo_id => $valor) {
                    if (strpos($campo_id, 'campo_') === 0) {
                        $id_campo = intval(str_replace('campo_', '', $campo_id));
                        $valor_resposta = trim($valor);

                        if ($valor_resposta === "") {
                            throw new Exception("Todos os campos devem ser preenchidos.");
                        }

                        $valor_resposta = htmlspecialchars($valor_resposta, ENT_QUOTES, 'UTF-8');
                        $descricao_campo = "Resposta preenchida no sistema";

                        $stmt = $conn->prepare("INSERT INTO respostas_questionario (id_questionario, id_campo, valor_resposta, id_usuario, descricao_campo, id_lancamento) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iisiss", $id_do_questionario, $id_campo, $valor_resposta, $_SESSION['usuario_id'], $descricao_campo, $id_lancamento);
                        $stmt->execute();
                    }
                }

                $conn->commit();
                $mensagem = "<div class='alert alert-success'>Respostas salvas com sucesso! ID do Lançamento: $id_lancamento</div>";

            } catch (Exception $e) {
                $conn->rollback();
                $mensagem = "<div class='alert alert-danger'>Erro ao salvar respostas: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// --- Busca de Dados para o Formulário ---
$id_usuario_logado = $_SESSION['usuario_id'];
$query = "
    SELECT q.id_questionario, q.nome_questionario
    FROM questionarios q
    JOIN questionario_permissoes p ON q.id_questionario = p.id_questionario
    WHERE p.id_usuario = ?
    ORDER BY q.nome_questionario
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id_usuario_logado);
$stmt->execute();
$result = $stmt->get_result();
$questionarios = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Realizar Lançamento</title>
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <div class="main-content p-5" style="margin-left: 250px;">
        <h1 class="mb-4">Realizar Lançamento</h1>

        <?= $mensagem ?>

        <form method="post">
            <div class="mb-4">
                <label for="questionario" class="form-label">Selecione o Questionário</label>
                <select class="form-select" id="questionario" name="questionario" required>
                    <option value="-">Escolha um questionário...</option>
                    <?php if (empty($questionarios)): ?>
                        <option value="-" disabled>Nenhum questionário disponível para você.</option>
                    <?php else: ?>
                        <?php foreach ($questionarios as $questionario): ?>
                            <option value="<?= $questionario['id_questionario'] ?>"
                                <?= ($id_do_questionario == $questionario['id_questionario']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($questionario['nome_questionario']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button class="btn btn-primary mt-2" name="escolheu_btn" type="submit">Preencher</button>
            </div>
        </form>

        <div id="divDasPerguntas" class="container" style="display: <?= $abrir_div_perguntas ?>;">
            <form method="post">
                <input type="hidden" name="questionario" value="<?= htmlspecialchars($id_do_questionario) ?>" />
                <?php
                if (!empty($id_do_questionario) && $id_do_questionario != "-") {
                    $stmt = $conn->prepare("SELECT * FROM campos_questionario WHERE id_questionario = ?");
                    $stmt->bind_param('i', $id_do_questionario);
                    $stmt->execute();
                    $result2 = $stmt->get_result();
                    if ($result2->num_rows > 0) {
                        while ($row = $result2->fetch_assoc()) {
                            $tipo_c = $row["tipo_campo"];
                            $nome_campo = htmlspecialchars($row["nome_campo"]);
                            $id_campo = $row["id_campo"];

                            if ($tipo_c == "DATE" || $tipo_c == "TEXT" || $tipo_c == "NUMBER" || $tipo_c == "TIME") {
                                $input_type = strtolower($tipo_c);
                                $step_attr = ($input_type === 'number') ? 'step="any"' : '';
                                echo '<div class="form-floating mb-3"><input type="' . $input_type . '" ' . $step_attr . ' class="form-control" id="campo_' . $id_campo . '" name="campo_' . $id_campo . '" required><label for="campo_' . $id_campo . '">' . $nome_campo . '</label></div>';
                            } elseif ($tipo_c == "DROPDOWN") {
                                $opcoes = json_decode($row["opcoes"], true);
                                if (is_array($opcoes)) {
                                    echo '<div class="form-floating mb-3"><select class="form-select" id="campo_' . $id_campo . '" name="campo_' . $id_campo . '" required><option value="" selected>Selecione...</option>';
                                    foreach ($opcoes as $opcao) {
                                        $opcao_valor = htmlspecialchars(trim($opcao));
                                        echo '<option value="' . $opcao_valor . '">' . $opcao_valor . '</option>';
                                    }
                                    echo '</select><label for="campo_' . $id_campo . '">' . $nome_campo . '</label></div>';
                                }
                            }
                        }
                    }
                    $stmt->close();
                }
                ?>
                <button type="submit" name="lancar_btn" class="btn btn-primary" id="confirmar-envio">Confirmar
                    Envio</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>