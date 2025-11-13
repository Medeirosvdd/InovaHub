<?php
require '../includes/verifica_login.php';
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = $_POST['titulo'];
    $texto  = $_POST['noticia'];
    $img    = $_POST['imagem'] ?? null;

    if ($titulo === '' || $texto === '') {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO noticias (titulo,noticia,autor,imagem) VALUES (?,?,?,?)");
        $stmt->execute([$titulo, $texto, $usuario['id'], $img]);

        header("Location: ../usuario/dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Nova Notícia - InovaHub</title>
</head>

<body>

    <h1>Publicar nova notícia - InovaHub</h1>

    <form method="post">
        <label>Título</label>
        <input type="text" name="titulo" required>

        <label>Imagem (URL) – Opcional</label>
        <input type="text" name="imagem">

        <label>Notícia</label>
        <textarea name="noticia" rows="8" required></textarea>

        <button type="submit">Publicar</button>
    </form>

    <p><?= $erro ?></p>

</body>

</html>