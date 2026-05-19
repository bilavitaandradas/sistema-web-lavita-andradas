<?php
session_start();

require_once '../../php/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /TCC/index.php');
    exit();
}

// --- CONFIGURAÇÃO DA PAGINAÇÃO ---
$limite = 20;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $limite;

// --- FUNÇÃO PARA FORMATAR VALORES ---
function formatarValor($valor, $tipo) {
    if ($valor === null || $valor === '') {
        return '-';
    }

    switch ($tipo) {

        case 'DATE':

            $d = DateTime::createFromFormat('Y-m-d', $valor);

            return $d
                ? $d->format('d/m/Y')
                : $valor;

        case 'TIME':

            $d = DateTime::createFromFormat('H:i:s', $valor);

            return $d
                ? $d->format('H:i')
                : $valor;

        case 'NUMBER':
            return number_format((float)$valor, 2, ',', '.');
        case 'CHECKBOX':
            $valorLimpo = html_entity_decode($valor, ENT_QUOTES, 'UTF-8');
            $decoded = json_decode($valorLimpo, true);
            if (is_array($decoded)) {
                $html = '';
                foreach ($decoded as $item) {
                    $html .= '<span class="badge badge-checkbox">' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</span> ';
                }
                return $html;
            }
            return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        default:

            return htmlspecialchars(
                $valor,
                ENT_QUOTES,
                'UTF-8'
            );
    }
}

// --- FILTROS ---
$idQuestionario = isset($_GET['questionario']) ? intval($_GET['questionario']) : null;
$usuarioFiltro = isset($_GET['usuario']) && $_GET['usuario'] !== '' ? intval($_GET['usuario']) : null;
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

// --- LISTA DE QUESTIONÁRIOS (PARA O FILTRO) ---
$queryQuestionarios = "
    SELECT q.id_questionario, q.nome_questionario, s.nome_sitio 
    FROM questionarios q 
    JOIN sitios s ON q.id_sitio = s.id_sitio 
    ORDER BY s.nome_sitio, q.nome_questionario
";
$resultQuest = $conn->query($queryQuestionarios);
$questionarios = $resultQuest->fetch_all(MYSQLI_ASSOC);

// --- LISTA DE USUÁRIOS (PARA O FILTRO) ---
// Trazemos os utilizadores ordenados por nome para preencher o novo select
$queryUsuarios = "SELECT id, nome FROM usuarios ORDER BY nome ASC";
$resultUsu = $conn->query($queryUsuarios);
$usuariosLista = $resultUsu ? $resultUsu->fetch_all(MYSQLI_ASSOC) : [];

// --- VARIÁVEIS DE CONTROLO ---
$dados = [];

$colunas = [];
$totalLancamentos = 0;
$nomeQuestionarioSelecionado = "";

$nomeSitioSelecionado = "";
$usuario_tem_permissao = false;

