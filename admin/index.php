<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);


// Para admin: só admin pode acessar
if (!$usuario || !ehAdmin($usuario)) {
    header('Location: ../index.php');
    exit();
}

// Para editor: só editores e admins
if (!$usuario || !podePublicar($usuario)) {
    header('Location: ../index.php');
    exit();
}

// Para publicar: só editores e admins  
if (!$usuario || !podePublicar($usuario)) {
    header('Location: ../index.php');
    exit();
}

// Estatísticas
$total_noticias = $pdo->query("SELECT COUNT(*) as total FROM noticias")->fetch()['total'];
$total_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch()['total'];
$total_comentarios = $pdo->query("SELECT COUNT(*) as total FROM comentarios")->fetch()['total'];
$noticias_publicadas = $pdo->query("SELECT COUNT(*) as total FROM noticias WHERE status = 'publicada'")->fetch()['total'];

// Notícias recentes
$noticias_recentes = $pdo->query("
    SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome 
    FROM noticias n 
    JOIN usuarios u ON n.autor = u.id 
    JOIN categorias c ON n.categoria = c.id 
    ORDER BY n.data DESC 
    LIMIT 5
")->fetchAll();

// Usuários recentes
$usuarios_recentes = $pdo->query("
    SELECT * FROM usuarios 
    ORDER BY criado_em DESC 
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - InovaHub</title>
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
            width: 250px;
            background: var(--secondary);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            background: var(--primary);
            text-align: center;
        }

        .sidebar-header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }

        .nav-links {
            padding: 20px 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--primary);
        }

        .nav-links i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .header h1 {
            color: var(--secondary);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.news {
            border-top: 4px solid var(--primary);
        }

        .stat-card.users {
            border-top: 4px solid var(--success);
        }

        .stat-card.comments {
            border-top: 4px solid var(--warning);
        }

        .stat-card.published {
            border-top: 4px solid var(--danger);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* Tables */
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .section-header h2 {
            color: var(--secondary);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--secondary);
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-published {
            background: #d4edda;
            color: #155724;
        }

        .status-draft {
            background: #fff3cd;
            color: #856404;
        }

        .status-archived {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-edit {
            background: #17a2b8;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-view {
            background: #6c757d;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
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
                <a href="index.php" class="active">📊 Dashboard</a>
                <a href="noticias.php">📰 Gerenciar Notícias</a>
                <a href="usuarios.php">👥 Gerenciar Usuários</a>
                <a href="categorias.php">📂 Gerenciar Categorias</a>
                <a href="comentarios.php">💬 Moderar Comentários</a>
                <a href="../index.php">🏠 Voltar ao Site</a>
                <a href="../auth/logout.php">🚪 Sair</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>📊 Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                    </div>
                    <span>Olá, <?= $usuario['nome'] ?></span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card news">
                    <div class="stat-number"><?= $total_noticias ?></div>
                    <div class="stat-label">Total de Notícias</div>
                </div>
                <div class="stat-card users">
                    <div class="stat-number"><?= $total_usuarios ?></div>
                    <div class="stat-label">Usuários Cadastrados</div>
                </div>
                <div class="stat-card comments">
                    <div class="stat-number"><?= $total_comentarios ?></div>
                    <div class="stat-label">Comentários</div>
                </div>
                <div class="stat-card published">
                    <div class="stat-number"><?= $noticias_publicadas ?></div>
                    <div class="stat-label">Notícias Publicadas</div>
                </div>
            </div>

            <!-- Notícias Recentes -->
            <div class="section">
                <div class="section-header">
                    <h2>📰 Notícias Recentes</h2>
                    <a href="noticias.php" class="btn btn-primary">Ver Todas</a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Categoria</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noticias_recentes as $noticia): ?>
                            <tr>
                                <td><?= htmlspecialchars($noticia['titulo']) ?></td>
                                <td><?= $noticia['autor_nome'] ?></td>
                                <td><?= $noticia['categoria_nome'] ?></td>
                                <td><?= date('d/m/Y', strtotime($noticia['data'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $noticia['status'] ?>">
                                        <?= ucfirst($noticia['status']) ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="../noticia.php?slug=<?= $noticia['slug'] ?>" class="btn btn-view btn-sm">👁️</a>
                                    <a href="editar_noticia.php?id=<?= $noticia['id'] ?>" class="btn btn-edit btn-sm">✏️</a>
                                    <a href="excluir_noticia.php?id=<?= $noticia['id'] ?>" class="btn btn-delete btn-sm">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Usuários Recentes -->
            <div class="section">
                <div class="section-header">
                    <h2>👥 Usuários Recentes</h2>
                    <a href="usuarios.php" class="btn btn-primary">Ver Todos</a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_recentes as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['nome']) ?></td>
                                <td><?= $user['email'] ?></td>
                                <td>
                                    <span class="status-badge <?= $user['tipo'] === 'admin' ? 'status-published' : 'status-draft' ?>">
                                        <?= ucfirst($user['tipo']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['criado_em'])) ?></td>
                                <td class="action-buttons">
                                    <a href="editar_usuario.php?id=<?= $user['id'] ?>" class="btn btn-edit btn-sm">✏️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>