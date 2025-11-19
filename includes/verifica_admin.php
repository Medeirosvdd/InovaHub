<?php
// admin/verifica_admin.php
require_once '../conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// Verificar se é admin (adaptado para sua estrutura)
// Supondo que você tenha uma coluna 'is_admin' na tabela usuarios
$sql = "SELECT is_admin FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario || !$usuario['is_admin']) {
    header('Location: ../dashboard.php');
    exit();
}
