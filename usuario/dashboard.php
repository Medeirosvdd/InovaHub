<?php
require '../includes/verifica_login.php';
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

$stmt = $pdo->prepare("SELECT * FROM noticias WHERE autor = ? ORDER BY data DESC");
$stmt->execute([$usuario['id']]);
$minhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <title>Dashboard - InovaHub</title>
</head>

<body>

    <h1>Bem-vindo, <?= $usuario['nome'] ?></h1>

    <a href="../noticias/nova_noticia.php">Nova notícia</a>

    <h2>Suas notícias</h2>

    <?php foreach ($minhas as $n): ?>
        <p>
            <strong><?= $n['titulo'] ?></strong><br>
            <a href="../noticia.php?id=<?= $n['id'] ?>">Ver</a> |
            <a href="../noticias/editar_noticia.php?id=<?= $n['id'] ?>">Editar</a> |
            <a href="../noticias/excluir_noticia.php?id=<?= $n['id'] ?>">Excluir</a>
        </p>
    <?php endforeach; ?>

</body>

</html>