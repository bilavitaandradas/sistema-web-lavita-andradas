<?php
// Inicia a sessão.
session_start();

// Inclui a configuração do banco de dados.
require_once '../../php/config.php';

// 1. VERIFICAÇÃO DE LOGIN
// Garante que o usuário está logado.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

$idLancamento = $_GET['id'] ?? null;
$idQuestionarioParaVoltar = $_GET['qid'] ?? null; // Pega o ID para o redirecionamento inteligente

// 2. VERIFICAÇÃO DO ID DO LANÇAMENTO
// Verifica se o ID do lançamento foi fornecido.
if ($idLancamento) {

    // --- NOVO: BLOCO DE VERIFICAÇÃO DE PERMISSÃO ---
    
    // Passo A: Descobrir a qual questionário este lançamento pertence.
    $stmt_q_id = $conn->prepare("SELECT id_questionario FROM respostas_questionario WHERE id_lancamento = ? LIMIT 1");
    $stmt_q_id->bind_param('s', $idLancamento);
    $stmt_q_id->execute();
    $stmt_q_id->bind_result($id_questionario_do_lancamento);
    $stmt_q_id->fetch();
    $stmt_q_id->close();

    $tem_permissao = false;
    // Se encontramos um questionário associado...
    if ($id_questionario_do_lancamento) {
        // Passo B: Verificar se o usuário logado tem permissão para este questionário.
        $id_usuario_logado = $_SESSION['usuario_id'];
        $stmt_perm = $conn->prepare("SELECT COUNT(*) FROM questionario_permissoes WHERE id_questionario = ? AND id_usuario = ?");
        $stmt_perm->bind_param("ii", $id_questionario_do_lancamento, $id_usuario_logado);
        $stmt_perm->execute();
        $stmt_perm->bind_result($count);
        $stmt_perm->fetch();
        $stmt_perm->close();

        if ($count > 0) {
            $tem_permissao = true;
        }
    }

    // 3. EXECUÇÃO DA EXCLUSÃO (SOMENTE SE TIVER PERMISSÃO)
    if ($tem_permissao) {
        // A lógica de exclusão que você já tinha, agora protegida pelo 'if'.
        $query = "DELETE FROM respostas_questionario WHERE id_lancamento = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param('s', $idLancamento);
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Lançamento excluído com sucesso!";
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao excluir o lançamento: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao preparar a consulta: " . $conn->error;
        }
    } else {
        // Se a verificação de permissão falhar, define uma mensagem de erro.
        $_SESSION['mensagem_erro'] = "Acesso negado. Você não tem permissão para excluir este lançamento.";
    }

} else {
    $_SESSION['mensagem_erro'] = "ID do lançamento não fornecido.";
}

// 4. REDIRECIONAMENTO INTELIGENTE
// Redireciona de volta para a página de verificação, já com o questionário correto selecionado.
if ($idQuestionarioParaVoltar) {
    header('Location: verificar.php?questionario=' . $idQuestionarioParaVoltar);
} else {
    header('Location: verificar.php');
}
exit();
?>