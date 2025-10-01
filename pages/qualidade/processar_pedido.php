<?php
session_start();
require_once '../../php/config.php';

// --- VERIFICAÇÃO DE SEGURANÇA ---
// Garante que o usuário está logado e tem permissão para criar um pedido.
// Ajuste as 'roles' permitidas conforme sua necessidade.
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'qualidade', 'gerente'])) {
    // Se não tiver permissão, redireciona com uma mensagem de erro.
    $_SESSION['mensagem_pedido'] = '<div class="alert alert-danger">Você não tem permissão para criar pedidos.</div>';
    header('Location: index.php');
    exit();
}

// --- PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['defensivo_id'])) {
    
    $defensivo_ids = $_POST['defensivo_id'];
    $quantidades = $_POST['quantidade'];
    $id_solicitante = $_SESSION['usuario_id']; // Pega o ID do usuário da sessão

    // Validação para garantir que o número de defensivos e quantidades é o mesmo
    if (count($defensivo_ids) !== count($quantidades)) {
        $_SESSION['mensagem_pedido'] = '<div class="alert alert-danger">Ocorreu um erro ao processar os itens. Tente novamente.</div>';
        header('Location: index.php');
        exit();
    }

    // Inicia uma transação. Ou tudo funciona, ou nada é salvo.
    $conn->begin_transaction();

    try {
        // 1. Insere o "cabeçalho" do pedido na tabela 'pedidos_compra'
        // O status inicial é sempre 'Pendente'
        $stmt_pedido = $conn->prepare("INSERT INTO pedidos_compra (id_solicitante, data_solicitacao, status_aprovacao) VALUES (?, NOW(), 'Pendente')");
        $stmt_pedido->bind_param('i', $id_solicitante);
        $stmt_pedido->execute();
        
        // 2. Pega o ID do pedido que acabamos de criar
        $id_pedido_criado = $conn->insert_id;
        $stmt_pedido->close();

        // 3. Prepara a query para inserir os itens do pedido
        $stmt_item = $conn->prepare("INSERT INTO itens_pedido (id_pedido, id_defensivo, quantidade_solicitada) VALUES (?, ?, ?)");

        // 4. Itera sobre cada item enviado pelo formulário e o insere no banco
        for ($i = 0; $i < count($defensivo_ids); $i++) {
            $id_defensivo = (int)$defensivo_ids[$i];
            $quantidade = (float)$quantidades[$i];

            // Validação simples para garantir que os dados são válidos
            if ($id_defensivo > 0 && $quantidade > 0) {
                $stmt_item->bind_param('iid', $id_pedido_criado, $id_defensivo, $quantidade);
                $stmt_item->execute();
            }
        }
        $stmt_item->close();

        // 5. Se todas as inserções deram certo, confirma a transação
        $conn->commit();
        $_SESSION['mensagem_pedido'] = '<div class="alert alert-success">Pedido de compra  enviado com sucesso!</div>';

    } catch (Exception $e) {
        // Se qualquer erro ocorreu, desfaz todas as operações
        $conn->rollback();
        $_SESSION['mensagem_pedido'] = '<div class="alert alert-danger">Erro ao salvar o pedido: ' . $e->getMessage() . '</div>';
    }

} else {
    // Se o formulário foi enviado vazio
    $_SESSION['mensagem_pedido'] = '<div class="alert alert-warning">Nenhum item foi adicionado ao pedido.</div>';
}

// Redireciona de volta para a página de novos pedidos para mostrar a mensagem
header('Location: index.php');
exit();

?>