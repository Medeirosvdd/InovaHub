<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!$usuario) {
    header('Location: ../auth/login.php');
    exit();
}

$idUsuario = $usuario['id'];

$sql = "SELECT * FROM noticias WHERE autor = ? ORDER BY data DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$idUsuario]);
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?= htmlspecialchars($usuario['nome']) ?> | InovaHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header>
    <h1>InovaHub</h1>
    <nav>
        <a href="../index.php">Início</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="../noticias/nova_noticia.php">Publicar</a>

        <?php if (ehAdmin($usuario)): ?>
            <a href="../admin/index.php">Admin</a>
        <?php endif; ?>

        <a href="../auth/logout.php">Sair (<?= htmlspecialchars($usuario['nome']) ?>)</a>
        <button id="btn-theme">Tema</button>
    </nav>
</header>

<main>

    <h2>Minhas Notícias</h2>

    <a href="../noticias/nova_noticia.php">
        <button style="margin-bottom: 20px;">+ Nova Notícia</button>
    </a>

    <?php if (count($noticias) === 0): ?>
        <p>Você ainda não publicou nenhuma notícia.</p>

    <?php else: ?>

        <?php foreach ($noticias as $n): ?>
            <div class="card" style="align-items:center;">

                <?php if (!empty($n['imagem'])): ?>
                    <img src="../<?= htmlspecialchars($n['imagem']) ?>" class="card-thumbnail" alt="">
                <?php else: ?>
                    <img src="../assets/img/sem-imagem.png" class="card-thumbnail" alt="">
                <?php endif; ?>

                <div class="card-info">
                    <h3><?= htmlspecialchars($n['titulo']) ?></h3>

                    <p class="meta">
                        Publicado em <?= date("d/m/Y H:i", strtotime($n['data'])) ?>
                    </p>

                    <p class="card-text">
                        <?= resumoTexto($n['noticia'], 160) ?>
                    </p>

                    <br>

                    <a href="../noticias/editar_noticia.php?id=<?= $n['id'] ?>">
                        <button style="background:#00C3FF;color:#000;">Editar</button>
                    </a>

                    <a href="../noticias/excluir_noticia.php?id=<?= $n['id'] ?>">
                        <button style="background:#ff5050;color:#fff;margin-left:10px;">Excluir</button>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</main>

<script src="../assets/js/theme.js"></script>
</body>

</html>
