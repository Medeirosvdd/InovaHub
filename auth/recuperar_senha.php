<?php
session_start();
require '../includes/conexao.php';

// Redirecionar se já estiver logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // Validações
    if (empty($email) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        // Buscar usuário
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];

            // Atualizar último login
            $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$usuario['id']]);

            // Redirecionar
            header('Location: ../index.php');
            exit();
        } else {
            $erro = "Email ou senha incorretos.";
        }
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
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-logo {
            font-size: 2.5rem;
            color: #c4170c;
            margin-bottom: 10px;
        }

        .auth-title {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .auth-subtitle {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #c4170c;
            box-shadow: 0 0 0 3px rgba(196, 23, 12, 0.1);
        }

        .btn-auth {
            width: 100%;
            padding: 12px;
            background: #c4170c;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-auth:hover {
            background: #a6140b;
        }

        .auth-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .auth-link {
            color: #c4170c;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }

        .social-login {
            margin-top: 25px;
            text-align: center;
        }

        .social-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-social {
            flex: 1;
            padding: 10px;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-social:hover {
            background: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">⚡</div>
                <h1 class="auth-title">Acesse sua conta</h1>
                <p class="auth-subtitle">Entre para publicar e comentar</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['sucesso']) ?>
                </div>
                <?php unset($_SESSION['sucesso']); ?>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">📧 Email</label>
                    <input type="email" name="email" class="form-input" placeholder="seu@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">🔒 Senha</label>
                    <div class="password-toggle">
                        <input type="password" name="senha" id="senha" class="form-input" placeholder="Sua senha" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">🚀 Entrar</button>
            </form>

            <!-- Login Social (Futuro) -->
            <div class="social-login">
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Ou entre com</p>
                <div class="social-buttons">
                    <button class="btn-social" disabled>Google</button>
                    <button class="btn-social" disabled>GitHub</button>
                </div>
            </div>

            <div class="auth-links">
                <p>
                    Não tem conta?
                    <a href="cadastro.php" class="auth-link">Cadastre-se aqui</a>
                </p>
                <p style="margin-top: 10px;">
                    <a href="recuperar_senha.php" class="auth-link">Esqueceu sua senha?</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const toggleBtn = document.querySelector('.toggle-password');

            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                senhaInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Focar no primeiro campo
        document.querySelector('input[name="email"]').focus();
    </script>
</body>

</html>