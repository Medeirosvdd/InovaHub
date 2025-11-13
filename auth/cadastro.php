<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $senha2 = $_POST['senha2'];

    if ($senha !== $senha2) {
        $erro = "Senhas não conferem.";
    } elseif (buscarUsuarioPorEmail($pdo, $email)) {
        $erro = "Já existe usuário com esse e-mail.";
    } else {
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome,email,senha) VALUES (?,?,?)");
        $stmt->execute([$nome,$email,$hash]);

        $_SESSION['usuario_id'] = $pdo->lastInsertId();
        header("Location: ../usuario/dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cadastro - InovaHub</title>
</head>
<body>

<h1>Criar Conta - InovaHub</h1>

<form method="post">
    <label>Nome</label>
    <input type="text" name="nome" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Senha</label>
    <input type="password" name="senha" required>

    <label>Confirmar Senha</label>
    <input type="password" name="senha2" required>

    <button type="submit">Cadastrar</button>
</form>

<p><?= $erro ?></p>

</body>
</html>
