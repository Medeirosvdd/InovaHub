<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!$usuario || !ehAdmin($usuario)) {
    header('Location: ../index.php');
    exit();
}

// Verificar se o ID foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['erro'] = "ID do usuário não especificado.";
    header('Location: usuarios.php');
    exit();
}

$user_id = intval($_GET['id']);

// Buscar informações do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user_edit = $stmt->fetch();

if (!$user_edit) {
    $_SESSION['erro'] = "Usuário não encontrado.";
    header('Location: usuarios.php');
    exit();
}

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $tipo = $_POST['tipo'];
    $status = $_POST['status'];
    
    // Validar dados
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório.";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Email inválido.";
    } else {
        // Verificar se email já existe (exceto para o próprio usuário)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $erros[] = "Este email já está em uso por outro usuário.";
        }
    }
    
    // Se não há erros, atualizar o usuário
    if (empty($erros)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nome = ?, email = ?, tipo = ?, status = ?, atualizado_em = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$nome, $email, $tipo, $status, $user_id]);
            
            $_SESSION['sucesso'] = "Usuário atualizado com sucesso!";
            header('Location: usuarios.php');
            exit();
            
        } catch (Exception $e) {
            $erros[] = "Erro ao atualizar usuário: " . $e->getMessage();
        }
    }
}

// Mensagens de feedback
if (isset($_SESSION['sucesso'])) {
    $mensagem_sucesso = $_SESSION['sucesso'];
    unset($_SESSION['sucesso']);
}

