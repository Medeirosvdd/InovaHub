<?php
session_start();
require 'includes/conexao.php';
require 'includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = (int) $_GET['id'];

$sql = "SELECT n.*, u.nome AS autor_nome 
        FROM noticias n
        JOIN usuarios u ON u.id = n.autor
        WHERE n.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$noticia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$noticia) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario']) && $usuario) {
    $comentario = trim($_POST['comentario']);
    if ($comentario !== '') {
        $sql = "INSERT INTO comentarios (noticia_id, usuario_id, comentario) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $usuario['id'], $comentario]);
        header("Location: noticia.php?id=" . $id);
        exit();
    }
}

$sqlComentarios = "SELECT c.*, u.nome AS usuario_nome 
                   FROM comentarios c
                   JOIN usuarios u ON u.id = c.usuario_id
                   WHERE c.noticia_id = ?
                   ORDER BY c.data DESC";
$stmt = $pdo->prepare($sqlComentarios);
$stmt->execute([$id]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($noticia['titulo']) ?> - InovaHub</title>
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
    <div class="noticia-container">
        <h2 class="noticia-title"><?= htmlspecialchars($noticia['titulo']) ?></h2>
        <p class="noticia-meta">
            Por <?= htmlspecialchars($noticia['autor_nome']) ?> •
            <?= date("d/m/Y H:i", strtotime($noticia['data'])) ?>
        </p>

        <?php if (!empty($noticia['imagem'])): ?>
            <img src="<?= htmlspecialchars($noticia['imagem']) ?>" alt="" class="imagem-noticia">
        <?php endif; ?>

        <p class="noticia-texto"><?= nl2br(htmlspecialchars($noticia['noticia'])) ?></p>
    </div>

    <div class="comentarios">
        <h3>Comentários</h3>

        <?php if ($usuario): ?>
            <form method="post">
                <label for="comentario">Deixe seu comentário:</label>
                <textarea name="comentario" id="comentario" rows="4" required></textarea>
                <input type="submit" value="Comentar">
            </form>
        <?php else: ?>
            <p>Faça <a href="auth/login.php">login</a> para comentar.</p>
        <?php endif; ?>

        <?php foreach ($comentarios as $c): ?>
            <div class="comentario">
                <div class="comentario-cabecalho">
                    <?= htmlspecialchars($c['usuario_nome']) ?>
                    • <?= date("d/m/Y H:i", strtotime($c['data'])) ?>
                </div>
                <div class="comentario-texto">
                    <?= nl2br(htmlspecialchars($c['comentario'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<script src="assets/js/theme.js"></script>
</body>
</html>
