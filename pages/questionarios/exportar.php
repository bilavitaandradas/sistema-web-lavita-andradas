<?php
session_start();
require_once '../../php/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}
$queryQuestionarios = "SELECT id_questionario, nome_questionario FROM questionarios ORDER BY nome_questionario";
$result = $conn->query($queryQuestionarios);
$questionarios = $result->fetch_all(MYSQLI_ASSOC);
date_default_timezone_set('America/Sao_Paulo');
$dataFimPadrao = date('Y-m-d');
$dataInicioPadrao = date('Y-m-d', strtotime('-7 days'));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Exportar Dados - La Vita Andradas</title>
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <?php include '../../php/header.php'; ?>
    <?php include '../../php/sidebar.php'; ?>

    <div class="container mt-5" style="margin-left: 250px;">
        <div class="row">
            <div class="col-md-8">
                <h1>Exportar Lançamentos</h1>
                <p>Selecione o questionário e o período desejado para gerar um arquivo Excel (.xlsx) com os dados.</p>

                <form id="formExportar" action="gerar_xls.php" method="GET" target="_blank" class="mt-4 p-4 border rounded bg-light">
                    
                    <div class="mb-3">
                        <label for="id_questionario" class="form-label"><strong>1. Selecione o Questionário:</strong></label>
                        <select name="id_questionario" id="id_questionario" class="form-select" required>
                            <option value="">Selecione um questionário...</option>
                            <?php foreach ($questionarios as $q): ?>
                                <option value="<?= $q['id_questionario'] ?>">
                                    <?= htmlspecialchars($q['nome_questionario']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>2. Selecione o Período:</strong></label>
                        <div class="row">
                            <div class="col">
                                <label for="data_inicio" class="form-label small">Data de Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= $dataInicioPadrao ?>" required>
                            </div>
                            <div class="col">
                                <label for="data_fim" class="form-label small">Data de Fim</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= $dataFimPadrao ?>" required>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <button type="button" id="btnExportar" class="btn btn-success w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-excel" viewBox="0 0 16 16"><path d="M5.884 6.68a.5.5 0 1 0-.768.64L7.349 10l-2.233 2.68a.5.5 0 0 0 .768.64L8 10.781l2.116 2.54a.5.5 0 0 0 .768-.64L8.651 10l2.233-2.68a.5.5 0 0 0-.768-.64L8 9.219l-2.116-2.54z"/><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                        Exportar para Excel (.xlsx)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Adiciona um 'escutador' de eventos ao botão
        document.getElementById('btnExportar').addEventListener('click', function() {
            // Pega os elementos do formulário
            const form = document.getElementById('formExportar');
            const questionario = document.getElementById('id_questionario').value;
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;

            // Validação simples para garantir que os campos estão preenchidos
            if (!questionario || !dataInicio || !dataFim) {
                alert('Por favor, preencha todos os campos do filtro.');
                return;
            }

            // Monta a URL para a nossa verificação AJAX
            const urlVerificacao = `verificar_lancamentos_ajax.php?id_questionario=${questionario}&data_inicio=${dataInicio}&data_fim=${dataFim}`;

            // Faz a chamada "invisível" ao servidor
            fetch(urlVerificacao)
                .then(response => response.json()) // Converte a resposta para JSON
                .then(data => {
                    // Se a contagem de lançamentos for maior que 0
                    if (data.total > 0) {
                        // Submete o formulário, o que vai gerar o arquivo na nova aba
                        form.submit();
                    } else {
                        // Se for 0, mostra o alerta na página atual, sem submeter o formulário
                        alert('Nenhum lançamento encontrado para o período e questionário selecionados.');
                    }
                })
                .catch(error => {
                    // Em caso de erro na comunicação
                    console.error('Erro ao verificar os lançamentos:', error);
                    alert('Ocorreu um erro ao verificar os dados. Tente novamente.');
                });
        });
    </script>

</body>
</html>