<?php
session_start();
require 'includes/conexao.php';
require 'includes/funcoes.php';

$usuario = usuarioLogado($pdo);

$sql = "SELECT n.*, u.nome AS autor_nome 
        FROM noticias n
        JOIN usuarios u ON u.id = n.autor
        ORDER BY data DESC";

$noticias = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>InovaHub - Tecnologia e Inovação</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header>
        <h1>InovaHub</h1>
        <nav>
            <a href="index.php">Início</a>

            <?php if ($usuario): ?>
                <a href="usuario/dashboard.php">Dashboard</a>
                <a href="noticias/nova_noticia.php">Publicar</a>
                <?php if (ehAdmin($usuario)): ?>
                    <a href="admin/index.php">Admin</a>
                <?php endif; ?>
                <a href="auth/logout.php">Sair (<?= htmlspecialchars($usuario['nome']) ?></a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/cadastro.php">Cadastrar</a>
            <?php endif; ?>

            <button id="btn-theme">Tema</button>
        </nav>
    </header>

    <main>
        <h2>Últimas notícias de Tecnologia e Inovação</h2>

        <?php foreach ($noticias as $n): ?>
            <div class="card">
                <?php if (!empty($n['imagem'])): ?>
                    <img src="<?= htmlspecialchars($n['imagem']) ?>" class="card-thumbnail" alt="">
                <?php else: ?>
                    <img src="assets/img/sem-imagem.png" class="card-thumbnail" alt="">
                <?php endif; ?>

                <div class="card-info">
                    <h3><a href="noticia.php?id=<?= $n['id'] ?>"><?= htmlspecialchars($n['titulo']) ?></a></h3>
                    <p class="meta">Por <?= htmlspecialchars($n['autor_nome']) ?> • <?= date("d/m/Y H:i", strtotime($n['data'])) ?></p>
                    <p class="card-text"><?= resumoTexto($n['noticia'], 200) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </main>

    <script src="assets/js/theme.js"></script>
</body>
</html>
