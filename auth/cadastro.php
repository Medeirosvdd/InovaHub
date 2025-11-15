<?php
session_start();
require '../includes/conexao.php';

// Redirecionar se já estiver logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit();
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Por favor, insira um email válido.";
    } else {
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $erro = "Este email já está cadastrado.";
        } else {
            // Criar usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, senha, tipo) 
                    VALUES (?, ?, ?, 'usuario')
                ");
                $stmt->execute([$nome, $email, $senha_hash]);
                
                $_SESSION['sucesso'] = "Cadastro realizado com sucesso! Faça login para continuar.";
                header('Location: login.php');
                exit();
                
            } catch (PDOException $e) {
                $erro = "Erro ao criar conta. Tente novamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - InovaHub</title>
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
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
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
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
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
        
        .terms {
            font-size: 0.8rem;
            color: #666;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">⚡</div>
                <h1 class="auth-title">Crie sua conta</h1>
                <p class="auth-subtitle">Junte-se à comunidade InovaHub</p>
            </div>
            
            <?php if ($erro): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">👤 Nome completo</label>
                    <input type="text" name="nome" class="form-input" placeholder="Seu nome completo" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">📧 Email</label>
                    <input type="email" name="email" class="form-input" placeholder="seu@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">🔒 Senha</label>
                    <div class="password-toggle">
                        <input type="password" name="senha" id="senha" class="form-input" placeholder="Mínimo 6 caracteres" required oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="toggle-password" onclick="togglePassword('senha')">👁️</button>
                    </div>
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">🔒 Confirmar senha</label>
                    <div class="password-toggle">
                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-input" placeholder="Digite novamente" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmar_senha')">👁️</button>
                    </div>
                    <div id="password-match" class="password-strength"></div>
                </div>
                
                <button type="submit" class="btn-auth">🚀 Criar conta</button>
                
                <div class="terms">
                    Ao se cadastrar, você concorda com nossos 
                    <a href="../termos.php" style="color: #c4170c;">Termos de Uso</a> 
                    e 
                    <a href="../privacidade.php" style="color: #c4170c;">Política de Privacidade</a>.
                </div>
            </form>
            
            <div class="auth-links">
                <p>
                    Já tem conta? 
                    <a href="login.php" class="auth-link">Faça login aqui</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const toggleBtn = input.parentNode.querySelector('.toggle-password');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                input.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        function checkPasswordStrength(password) {
            const strengthElement = document.getElementById('password-strength');
            let strength = '';
            let className = '';
            
            if (password.length === 0) {
                strength = '';
            } else if (password.length < 6) {
                strength = 'Senha muito curta';
                className = 'strength-weak';
            } else if (password.length < 8) {
                strength = 'Senha média';
                className = 'strength-medium';
            } else if (/[A-Z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
                strength = 'Senha forte';
                className = 'strength-strong';
            } else {
                strength = 'Senha boa';
                className = 'strength-medium';
            }
            
            strengthElement.textContent = strength;
            strengthElement.className = 'password-strength ' + className;
            
            // Verificar se as senhas coincidem
            checkPasswordMatch();
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('senha').value;
            const confirmPassword = document.getElementById('confirmar_senha').value;
            const matchElement = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchElement.textContent = '';
            } else if (password === confirmPassword) {
                matchElement.textContent = 'Senhas coincidem ✓';
                matchElement.className = 'password-strength strength-strong';
            } else {
                matchElement.textContent = 'Senhas não coincidem ✗';
                matchElement.className = 'password-strength strength-weak';
            }
        }
        
        // Adicionar event listener para o campo de confirmação
        document.getElementById('confirmar_senha').addEventListener('input', checkPasswordMatch);
        
        // Focar no primeiro campo
        document.querySelector('input[name="nome"]').focus();
    </script>
</body>
</html>