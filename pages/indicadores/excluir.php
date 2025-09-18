<?php
session_start();
include '../../php/config.php';

// Validação: apenas admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TCC/inicio.php');
    exit();
}

$id_indicador = $_GET['id'] ?? null;

if (!$id_indicador) {
    die("ID do indicador não fornecido.");
}

// Excluir primeiro as permissões vinculadas (chave estrangeira)
$conn->query("DELETE FROM indicador_roles WHERE id_indicador = $id_indicador");

// Excluir o indicador
$conn->query("DELETE FROM indicadores WHERE id_indicador = $id_indicador");

header("Location: gerenciar.php");
exit();