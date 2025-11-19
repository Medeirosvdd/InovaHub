<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!$usuario || !ehAdmin($usuario)) {
    header('Location: ../index.php');
    exit();
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

// Buscar todos os usuários
$usuarios = $pdo->query("
    SELECT id, nome, email, tipo, status, criado_em 
    FROM usuarios 
    ORDER BY criado_em DESC
")->fetchAll();

// Estatísticas
$total_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch()['total'];
$total_admins = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'admin'")->fetch()['total'];
$total_editores = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'editor'")->fetch()['total'];
$total_leitores = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'usuario'")->fetch()['total'];
$total_ativos = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE status = 'ativo'")->fetch()['total'];
$total_inativos = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE status = 'inativo'")->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin</title>
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
            --purple: #6f42c1;
            --teal: #20c997;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.total::before {
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }

        .stat-card.admins::before {
            background: linear-gradient(90deg, var(--danger), #c82333);
        }

        .stat-card.editors::before {
            background: linear-gradient(90deg, var(--warning), #e0a800);
        }

        .stat-card.readers::before {
            background: linear-gradient(90deg, var(--info), #138496);
        }

        .stat-card.ativos::before {
            background: linear-gradient(90deg, var(--success), #1e7e34);
        }

        .stat-card.inativos::before {
            background: linear-gradient(90deg, #6c757d, #495057);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2.2em;
            font-weight: 800;
            margin: 10px 0;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        /* Section */
        .section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .section-header h2 {
            color: var(--secondary);
            font-size: 22px;
            font-weight: 700;
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

        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .table th {
            background: linear-gradient(135deg, var(--secondary) 0%, #1a2530 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table th:first-child {
            border-radius: 8px 0 0 0;
        }

        .table th:last-child {
            border-radius: 0 8px 0 0;
        }

        .table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }

        .table tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        /* User Avatar in Table */
        .user-avatar-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 10px;
        }

        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Type Badges */
        .type-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid;
        }

        .type-admin {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .type-editor {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }

        .type-usuario {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid;
        }

        .status-ativo {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .status-inativo {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Action Buttons - AGORA SÓ UM BOTÃO */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-manage {
            background: var(--warning);
            color: white;
            border: none;
        }

        .btn-manage:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background: #e0a800;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }

        /* Search and Filters */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(196, 23, 12, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-select {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            min-width: 150px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }

            .main-content {
                margin-left: 250px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .filters {
                flex-direction: column;
            }

            .search-box {
                min-width: 100%;
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
                <h1>👥 Gerenciar Usuários</h1>
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

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?= $total_usuarios ?></div>
                    <div class="stat-label">Total de Usuários</div>
                </div>
                <div class="stat-card admins">
                    <div class="stat-icon">👑</div>
                    <div class="stat-number"><?= $total_admins ?></div>
                    <div class="stat-label">Administradores</div>
                </div>
                <div class="stat-card editors">
                    <div class="stat-icon">✏️</div>
                    <div class="stat-number"><?= $total_editores ?></div>
                    <div class="stat-label">Editores</div>
                </div>
                <div class="stat-card readers">
                    <div class="stat-icon">📖</div>
                    <div class="stat-number"><?= $total_leitores ?></div>
                    <div class="stat-label">Leitores</div>
                </div>
                <div class="stat-card ativos">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?= $total_ativos ?></div>
                    <div class="stat-label">Ativos</div>
                </div>
                <div class="stat-card inativos">
                    <div class="stat-icon">⏸️</div>
                    <div class="stat-number"><?= $total_inativos ?></div>
                    <div class="stat-label">Inativos</div>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>📋 Todos os Usuários</h2>
                    <a href="novo_usuario.php" class="btn btn-primary">
                        ➕ Novo Usuário
                    </a>
                </div>

                <!-- Filters -->
                <div class="filters">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="🔍 Buscar usuários...">
                        <div class="search-icon">🔍</div>
                    </div>
                    <select class="filter-select" id="typeFilter">
                        <option value="">Todos os Tipos</option>
                        <option value="admin">Administradores</option>
                        <option value="editor">Editores</option>
                        <option value="usuario">Leitores</option>
                    </select>
                    <select class="filter-select" id="statusFilter">
                        <option value="">Todos os Status</option>
                        <option value="ativo">Ativos</option>
                        <option value="inativo">Inativos</option>
                    </select>
                </div>

                <?php if (empty($usuarios)): ?>
                    <div class="empty-state">
                        <div class="icon">👥</div>
                        <h3>Nenhum usuário encontrado</h3>
                        <p>Comece criando o primeiro usuário!</p>
                        <a href="novo_usuario.php" class="btn btn-primary" style="margin-top: 20px;">
                            ➕ Criar Primeiro Usuário
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Email</th>
                                    <th>Tipo</th>
                                    <th>Data Cadastro</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info-cell">
                                                <div class="user-avatar-sm">
                                                    <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--secondary);">
                                                        <?= htmlspecialchars($user['nome']) ?>
                                                    </div>
                                                    <div style="font-size: 12px; color: #666;">
                                                        ID: <?= $user['id'] ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $user['email'] ?></td>
                                        <td>
                                            <span class="type-badge type-<?= $user['tipo'] ?>">
                                                <?= ucfirst($user['tipo']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($user['criado_em'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $user['status'] ?>">
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- APENAS UM BOTÃO QUE VAI PARA EDIÇÃO COMPLETA -->
                                                <a href="editar_usuario.php?id=<?= $user['id'] ?>"
                                                    class="btn btn-manage btn-sm"
                                                    title="Gerenciar Usuário">
                                                    ⚙️ Gerenciar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Filtros e busca
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const typeFilter = document.getElementById('typeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            function filterUsers() {
                const searchTerm = searchInput.value.toLowerCase();
                const typeValue = typeFilter.value;
                const statusValue = statusFilter.value;

                for (let row of rows) {
                    const name = row.cells[0].textContent.toLowerCase();
                    const email = row.cells[1].textContent.toLowerCase();
                    const type = row.cells[2].textContent.toLowerCase();
                    const status = row.cells[4].textContent.toLowerCase();

                    const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                    const matchesType = !typeValue || type.includes(typeValue);
                    const matchesStatus = !statusValue || status.includes(statusValue);

                    row.style.display = matchesSearch && matchesType && matchesStatus ? '' : 'none';
                }
            }

            searchInput.addEventListener('input', filterUsers);
            typeFilter.addEventListener('change', filterUsers);
            statusFilter.addEventListener('change', filterUsers);

            // Adiciona animação suave às linhas da tabela
            for (let i = 0; i < rows.length; i++) {
                rows[i].style.animationDelay = `${i * 0.05}s`;
            }
        });
    </script>
</body>

</html>