// --- CARREGAMENTO DOS DADOS ---
if ($idQuestionario) {
    $id_usuario_logado = $_SESSION['usuario_id'];

    // Verifica permissão do utilizador
    $stmtPerm = $conn->prepare("SELECT COUNT(*) FROM questionario_permissoes WHERE id_questionario = ? AND id_usuario = ?");
    $stmtPerm->bind_param("ii", $idQuestionario, $id_usuario_logado);
    $stmtPerm->execute();

    $stmtPerm->bind_result($count);

    $stmtPerm->fetch();

    $stmtPerm->close();
    
    if ($count > 0) $usuario_tem_permissao = true;

    // Busca dados do questionário
    $stmt = $conn->prepare("SELECT q.nome_questionario, s.nome_sitio FROM questionarios q JOIN sitios s ON q.id_sitio = s.id_sitio WHERE q.id_questionario = ?");
    $stmt->bind_param('i', $idQuestionario);
    $stmt->execute();

    $stmt->bind_result(
        $nomeQuestionarioSelecionado,
        $nomeSitioSelecionado
    );

    $stmt->fetch();

    $stmt->close();

    // Busca campos dinâmicos
    $stmtCampos = $conn->prepare("SELECT id_campo, nome_campo, tipo_campo FROM campos_questionario WHERE id_questionario = ? ORDER BY id_campo ASC");
    $stmtCampos->bind_param('i', $idQuestionario);
    $stmtCampos->execute();

    $resultCampos = $stmtCampos->get_result();
    
    while ($campo = $resultCampos->fetch_assoc()) {

        $colunas[$campo['id_campo']] = [

            'nome' => $campo['nome_campo'],

            'tipo' => $campo['tipo_campo']
        ];
    }

    $stmtCampos->close();

    // --- CONSTRUÇÃO DINÂMICA DAS QUERIES ---
    // Conta o total de lançamentos para a paginação (com filtro de utilizador opcional)
    $sqlTotal = "SELECT COUNT(DISTINCT id_lancamento) FROM respostas_questionario WHERE id_questionario = ? AND DATE(criado_em) BETWEEN ? AND ?";
    if ($usuarioFiltro) {
        $sqlTotal .= " AND id_usuario = ?";
    }
    
    $stmtTotal = $conn->prepare($sqlTotal);
    if ($usuarioFiltro) {
        $stmtTotal->bind_param('issi', $idQuestionario, $dataInicio, $dataFim, $usuarioFiltro);
    } else {
        $stmtTotal->bind_param('iss', $idQuestionario, $dataInicio, $dataFim);
    }
    $stmtTotal->execute();
    $stmtTotal->bind_result($totalLancamentos);
    $stmtTotal->fetch();
    $stmtTotal->close();

    // Busca os lançamentos paginados (com filtro de utilizador opcional)
    $sqlLancamentos = "SELECT DISTINCT id_lancamento, criado_em, id_usuario FROM respostas_questionario WHERE id_questionario = ? AND DATE(criado_em) BETWEEN ? AND ?";
    if ($usuarioFiltro) {
        $sqlLancamentos .= " AND id_usuario = ?";
    }
    $sqlLancamentos .= " ORDER BY criado_em DESC LIMIT ? OFFSET ?";
    
    $stmtLancamentos = $conn->prepare($sqlLancamentos);
    if ($usuarioFiltro) {
        $stmtLancamentos->bind_param('issiii', $idQuestionario, $dataInicio, $dataFim, $usuarioFiltro, $limite, $offset);
    } else {
        $stmtLancamentos->bind_param('issii', $idQuestionario, $dataInicio, $dataFim, $limite, $offset);
    }
    $stmtLancamentos->execute();

    $resultLancamentos = $stmtLancamentos->get_result();

    while ($lancamento = $resultLancamentos->fetch_assoc()) {

        $idLancamento = $lancamento['id_lancamento'];

        $criadoEm = date(
            'd/m/Y H:i:s',
            strtotime($lancamento['criado_em'])
        );

        $idUsuario = $lancamento['id_usuario'];

        // Busca o nome do utilizador responsável pelo lançamento
        $stmtUser = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
        $stmtUser->bind_param('i', $idUsuario);
        $stmtUser->execute();

        $stmtUser->bind_result($nomeUsuario);

        $stmtUser->fetch();

        $stmtUser->close();

        // Busca as respostas do lançamento atual
        $stmtRespostas = $conn->prepare("SELECT id_campo, valor_resposta FROM respostas_questionario WHERE id_lancamento = ?");
        $stmtRespostas->bind_param('s', $idLancamento);
        $stmtRespostas->execute();

        $resultRespostas = $stmtRespostas->get_result();
        
        $respostas = [];

        while ($resp = $resultRespostas->fetch_assoc()) {

            $respostas[$resp['id_campo']] = $resp['valor_resposta'];
        }

        $stmtRespostas->close();

        // Agrupa tudo na array final
        $dados[] = [

            'id_lancamento' => $idLancamento,

            'criado_em' => $criadoEm,

            'usuario' => $nomeUsuario,

            'respostas' => $respostas
        ];
    }
    $stmtLancamentos->close();
}

