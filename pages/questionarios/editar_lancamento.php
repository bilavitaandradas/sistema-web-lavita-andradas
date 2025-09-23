<?php
// Inicia a sessão e inclui a configuração
session_start();
require_once '../../php/config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

// ==================================================================
// ETAPA 2: PROCESSAR O FORMULÁRIO (QUANDO ENVIADO VIA POST)
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obter os dados do formulário
    $idLancamento = $_POST['id_lancamento'];
    $idQuestionario = $_POST['id_questionario'];
    $respostas = $_POST['respostas'];

    // --- NOVO: BLOCO DE VERIFICAÇÃO DE PERMISSÃO PARA SALVAR (POST) ---
    $id_usuario_logado = $_SESSION['usuario_id'];
    $stmt_perm_post = $conn->prepare("SELECT COUNT(*) FROM questionario_permissoes WHERE id_questionario = ? AND id_usuario = ?");
    $stmt_perm_post->bind_param("ii", $idQuestionario, $id_usuario_logado);
    $stmt_perm_post->execute();
    $stmt_perm_post->bind_result($count_post);
    $stmt_perm_post->fetch();
    $stmt_perm_post->close();

    // Se a contagem for 0, o usuário não tem permissão. A ação é bloqueada.
    if ($count_post == 0) {
        $_SESSION['mensagem_erro'] = "Acesso negado. Você não tem permissão para salvar alterações neste lançamento.";
        header('Location: verificar.php');
        exit();
    }
    // -----------------------------------------------------------------

    // Se a permissão foi validada, o script continua para salvar os dados...
    if (empty($idLancamento) || empty($idQuestionario) || empty($respostas)) {
        $_SESSION['mensagem_erro'] = "Ocorreu um erro ao enviar os dados.";
        header('Location: verificar.php?questionario=' . $idQuestionario);
        exit();
    }

    $conn->begin_transaction();
    try {
        $query = "UPDATE respostas_questionario SET valor_resposta = ? WHERE id_resposta = ?";
        $stmt = $conn->prepare($query);
        foreach ($respostas as $id_resposta => $valor_resposta) {
            $stmt->bind_param('si', $valor_resposta, $id_resposta);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar a resposta ID: " . $id_resposta);
            }
        }
        $conn->commit();
        $_SESSION['mensagem_sucesso'] = "Lançamento atualizado com sucesso!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem_erro'] = "Erro ao atualizar o lançamento: " . $e->getMessage();
    }
    if (isset($stmt)) {
        $stmt->close();
    }

    header('Location: verificar.php?questionario=' . $idQuestionario);
    exit();
}


// ==================================================================
// ETAPA 1: EXIBIR O FORMULÁRIO (QUANDO ACESSADO VIA GET)
// ==================================================================

$idLancamento = $_GET['id'] ?? null;
if (!$idLancamento) {
    $_SESSION['mensagem_erro'] = "ID do lançamento não especificado.";
    header('Location: verificar.php');
    exit();
}

// Busca os dados do lançamento para exibir no formulário
$query = "SELECT r.id_resposta, r.valor_resposta, r.id_questionario, c.nome_campo, c.tipo_campo, q.nome_questionario FROM respostas_questionario AS r JOIN campos_questionario AS c ON r.id_campo = c.id_campo JOIN questionarios AS q ON r.id_questionario = q.id_questionario WHERE r.id_lancamento = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $idLancamento);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $respostas = $result->fetch_all(MYSQLI_ASSOC);
    $nomeQuestionario = $respostas[0]['nome_questionario'];
    $idQuestionario = $respostas[0]['id_questionario']; // Pega o ID do questionário a partir dos dados do lançamento
} else {
    $_SESSION['mensagem_erro'] = "Lançamento não encontrado.";
    header('Location: verificar.php');
    exit();
}
$stmt->close();


// --- NOVO: BLOCO DE VERIFICAÇÃO DE PERMISSÃO PARA VISUALIZAR (GET) ---
$id_usuario_logado = $_SESSION['usuario_id'];
$stmt_perm_get = $conn->prepare("SELECT COUNT(*) FROM questionario_permissoes WHERE id_questionario = ? AND id_usuario = ?");
$stmt_perm_get->bind_param("ii", $idQuestionario, $id_usuario_logado);
$stmt_perm_get->execute();
$stmt_perm_get->bind_result($count_get);
$stmt_perm_get->fetch();
$stmt_perm_get->close();

// Se a contagem for 0, o usuário não tem permissão para ver esta página.
if ($count_get == 0) {
    $_SESSION['mensagem_erro'] = "Acesso negado. Você não tem permissão para editar este lançamento.";
    // Redireciona para a página de verificação, mostrando o erro.
    header('Location: verificar.php?questionario=' . $idQuestionario);
    exit();
}
// -----------------------------------------------------------------


function getTipoInput($tipo_campo) {
    switch ($tipo_campo) {
        case 'DATE': return 'date';
        case 'TIME': return 'time';
        case 'NUMBER': return 'number';
        default: return 'text';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Lançamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
</head>
<body>
    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <div class="container mt-5" style="margin-left: 250px;">
        <h1>Editar Lançamento</h1>
        <h5>Questionário: <?= htmlspecialchars($nomeQuestionario) ?></h5>
        <p>ID do Lançamento: <?= htmlspecialchars($idLancamento) ?></p>

        <form method="POST" action="editar_lancamento.php">
            <input type="hidden" name="id_lancamento" value="<?= htmlspecialchars($idLancamento) ?>">
            <input type="hidden" name="id_questionario" value="<?= htmlspecialchars($idQuestionario) ?>">

            <?php foreach ($respostas as $resposta): ?>
                <div class="mb-3">
                    <label for="resposta_<?= $resposta['id_resposta'] ?>" class="form-label">
                        <strong><?= htmlspecialchars($resposta['nome_campo']) ?></strong>
                    </label>
                    <?php
                    $tipoInput = getTipoInput($resposta['tipo_campo']);
                    $valor = htmlspecialchars($resposta['valor_resposta']);
                    $step = ($tipoInput === 'number') ? 'step="any"' : '';
                    ?>
                    <input type="<?= $tipoInput ?>" class="form-control" id="resposta_<?= $resposta['id_resposta'] ?>" name="respostas[<?= $resposta['id_resposta'] ?>]" value="<?= $valor ?>" <?= $step ?> required>
                </div>
            <?php endforeach; ?>
            <hr>
            <button type="submit" class="btn btn-success">Salvar Alterações</button>
            <a href="verificar.php?questionario=<?= htmlspecialchars($idQuestionario) ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>