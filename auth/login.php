<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $user = buscarUsuarioPorEmail($pdo, $email);

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        header("Location: ../usuario/dashboard.php");
        exit();
    } else {
        $erro = "E-mail ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Login - InovaHub</title>
</head>

<body>

    <h1>Login - InovaHub</h1>

    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Senha</label>
        <input type="password" name="senha" required>

        <button type="submit">Entrar</button>
    </form>

    <p><?= $erro ?></p>

</body>

</html>