$totalPaginas = ceil($totalLancamentos / $limite);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>

    <meta charset="UTF-8">

    <title>Verificar Respostas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link rel="icon" type="image/x-icon" href="/TCC/img/favicon.ico">
    <style>
        .main-content {
            margin-left: 250px; margin-top: 60px; width: calc(100% - 250px);
            min-height: 100vh; padding: 12px 16px; background: #f8f9fa; overflow-x: hidden;
        }
        .table-wrapper {
            background: white; border-radius: 10px; overflow: auto;
            max-height: calc(100vh - 240px); border: 1px solid #dee2e6;
        }
        .table { margin-bottom: 0; font-size: 13px; white-space: nowrap; }
        .table td { vertical-align: middle; }
        .table thead th { position: sticky; top: 0; z-index: 20; background: #212529 !important; }
        .coluna-fixa-id { position: sticky; left: 0; z-index: 15; background: white !important; min-width: 120px; }
        .coluna-fixa-usuario { position: sticky; left: 120px; z-index: 15; background: white !important; min-width: 180px; }
        thead .coluna-fixa-id, thead .coluna-fixa-usuario { z-index: 25; background: #212529 !important; }
        .filtro-card, .stats-card { border: 0; border-radius: 10px; }
        .badge-checkbox { background: #dbeafe; color: #1e3a8a; font-weight: 500; }
        .select2-container .select2-selection--single { height: 31px !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 29px !important; font-size: 13px; }
        @media (max-width: 992px) { .main-content { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

<?php include '../../php/header.php'; ?>

<?php include '../../php/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Verificar Respostas</h3>
            <small class="text-muted">Painel operacional de lançamentos</small>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card shadow-sm filtro-card mb-3">
        <div class="card-body py-2">
            <form method="GET">
                <div class="row g-2 align-items-end">
                    
                    <!-- Campo Questionário -->
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Questionário</label>
                        <select name="questionario" id="questionario" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($questionarios as $q): ?>
                                <option value="<?= $q['id_questionario'] ?>" <?= ($idQuestionario == $q['id_questionario']) ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($q['nome_sitio']) ?>] - <?= htmlspecialchars($q['nome_questionario']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Novo Campo: Utilizador -->
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Usuário</label>
                        <select name="usuario" id="usuario_filtro" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($usuariosLista as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($usuarioFiltro == $u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Datas e Botão -->
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Data Inicial</label>
                        <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= $dataInicio ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Data Final</label>
                        <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= $dataFim ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- CARDS DE ESTATÍSTICAS -->
    <?php if ($idQuestionario): ?>
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card shadow-sm stats-card">
                    <div class="card-body py-2">
                        <small class="text-muted">Questionário</small>
                        <div class="fw-semibold"><?= htmlspecialchars($nomeQuestionarioSelecionado) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm stats-card">
                    <div class="card-body py-2">
                        <small class="text-muted">Total</small>
                        <div class="fs-5 fw-bold"><?= number_format($totalLancamentos, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm stats-card">
                    <div class="card-body py-2">
                        <small class="text-muted">Página</small>
                        <div class="fs-5 fw-bold"><?= $pagina ?> / <?= max($totalPaginas, 1) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TOGGLE DE COLUNAS -->
    <?php if (!empty($colunas)): ?>
        <div class="mb-2">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#colunasCollapse">
                <i class="bi bi-layout-three-columns"></i> Colunas
            </button>
            <div class="collapse mt-2" id="colunasCollapse">
                <div class="card card-body py-2">
                    <div class="row">
                        <?php foreach ($colunas as $idCampo => $campoInfo): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input toggle-coluna" type="checkbox" checked data-coluna="coluna_<?= $idCampo ?>">
                                    <label class="form-check-label small"><?= htmlspecialchars($campoInfo['nome']) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TABELA DE DADOS -->
    <?php if (!empty($dados)): ?>
        <div class="table-wrapper shadow-sm">
            <table class="table table-bordered table-hover table-sm align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="coluna-fixa-id">ID</th>
                        <th class="coluna-fixa-usuario">Usuário</th>
                        <th>Data/Hora</th>
                        <th>Sítio</th>
                        <?php foreach ($colunas as $idCampo => $campoInfo): ?>
                            <th class="coluna_<?= $idCampo ?>"><?= htmlspecialchars($campoInfo['nome']) ?></th>
                        <?php endforeach; ?>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados as $dado): ?>
                        <tr>
                            <td class="coluna-fixa-id bg-white"><?= $dado['id_lancamento'] ?></td>
                            <td class="coluna-fixa-usuario bg-white"><?= htmlspecialchars($dado['usuario']) ?></td>
                            <td><?= $dado['criado_em'] ?></td>
                            <td><?= htmlspecialchars($nomeSitioSelecionado) ?></td>
                            <?php foreach ($colunas as $idCampo => $campoInfo): ?>
                                <td class="coluna_<?= $idCampo ?>">
                                    <?= formatarValor($dado['respostas'][$idCampo] ?? '-', $campoInfo['tipo']) ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <?php if ($usuario_tem_permissao): ?>
                                    <div class="d-flex gap-1">
                                        <a href="editar_lancamento.php?id=<?= $dado['id_lancamento'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                                        <a href="excluir_lancamento.php?id=<?= $dado['id_lancamento'] ?>&qid=<?= $idQuestionario ?>" class="btn btn-danger btn-sm" onclick="return confirm('Confirmar exclusão?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINAÇÃO -->
        <?php if ($totalPaginas > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                            <!-- A URL da paginação foi atualizada para transportar a variável &usuario -->
                            <a class="page-link" href="?questionario=<?= $idQuestionario ?>&usuario=<?= $usuarioFiltro ?>&data_inicio=<?= $dataInicio ?>&data_fim=<?= $dataFim ?>&pagina=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>

$(document).ready(function() {
    // Inicializa o Select2 para o Questionário
    $('#questionario').select2({
        placeholder: "Selecione ou pesquise",
        allowClear: true,

        width: '100%'
    });

    // Inicializa o Select2 para o novo campo de Usuário
    $('#usuario_filtro').select2({
        placeholder: "Todos os utilizadores",
        allowClear: true,
        width: '100%'
    });

    // Ocultar e exibir colunas dinamicamente
    $('.toggle-coluna').on('change', function() {
        const classe = '.' + $(this).data('coluna');
        if ($(this).is(':checked')) {
            $(classe).show();
        } else {
            $(classe).hide();
        }
    });
});

</script>

</body>
</html>