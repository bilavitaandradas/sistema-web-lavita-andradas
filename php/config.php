<?php

setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

// Configurações do banco de dados
$host = 'localhost';
$usuario = 'root'; // Usuário padrão do XAMPP
$senha = ''; // Senha padrão do XAMPP (geralmente vazia)
$banco = 'la_vita_andradas';


// Criar conexão
$conn = new mysqli($host, $usuario, $senha, $banco);


// Verificar conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Força UTF-8 para toda comunicação com o banco
$conn->set_charset("utf8mb4");

$conn->query("SET time_zone='-03:00'");

?>