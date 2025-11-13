<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!$usuario) {
    header('Location: ../auth/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: ../usuario/dashboard.php');
    exit();
}

$id = (int) $_GET['id'];

$sql = "SELECT * FROM noticias WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$noticia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$noticia) {
    header('Location: ../usuario/dashboard.php');
    exit();
}

if ($noticia['autor'] != $usuario['id'] && !ehAdmin($usuario)) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($noticia['imagem']) && file_exists("../" . $noticia['imagem'])) {
        unlink("../" . $noticia['imagem']);
    }

    $sql = "DELETE FROM noticias WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    header("Location: ../usuario/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Excluir Notícia - InovaHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <header>
        <h1>InovaHub</h1>
        <nav>
            <a href="../index.php">Início</a>
            <a href="../usuario/dashboard.php">Dashboard</a>
            <a href="../auth/logout.php">Sair</a>
            <button id="btn-theme">Tema</button>
        </nav>
    </header>

    <main>
        <h2>Excluir Notícia</h2>

        <p>Tem certeza que deseja excluir a notícia abaixo?</p>

        <div class="dashboard-item">
            <strong><?= htmlspecialchars($noticia['titulo']) ?></strong>
            <br>
            Publicada em: <?= date("d/m/Y H:i", strtotime($noticia['data'])) ?>
        </div>

        <form method="POST">
            <input type="submit" value="Excluir definitivamente" style="background:#ff4444;">
        </form>

        <br>
        <a href="../usuario/dashboard.php">Cancelar</a>

    </main>

    <script src="../assets/js/theme.js"></script>
</body>

</html>