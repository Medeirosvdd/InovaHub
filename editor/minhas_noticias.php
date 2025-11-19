<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

// Verificar se usuário está logado e pode publicar (editores e admins)
if (!$usuario || !podePublicar($usuario)) {
    header('Location: ../auth/login.php');
    exit();
}

$mensagem = '';

// Processar exclusão de notícia
if (isset($_GET['excluir'])) {
    $noticia_id = intval($_GET['excluir']);

    // Verificar se a notícia pertence ao usuário (a menos que seja admin)
    $stmt = $pdo->prepare("SELECT autor FROM noticias WHERE id = ?");
    $stmt->execute([$noticia_id]);
    $noticia = $stmt->fetch();

    if ($noticia && ($usuario['tipo'] === 'admin' || $noticia['autor'] == $usuario['id'])) {
        try {
            // Excluir comentários primeiro (se houver constraint de chave estrangeira)
            $pdo->prepare("DELETE FROM comentarios WHERE noticia_id = ?")->execute([$noticia_id]);

            // Excluir notícia
            $stmt = $pdo->prepare("DELETE FROM noticias WHERE id = ?");
            $stmt->execute([$noticia_id]);

            $mensagem = "<div class='alert success'>Notícia excluída com sucesso!</div>";
        } catch (Exception $e) {
            $mensagem = "<div class='alert error'>Erro ao excluir notícia: " . $e->getMessage() . "</div>";
        }
    } else {
        $mensagem = "<div class='alert error'>Você não tem permissão para excluir esta notícia.</div>";
    }
}

// Buscar notícias do usuário
if ($usuario['tipo'] === 'admin') {
    // Admin vê todas as notícias
    $stmt = $pdo->prepare("
        SELECT n.*, 
               u.nome as autor_nome,
               c.nome as categoria_nome,
               COUNT(cm.id) as total_comentarios
        FROM noticias n
        JOIN usuarios u ON n.autor = u.id
        JOIN categorias c ON n.categoria = c.id
        LEFT JOIN comentarios cm ON n.id = cm.noticia_id
        GROUP BY n.id
        ORDER BY n.data DESC
    ");
    $stmt->execute();
} else {
    // Editor vê apenas suas próprias notícias
    $stmt = $pdo->prepare("
        SELECT n.*, 
               u.nome as autor_nome,
               c.nome as categoria_nome,
               COUNT(cm.id) as total_comentarios
        FROM noticias n
        JOIN usuarios u ON n.autor = u.id
        JOIN categorias c ON n.categoria = c.id
        LEFT JOIN comentarios cm ON n.id = cm.noticia_id
        WHERE n.autor = ?
        GROUP BY n.id
        ORDER BY n.data DESC
    ");
    $stmt->execute([$usuario['id']]);
}

$noticias = $stmt->fetchAll();

// Estatísticas
$total_noticias = count($noticias);
$noticias_publicadas = array_filter($noticias, fn($n) => $n['status'] === 'publicada');
$noticias_rascunho = array_filter($noticias, fn($n) => $n['status'] === 'rascunho');
$total_comentarios = array_sum(array_column($noticias, 'total_comentarios'));
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Notícias - InovaHub</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .stat-card.total {
            border-top: 4px solid var(--primary);
        }

        .stat-card.publicadas {
            border-top: 4px solid var(--success);
        }

        .stat-card.rascunhos {
            border-top: 4px solid var(--warning);
        }

        .stat-card.comentarios {
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

        /* Section */
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
            padding: 10px 20px;
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

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: black;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Table */
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

        .status-publicada {
            background: #d4edda;
            color: #155724;
        }

        .status-rascunho {
            background: #fff3cd;
            color: #856404;
        }

        .status-arquivada {
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

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
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

            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .table {
                display: block;
                overflow-x: auto;
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
                <p>Painel do Editor</p>
            </div>

            <nav class="nav-links">
                <a href="../dashboard.php">📊 Dashboard</a>
                <a href="minhas_noticias.php" class="active">📰 Minhas Notícias</a>
                <a href="nova_noticia.php">✏️ Nova Notícia</a>
                <?php if ($usuario['tipo'] === 'admin'): ?>
                    <a href="../admin/usuarios.php">👥 Usuários</a>
                    <a href="../admin/comentarios.php">💬 Comentários</a>
                <?php endif; ?>
                <a href="../index.php">🏠 Voltar ao Site</a>
                <a href="../auth/logout.php">🚪 Sair</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>📰 Minhas Notícias</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                    </div>
                    <span>Olá, <?= $usuario['nome'] ?></span>
                </div>
            </div>

            <?= $mensagem ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?= $total_noticias ?></div>
                    <div class="stat-label">Total de Notícias</div>
                </div>
                <div class="stat-card publicadas">
                    <div class="stat-number"><?= count($noticias_publicadas) ?></div>
                    <div class="stat-label">Publicadas</div>
                </div>
                <div class="stat-card rascunhos">
                    <div class="stat-number"><?= count($noticias_rascunho) ?></div>
                    <div class="stat-label">Rascunhos</div>
                </div>
                <div class="stat-card comentarios">
                    <div class="stat-number"><?= $total_comentarios ?></div>
                    <div class="stat-label">Comentários</div>
                </div>
            </div>

            <!-- Lista de Notícias -->
            <div class="section">
                <div class="section-header">
                    <h2>Minhas Publicações</h2>
                    <a href="nova_noticia.php" class="btn btn-primary">
                        ✏️ Nova Notícia
                    </a>
                </div>

                <?php if (empty($noticias)): ?>
                    <div class="empty-state">
                        <h3>📝 Você ainda não tem notícias</h3>
                        <p>Crie sua primeira notícia usando o botão acima.</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Categoria</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Comentários</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noticias as $noticia): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($noticia['titulo']) ?></strong>
                                        <?php if ($usuario['tipo'] === 'admin'): ?>
                                            <br><small>por <?= $noticia['autor_nome'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $noticia['categoria_nome'] ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $noticia['status'] ?>">
                                            <?= ucfirst($noticia['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($noticia['data'])) ?></td>
                                    <td>
                                        <span class="badge"><?= $noticia['total_comentarios'] ?></span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="../noticia.php?slug=<?= $noticia['slug'] ?>"
                                            class="btn btn-view btn-sm" target="_blank">👁️</a>
                                        <a href="editar_noticia.php?id=<?= $noticia['id'] ?>"
                                            class="btn btn-edit btn-sm">✏️</a>
                                        <a href="?excluir=<?= $noticia['id'] ?>"
                                            class="btn btn-delete btn-sm"
                                            onclick="return confirm('Tem certeza que deseja excluir a notícia \'<?= addslashes($noticia['titulo']) ?>\'?')">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>