<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!$usuario) {
    header('Location: ../auth/login.php');
    exit();
}

$erro = '';
$sucesso = '';

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validações
    $erros = [];

    if (empty($nome)) {
        $erros[] = "O nome é obrigatório.";
    }

    if (empty($email)) {
        $erros[] = "O email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Por favor, insira um email válido.";
    } else {
        // Verificar se email já existe (exceto para o próprio usuário)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $usuario['id']]);
        if ($stmt->fetch()) {
            $erros[] = "Este email já está em uso por outro usuário.";
        }
    }

    // Validações de senha (se o usuário quiser alterar)
    if (!empty($nova_senha)) {
        if (empty($senha_atual)) {
            $erros[] = "Para alterar a senha, informe sua senha atual.";
        } elseif (!password_verify($senha_atual, $usuario['senha'])) {
            $erros[] = "Senha atual incorreta.";
        } elseif (strlen($nova_senha) < 6) {
            $erros[] = "A nova senha deve ter pelo menos 6 caracteres.";
        } elseif ($nova_senha !== $confirmar_senha) {
            $erros[] = "As novas senhas não coincidem.";
        }
    }

    // Se não há erros, atualizar o usuário
    if (empty($erros)) {
        try {
            if (!empty($nova_senha)) {
                // Atualizar com nova senha
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET nome = ?, email = ?, senha = ?, atualizado_em = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $email, $senha_hash, $usuario['id']]);
                $sucesso = "Perfil e senha atualizados com sucesso!";
            } else {
                // Atualizar apenas dados básicos
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET nome = ?, email = ?, atualizado_em = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $email, $usuario['id']]);
                $sucesso = "Perfil atualizado com sucesso!";
            }

            // Atualizar dados na sessão
            $_SESSION['usuario_nome'] = $nome;

            // Recarregar dados do usuário
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            $usuario = $stmt->fetch();
        } catch (Exception $e) {
            $erro = "Erro ao atualizar perfil: " . $e->getMessage();
        }
    } else {
        $erro = implode("<br>", $erros);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - InovaHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #c4170c;
            --primary-dark: #a6140b;
            --secondary: #2c3e50;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        body {
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Main Content */
        main {
            padding: 40px 0;
            min-height: calc(100vh - 200px);
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Edit Profile Layout */
        .edit-profile {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .edit-profile {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Sidebar */
        .profile-sidebar {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            height: fit-content;
        }

        .user-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 20px;
            box-shadow: 0 8px 25px rgba(196, 23, 12, 0.3);
        }

        .profile-sidebar h2 {
            color: var(--secondary);
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .user-type {
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-info {
            text-align: left;
            margin: 20px 0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            color: var(--secondary);
            font-weight: 500;
        }

        /* Form Section */
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .form-header h2 {
            color: var(--secondary);
            font-size: 1.5rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 480px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(196, 23, 12, 0.1);
        }

        .form-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
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
            font-size: 1.1rem;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(196, 23, 12, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(196, 23, 12, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        @media (max-width: 480px) {
            .form-actions {
                flex-direction: column;
            }
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-weight: 500;
            border-left: 4px solid;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }

        /* Password Strength */
        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
        }

        .strength-weak {
            color: var(--danger);
        }

        .strength-medium {
            color: var(--warning);
        }

        .strength-strong {
            color: var(--success);
        }

        /* Danger Zone */
        .danger-zone {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
            border: 2px solid #f8d7da;
        }

        .danger-zone h3 {
            color: var(--danger);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .danger-zone p {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>✏️ Editar Perfil</h1>
            <p>Atualize suas informações pessoais</p>
        </div>

        <div class="edit-profile">
            <!-- Sidebar do Perfil -->
            <div class="profile-sidebar">
                <div class="user-avatar-large">
                    <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                </div>
                <h2><?= htmlspecialchars($usuario['nome']) ?></h2>
                <span class="user-type">
                    <?= $usuario['tipo'] === 'admin' ? '👑 Administrador' : ($usuario['tipo'] === 'editor' ? '✏️ Editor' : '👤 Usuário') ?>
                </span>

                <div class="profile-info">
                    <div class="info-item">
                        <span class="info-label">📧 Email:</span>
                        <span class="info-value"><?= $usuario['email'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">📅 Membro desde:</span>
                        <span class="info-value"><?= date('d/m/Y', strtotime($usuario['criado_em'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">🔄 Última atualização:</span>
                        <span class="info-value">
                            <?= $usuario['atualizado_em'] ? date('d/m/Y H:i', strtotime($usuario['atualizado_em'])) : 'Nunca' ?>
                        </span>
                    </div>
                </div>

                <a href="dashboard.php" class="back-btn">↩️ Voltar</a>
            </div>

            <!-- Formulário de Edição -->
            <div class="form-section">
                <div class="form-header">
                    <h2>Informações Pessoais</h2>
                </div>

                <!-- Alert Messages -->
                <?php if ($sucesso): ?>
                    <div class="alert alert-success">✅ <?= $sucesso ?></div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alert alert-danger">❌ <?= $erro ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nome">👤 Nome Completo *</label>
                            <input type="text"
                                id="nome"
                                name="nome"
                                class="form-control"
                                value="<?= htmlspecialchars($usuario['nome']) ?>"
                                required
                                placeholder="Seu nome completo">
                        </div>

                        <div class="form-group">
                            <label for="email">📧 Email *</label>
                            <input type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($usuario['email']) ?>"
                                required
                                placeholder="seu@email.com">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <h3 style="margin-bottom: 20px; color: var(--secondary); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            🔒 Alterar Senha
                        </h3>
                        <p style="color: #666; margin-bottom: 20px; font-size: 0.9rem;">
                            Deixe os campos de senha em branco se não quiser alterar.
                        </p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="senha_atual">🔑 Senha Atual</label>
                            <div class="password-toggle">
                                <input type="password"
                                    id="senha_atual"
                                    name="senha_atual"
                                    class="form-control"
                                    placeholder="Sua senha atual">
                                <button type="button" class="toggle-password" onclick="togglePassword('senha_atual')">👁️</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nova_senha">🆕 Nova Senha</label>
                            <div class="password-toggle">
                                <input type="password"
                                    id="nova_senha"
                                    name="nova_senha"
                                    class="form-control"
                                    placeholder="Mínimo 6 caracteres"
                                    oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="toggle-password" onclick="togglePassword('nova_senha')">👁️</button>
                            </div>
                            <div id="password-strength" class="password-strength"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirmar_senha">✅ Confirmar Nova Senha</label>
                            <div class="password-toggle">
                                <input type="password"
                                    id="confirmar_senha"
                                    name="confirmar_senha"
                                    class="form-control"
                                    placeholder="Digite novamente"
                                    oninput="checkPasswordMatch()">
                                <button type="button" class="toggle-password" onclick="togglePassword('confirmar_senha')">👁️</button>
                            </div>
                            <div id="password-match" class="password-strength"></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                    </div>
                </form>

                <!-- Danger Zone -->
                <div class="danger-zone">
                    <h3>⚠️ Zona de Perigo</h3>
                    <p>Esta ação não pode ser desfeita. Isso excluirá permanentemente sua conta e todos os dados associados.</p>
                    <a href="excluir_conta.php" class="btn btn-danger" onclick="return confirm('ATENÇÃO: Esta ação é irreversível! Tem certeza que deseja excluir sua conta permanentemente?')">
                        🗑️ Excluir Minha Conta
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

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

            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            const password = document.getElementById('nova_senha').value;
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

        // Confirmação antes de sair da página com alterações não salvas
        let formChanged = false;
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input');

        inputs.forEach(input => {
            const originalValue = input.value;
            input.addEventListener('change', () => {
                formChanged = input.value !== originalValue;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'Você tem alterações não salvas. Tem certeza que deseja sair?';
            }
        });

        form.addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>

</html>