if (isset($_SESSION['erro'])) {
    $mensagem_erro = $_SESSION['erro'];
    unset($_SESSION['erro']);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Admin</title>
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
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--secondary) 0%, #1a2530 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 25px;
            background: var(--primary);
            text-align: center;
            border-bottom: 3px solid var(--primary-dark);
        }

        .sidebar-header h1 {
            font-size: 22px;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .sidebar-header p {
            font-size: 13px;
            opacity: 0.9;
        }

        .nav-links {
            padding: 20px 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--primary);
            transform: translateX(5px);
        }

        .nav-links a.active {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: var(--primary);
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid var(--primary);
        }

        .header h1 {
            color: var(--secondary);
            font-size: 28px;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(196, 23, 12, 0.3);
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left-color: var(--warning);
        }

        /* Form Section */
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .form-header h2 {
            color: var(--secondary);
            font-size: 22px;
            font-weight: 700;
        }

        .user-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 32px;
            box-shadow: 0 8px 20px rgba(196, 23, 12, 0.3);
            margin: 0 auto 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
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
            transform: translateY(-2px);
        }

        .form-control:disabled {
            background: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        .form-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
        }

        /* User Info Card */
        .user-info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--secondary);
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid;
        }

        .badge-admin {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .badge-editor {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }

        .badge-usuario {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        .badge-ativo {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .badge-inativo {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .user-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>🚀 InovaHub</h1>
                <p>Painel Administrativo</p>
            </div>
            <nav class="nav-links">
                <a href="index.php">📊 Dashboard</a>
                <a href="noticias.php">📰 Gerenciar Notícias</a>
                <a href="usuarios.php" class="active">👥 Gerenciar Usuários</a>
                <a href="categorias.php">📂 Gerenciar Categorias</a>
                <a href="comentarios.php">💬 Moderar Comentários</a>
                <a href="../index.php">🏠 Voltar ao Site</a>
                <a href="../auth/logout.php">🚪 Sair</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>✏️ Editar Usuário</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                    </div>
                    <span>Olá, <?= $usuario['nome'] ?></span>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($mensagem_sucesso)): ?>
                <div class="alert alert-success">✅ <?= $mensagem_sucesso ?></div>
            <?php endif; ?>

            <?php if (isset($mensagem_erro)): ?>
                <div class="alert alert-danger">❌ <?= $mensagem_erro ?></div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <strong>❌ Erros encontrados:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($erros as $erro): ?>
                            <li><?= $erro ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- User Info Card -->
            <div class="user-info-card">
                <div class="user-avatar-large">
                    <?= strtoupper(substr($user_edit['nome'], 0, 1)) ?>
                </div>
                <div class="user-info-grid">
                    <div class="info-item">
                        <span class="info-label">ID do Usuário</span>
                        <span class="info-value">#<?= $user_edit['id'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Data de Cadastro</span>
                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($user_edit['criado_em'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Última Atualização</span>
                        <span class="info-value">
                            <?= $user_edit['atualizado_em'] ? date('d/m/Y H:i', strtotime($user_edit['atualizado_em'])) : 'Nunca' ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status Atual</span>
                        <span class="badge badge-<?= $user_edit['status'] ?>">
                            <?= ucfirst($user_edit['status']) ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tipo Atual</span>
                        <span class="badge badge-<?= $user_edit['tipo'] ?>">
                            <?= ucfirst($user_edit['tipo']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-header">
                    <h2>📝 Informações do Usuário</h2>
                </div>

                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nome">👤 Nome Completo *</label>
                            <input type="text" 
                                   id="nome" 
                                   name="nome" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($user_edit['nome']) ?>" 
                                   required
                                   placeholder="Digite o nome completo">
                        </div>

                        <div class="form-group">
                            <label for="email">📧 Email *</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($user_edit['email']) ?>" 
                                   required
                                   placeholder="exemplo@email.com">
                        </div>

                        <div class="form-group">
                            <label for="tipo">🎭 Tipo de Usuário *</label>
                            <select id="tipo" name="tipo" class="form-control" required>
                                <option value="usuario" <?= $user_edit['tipo'] == 'usuario' ? 'selected' : '' ?>>Leitor</option>
                                <option value="editor" <?= $user_edit['tipo'] == 'editor' ? 'selected' : '' ?>>Editor</option>
                                <option value="admin" <?= $user_edit['tipo'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                            <div class="form-text">
                                Leitores: Apenas leem notícias | Editores: Publicam notícias | Admins: Gerenciam tudo
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">📊 Status da Conta *</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="ativo" <?= $user_edit['status'] == 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= $user_edit['status'] == 'inativo' ? 'selected' : '' ?>>Inativo</option>
                            </select>
                            <div class="form-text">
                                Usuários inativos não podem fazer login no sistema
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="usuarios.php" class="btn btn-secondary">
                            ↩️ Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            💾 Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($user_edit['id'] != $usuario['id']): ?>
            <div class="form-section">
                <div class="form-header">
                    <h2>⚠️ Ações Avançadas</h2>
                </div>
                
                <div style="text-align: center; padding: 20px;">
                    <p style="margin-bottom: 20px; color: #666;">
                        Ações irreversíveis para gerenciamento avançado do usuário
                    </p>
                    
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="alternar_status.php?id=<?= $user_edit['id'] ?>" 
                           class="btn <?= $user_edit['status'] == 'ativo' ? 'btn-danger' : 'btn-primary' ?>"
                           onclick="return confirm('Tem certeza que deseja <?= $user_edit['status'] == 'ativo' ? 'desativar' : 'ativar' ?> este usuário?')">
                           <?= $user_edit['status'] == 'ativo' ? '⏸️ Desativar' : '✅ Ativar' ?> Usuário
                        </a>
                        
                        <?php if ($user_edit['tipo'] != 'admin'): ?>
                        <a href="promover_usuario.php?id=<?= $user_edit['id'] ?>&tipo=admin" 
                           class="btn btn-primary"
                           onclick="return confirm('Promover este usuário para administrador? Ele terá acesso total ao sistema.')">
                           👑 Promover para Admin
                        </a>
                        <?php endif; ?>
                        
                        <a href="excluir_usuario.php?id=<?= $user_edit['id'] ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('ATENÇÃO: Esta ação é irreversível! Tem certeza que deseja excluir permanentemente este usuário?')">
                           🗑️ Excluir Usuário
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ <strong>Observação:</strong> Você está editando seu próprio perfil. 
                Algumas ações avançadas não estão disponíveis para o próprio usuário.
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Validação em tempo real do email
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = 'var(--danger)';
                this.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
            } else {
                this.style.borderColor = '#e9ecef';
                this.style.boxShadow = 'none';
            }
        });

        // Confirmação antes de sair da página com alterações não salvas
        let formChanged = false;
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, select');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        form.addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>