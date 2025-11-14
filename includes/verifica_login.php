<?php

/**
 * Verifica se usuário está logado
 */

session_start();

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['erro'] = "Você precisa fazer login para acessar esta página.";
    header('Location: ../auth/login.php');
    exit();
}

// Verificar se usuário ainda existe e está ativo
require 'conexao.php';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND ativo = 1");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit();
}
