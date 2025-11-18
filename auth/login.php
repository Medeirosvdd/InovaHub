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
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_tipo'] = $user['tipo'];
        header("Location: ../usuario/dashboard.php");
        exit();
    } else {
        $erro = "E-mail ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InovaHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <h1>Login - InovaHub</h1>

        <?php if ($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>

        <form method="post" class="login-form">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required>
            </div>

            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <p class="cadastro-link">
            Não tem conta? <a href="cadastro.php">Cadastre-se</a>
        </p>
    </div>

    <style>
        body {
            background: #f5f5f5;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .login-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }

        .form-group input:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: #0056b3;
        }

        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .cadastro-link {
            text-align: center;
            margin-top: 1rem;
        }

        .cadastro-link a {
            color: #007bff;
            text-decoration: none;
        }

        .cadastro-link a:hover {
            text-decoration: underline;
        }
    </style>
</body>

</html>