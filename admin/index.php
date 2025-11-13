<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!ehAdmin($usuario)) {
    header('Location: ../index.php');
    exit();
}

$sqlUsers = "SELECT id, nome, email, is_admin, criado_em FROM usuarios ORDER BY criado_em DESC";
$usuarios = $pdo->query($sqlUsers)->fetchAll(PDO::FETCH_ASSOC);

$sqlNoticias = "SELECT n.id, n.titulo, u.nome AS autor_nome, n.data
                FROM noticias n
                JOIN usuarios u ON u.id = n.autor
                ORDER BY n.data DESC";
$noticias = $pdo->query($sqlNoticias)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin - InovaHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
    <h1>Área Admin - InovaHub</h1>
    <nav>
        <a href="../index.php">Início</a>
        <a href="../usuario/dashboard.php">Dashboard</a>
        <a href="../auth/logout.php">Sair</a>
        <button id="btn-theme">Tema</button>
    </nav>
</header>

<main>
    <h2>Usuários</h2>

    <?php foreach ($usuarios as $u): ?>
        <div class="dashboard-item">
            <strong><?= htmlspecialchars($u['nome']) ?></strong>
            (<?= htmlspecialchars($u['email']) ?>)
            <?= $u['is_admin'] ? '• Admin' : '' ?>
        </div>
    <?php endforeach; ?>

    <h2>Notícias</h2>

    <?php foreach ($noticias as $n): ?>
        <div class="dashboard-item">
            <strong><?= htmlspecialchars($n['titulo']) ?></strong>
            • <?= htmlspecialchars($n['autor_nome']) ?>
            • <?= date("d/m/Y H:i", strtotime($n['data'])) ?>
        </div>
    <?php endforeach; ?>
</main>

<script src="../assets/js/theme.js"></script>
</body>
</html>
