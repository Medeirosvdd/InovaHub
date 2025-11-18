<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!$usuario) {
    header('Location: ../auth/login.php');
    exit();
}

// Se for editor ou admin, redireciona
if (podePublicar($usuario)) {
    if (ehAdmin($usuario)) {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../editor/index.php');
    }
    exit();
}

// Só continua aqui se for usuário comum
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - InovaHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <h1>👤 Minha Conta</h1>

        <div class="user-profile">
            <div class="profile-card">
                <h2>Bem-vindo, <?= htmlspecialchars($usuario['nome']) ?>!</h2>
                <p>Email: <?= $usuario['email'] ?></p>
                <p>Tipo de conta: <strong>Usuário</strong></p>
            </div>

            <div class="user-actions">
                <a href="editar_perfil.php" class="btn">✏️ Editar Perfil</a>
                <a href="minhas_curtidas.php" class="btn">❤️ Notícias Curtidas</a>
                <a href="meus_comentarios.php" class="btn">💬 Meus Comentários</a>
            </div>

            <div class="upgrade-info">
                <h3>💡 Quer publicar notícias?</h3>
                <p>Entre em contato com os administradores para se tornar um editor.</p>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>

</html>