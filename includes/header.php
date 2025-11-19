<?php if (usuarioLogado($pdo) && podePublicar(usuarioLogado($pdo))): ?>
    <li><a href="admin/upload_noticia.php">📝 Publicar Notícia</a></li>
    <li><a href="admin/comentarios.php">🛡️ Moderar</a></li>
<?php endif; ?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InovaHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <a href="index.php">INOVAHUB</a>
            </div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="noticias.php">Notícias</a>
                <a href="buscar.php">Buscar</a>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="usuario/dashboard.php">Dashboard</a>
                    <a href="auth/logout.php">Sair</a>
                <?php else: ?>
                    <a href="auth/login.php">Login</a>
                    <a href="auth/cadastro.php">Cadastrar</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>