<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

// DEBUG - REMOVER DEPOIS DE TESTAR
error_log("DEBUG EDITOR: Usuario: " . ($usuario ? $usuario['nome'] : 'NULO') .
    " | Tipo: " . ($usuario ? $usuario['tipo'] : 'N/A') .
    " | podePublicar: " . (podePublicar($usuario) ? 'SIM' : 'NÃO'));

// VERIFICAÇÃO ÚNICA E CORRETA: Apenas editores e admins podem acessar
if (!$usuario || !podePublicar($usuario)) {
    header('Location: ../index.php');
    exit();
}

// Buscar notícias
if (ehAdmin($usuario)) {
    $minhas_noticias = $pdo->query("
        SELECT n.*, c.nome as categoria_nome 
        FROM noticias n 
        JOIN categorias c ON n.categoria = c.id 
        ORDER BY n.data DESC 
        LIMIT 10
    ")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT n.*, c.nome as categoria_nome 
        FROM noticias n 
        JOIN categorias c ON n.categoria = c.id 
        WHERE n.autor = ? 
        ORDER BY n.data DESC 
        LIMIT 10
    ");
    $stmt->execute([$usuario['id']]);
    $minhas_noticias = $stmt->fetchAll();
}

// CONTAGEM CORRIGIDA
if (ehAdmin($usuario)) {
    $total_minhas_noticias = $pdo->query("SELECT COUNT(*) as total FROM noticias")->fetch()['total'];
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM noticias WHERE autor = ?");
    $stmt->execute([$usuario['id']]);
    $result = $stmt->fetch();
    $total_minhas_noticias = $result['total'];
}

// Estatísticas adicionais para melhor UX
if (ehAdmin($usuario)) {
    $total_publicadas = $pdo->query("SELECT COUNT(*) as total FROM noticias WHERE status = 'publicado'")->fetch()['total'];
    $total_rascunhos = $pdo->query("SELECT COUNT(*) as total FROM noticias WHERE status = 'rascunho'")->fetch()['total'];
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM noticias WHERE autor = ? AND status = 'publicado'");
    $stmt->execute([$usuario['id']]);
    $total_publicadas = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM noticias WHERE autor = ? AND status = 'rascunho'");
    $stmt->execute([$usuario['id']]);
    $total_rascunhos = $stmt->fetch()['total'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Editor - InovaHub</title>
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
            justify-content: space-between;
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
            justify-content: space-between;
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

        .status-publicado {
            background: #d4edda;
            color: #155724;
        }

        .status-rascunho {
            background: #fff3cd;
            color: #856404;
        }

        .status-pendente {
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

        /* Editor specific styles */
        .editor-only {
            display: block;
        }

        .admin-only {
            display: none;
        }

        <?php if (ehAdmin($usuario)): ?>.admin-only {
            display: block;
        }

        <?php endif; ?>
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

            .section-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>✏️ InovaHub</h1>
                <p>Painel do Editor</p>
            </div>

            <nav class="nav-links">
                <a href="index.php" class="active">📊 Meu Dashboard</a>
                <a href="minhas_noticias.php">📰 Minhas Notícias</a>
                <a href="../noticias/nova_noticia.php">➕ Nova Notícia</a>

                <?php if (ehAdmin($usuario)): ?>
                    <div class="admin-only">
                        <a href="../admin/index.php">⚙️ Painel Admin</a>
                    </div>
                <?php endif; ?>

                <a href="../index.php">🏠 Voltar ao Site</a>
                <a href="../auth/logout.php">🚪 Sair</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>✏️ Painel do Editor</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                    </div>
                    <span>Olá, <?= $usuario['nome'] ?> (<?= $usuario['tipo'] ?>)</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card news">
                    <div class="stat-number"><?= $total_minhas_noticias ?></div>
                    <div class="stat-label">
                        <?= ehAdmin($usuario) ? 'Total de Notícias' : 'Minhas Notícias' ?>
                    </div>
                </div>
                <!-- Mais stats específicas para editor podem ser adicionadas aqui -->
            </div>

            <!-- Notícias Recentes -->
            <div class="section">
                <div class="section-header">
                    <h2>📰 <?= ehAdmin($usuario) ? 'Todas as Notícias' : 'Minhas Notícias Recentes' ?></h2>
                    <a href="minhas_noticias.php" class="btn btn-primary">Ver Todas</a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($minhas_noticias as $noticia): ?>
                            <tr>
                                <td><?= htmlspecialchars($noticia['titulo']) ?></td>
                                <td><?= $noticia['categoria_nome'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($noticia['data'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $noticia['status'] ?>">
                                        <?= ucfirst($noticia['status']) ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="../noticia.php?slug=<?= $noticia['slug'] ?>" class="btn btn-view btn-sm">👁️</a>
                                    <a href="../noticias/editar_noticia.php?id=<?= $noticia['id'] ?>" class="btn btn-edit btn-sm">✏️</a>
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