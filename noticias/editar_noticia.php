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
    $titulo = trim($_POST['titulo']);
    $texto = trim($_POST['noticia']);

    if ($titulo !== "" && $texto !== "") {

        $caminhoImagem = $noticia['imagem'];

        if (!empty($_FILES['imagem']['name'])) {
            $nomeTmp = $_FILES['imagem']['tmp_name'];
            $nomeFinal = uniqid() . "_" . $_FILES['imagem']['name'];
            $destino = "../uploads/noticias/" . $nomeFinal;

            move_uploaded_file($nomeTmp, $destino);

            $caminhoImagem = "uploads/noticias/" . $nomeFinal;
        }

        $sql = "UPDATE noticias SET titulo = ?, noticia = ?, imagem = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $texto, $caminhoImagem, $id]);

        header("Location: ../noticia.php?id=$id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Notícia - InovaHub</title>
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
        <h2>Editar Notícia</h2>

        <form method="POST" enctype="multipart/form-data">
            <label>Título</label>
            <input type="text" name="titulo" value="<?= htmlspecialchars($noticia['titulo']) ?>" required>

            <label>Texto da notícia</label>
            <textarea name="noticia" rows="8" required><?= htmlspecialchars($noticia['noticia']) ?></textarea>

            <label>Imagem atual</label><br>
            <?php if (!empty($noticia['imagem'])): ?>
                <img src="../<?= $noticia['imagem'] ?>" style="max-width: 250px; border-radius: 4px; margin: 10px 0;">
            <?php else: ?>
                <p>Sem imagem</p>
            <?php endif; ?>

            <label>Alterar imagem (opcional)</label>
            <input type="file" name="imagem">

            <input type="submit" value="Salvar alterações">
        </form>

    </main>

    <script src="../assets/js/theme.js"></script>
</body>

</html>