<?php
require_once '../../php/config.php';
require_once '../../php/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

echo "<h2>Iniciando sincronização de colaboradores...</h2>";

$arquivo = '../../planilhas/colaboradores.xlsx';

if (!file_exists($arquivo)) {
    die("Arquivo colaboradores.xlsx não encontrado em /planilhas.");
}

$xlsx = SimpleXLSX::parse($arquivo);

if (!$xlsx) {
    die("Erro ao ler o arquivo: " . SimpleXLSX::parseError());
}

$linhasInseridas = 0;
$linhasAtualizadas = 0;

foreach ($sheet as $i => $linha) {
    if ($i < 6) continue; // Pular cabeçalho (até a linha 6)

    // Colunas conforme planilha
    $codigo          = trim($linha[0]);
    $centro_custo    = trim($linha[1]);
    $nome            = trim($linha[3]);
    $data_nascimento = !empty($linha[5]) ? date('Y-m-d', strtotime(str_replace('/', '-', $linha[5]))) : null;
    $data_admissao   = !empty($linha[6]) ? date('Y-m-d', strtotime(str_replace('/', '-', $linha[6]))) : null;
    $data_demissao   = !empty($linha[7]) ? date('Y-m-d', strtotime(str_replace('/', '-', $linha[7]))) : null;
    $status          = strtoupper(trim($linha[9])) == 'ATIVO' ? 'ATIVO' : 'DESLIGADO';
    $sitio           = trim($linha[10]);
    $setor           = trim($linha[11]);

    if (empty($codigo)) continue; // Ignorar linhas sem código

    // Verificar se já existe
    $query = $conn->prepare("SELECT id FROM usuarios WHERE codigo = ?");
    $query->bind_param("s", $codigo);
    $query->execute();
    $query->store_result();

    if ($query->num_rows > 0) {
        // Atualizar dados
        $sql = "UPDATE usuarios SET nome = ?, data_nascimento = ?, data_admissao = ?, data_demissao = ?, status = ?, sitio = ?, setor = ?, centro_custo = ? WHERE codigo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $nome, $data_nascimento, $data_admissao, $data_demissao, $status, $sitio, $setor, $centro_custo, $codigo);
        $stmt->execute();
        $linhasAtualizadas++;
    } else {
        // Inserir novo usuário
        $sql = "INSERT INTO usuarios (codigo, nome, data_nascimento, data_admissao, data_demissao, status, sitio, setor, centro_custo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $codigo, $nome, $data_nascimento, $data_admissao, $data_demissao, $status, $sitio, $setor, $centro_custo);
        $stmt->execute();
        $linhasInseridas++;
    }

    $query->free_result();
}

echo "<br>Sincronização concluída.<br>";
echo "Linhas inseridas: $linhasInseridas<br>";
echo "Linhas atualizadas: $linhasAtualizadas<br>";
?>