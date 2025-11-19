<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

// Verificar se usuário está logado
if (!$usuario) {
    header('Location: ../auth/login.php');
    exit();
}

$mensagem = '';

// Processar exclusão de comentário
if (isset($_GET['excluir'])) {
    $comentario_id = intval($_GET['excluir']);

    // Verificar se o comentário pertence ao usuário
    $stmt = $pdo->prepare("SELECT usuario_id FROM comentarios WHERE id = ?");
    $stmt->execute([$comentario_id]);
    $comentario = $stmt->fetch();

    if ($comentario && $comentario['usuario_id'] == $usuario['id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM comentarios WHERE id = ?");
            $stmt->execute([$comentario_id]);

            $mensagem = "<div class='alert success'>Comentário excluído com sucesso!</div>";
        } catch (Exception $e) {
            $mensagem = "<div class='alert error'>Erro ao excluir comentário: " . $e->getMessage() . "</div>";
        }
    } else {
        $mensagem = "<div class='alert error'>Você não tem permissão para excluir este comentário.</div>";
    }
}

// Buscar comentários do usuário
$stmt = $pdo->prepare("
    SELECT c.*, 
           n.titulo as noticia_titulo, 
           n.slug as noticia_slug,
           cat.nome as categoria_nome,
           n.autor as noticia_autor_id,
           u_autor.nome as noticia_autor_nome
    FROM comentarios c
    JOIN noticias n ON c.noticia_id = n.id
    JOIN categorias cat ON n.categoria = cat.id
    JOIN usuarios u_autor ON n.autor = u_autor.id
    WHERE c.usuario_id = ?
    ORDER BY c.criado_em DESC
");
$stmt->execute([$usuario['id']]);
$comentarios = $stmt->fetchAll();

// Estatísticas
$total_comentarios = count($comentarios);
$comentarios_aprovados = array_filter($comentarios, fn($c) => $c['aprovado'] == 1);
$comentarios_pendentes = array_filter($comentarios, fn($c) => $c['aprovado'] == 0);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Comentários - InovaHub</title>
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

        .user-container {
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

        .stat-card.aprovados {
            border-top: 4px solid var(--success);
        }

        .stat-card.pendentes {
            border-top: 4px solid var(--warning);
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Comentários */
        .comentario-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            background: #fafafa;
        }

        .comentario-item.aprovado {
            background: white;
            border-left: 4px solid var(--success);
        }

        .comentario-item.pendente {
            background: #fffdf6;
            border-left: 4px solid var(--warning);
        }

        .comentario-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .comentario-meta {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: #666;
            flex-wrap: wrap;
            align-items: center;
        }

        .comentario-noticia {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .comentario-noticia:hover {
            text-decoration: underline;
        }

        .comentario-info {
            font-size: 13px;
            color: #888;
            margin-top: 5px;
        }

        .comentario-texto {
            margin: 15px 0;
            line-height: 1.6;
            padding: 15px;
            background: white;
            border-radius: 5px;
            white-space: pre-wrap;
            border: 1px solid #eee;
        }

        .comentario-acoes {
            display: flex;
            gap: 10px;
            margin-top: 15px;
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

        .badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-aprovado {
            background: #d4edda;
            color: #155724;
        }

        .badge-pendente {
            background: #fff3cd;
            color: #856404;
        }

        .badge-categoria {
            background: #e2e3e5;
            color: #383d41;
            font-size: 11px;
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

            .comentario-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .comentario-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .comentario-acoes {
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    <div class="user-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>🚀 InovaHub</h1>
                <p>Área do Usuário</p>
            </div>

            <nav class="nav-links">
                <a href="../index.php">🏠 Página Inicial</a>
                <a href="dashboard.php">👤 Meu Perfil</a>
                <a href="meus_comentarios.php" class="active">💬 Meus Comentários</a>
                <?php if (podePublicar($usuario)): ?>
                    <a href="../editor/minhas_noticias.php">📰 Minhas Notícias</a>
                    <a href="../editor/nova_noticia.php">✏️ Nova Notícia</a>
                <?php endif; ?>
                <a href="../auth/logout.php">🚪 Sair</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>💬 Meus Comentários</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                    </div>
                    <span>Olá, <?= htmlspecialchars($usuario['nome']) ?></span>
                </div>
            </div>

            <?= $mensagem ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?= $total_comentarios ?></div>
                    <div class="stat-label">Total de Comentários</div>
                </div>
                <div class="stat-card aprovados">
                    <div class="stat-number"><?= count($comentarios_aprovados) ?></div>
                    <div class="stat-label">Comentários Aprovados</div>
                </div>
                <div class="stat-card pendentes">
                    <div class="stat-number"><?= count($comentarios_pendentes) ?></div>
                    <div class="stat-label">Aguardando Aprovação</div>
                </div>
            </div>

            <!-- Lista de Comentários -->
            <div class="section">
                <div class="section-header">
                    <h2>Minhas Participações</h2>
                    <a href="../index.php" class="btn btn-primary">
                        📖 Ver Mais Notícias
                    </a>
                </div>

                <?php if (empty($comentarios)): ?>
                    <div class="empty-state">
                        <h3>💭 Você ainda não fez comentários</h3>
                        <p>Participe das discussões comentando nas notícias que você mais gostou!</p>
                        <a href="../index.php" class="btn btn-primary" style="margin-top: 15px;">
                            📖 Explorar Notícias
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($comentarios as $comentario): ?>
                        <div class="comentario-item <?= $comentario['aprovado'] ? 'aprovado' : 'pendente' ?>">
                            <div class="comentario-header">
                                <div class="comentario-meta">
                                    <span class="badge <?= $comentario['aprovado'] ? 'badge-aprovado' : 'badge-pendente' ?>">
                                        <?= $comentario['aprovado'] ? '✅ Aprovado' : '⏳ Aguardando moderação' ?>
                                    </span>
                                    <span>•</span>
                                    <span><?= date('d/m/Y H:i', strtotime($comentario['criado_em'])) ?></span>
                                </div>
                                <a href="../noticia.php?slug=<?= $comentario['noticia_slug'] ?>"
                                    class="comentario-noticia" target="_blank">
                                    📖 <?= htmlspecialchars($comentario['noticia_titulo']) ?>
                                </a>
                            </div>

                            <div class="comentario-info">
                                <span class="badge badge-categoria">
                                    <?= htmlspecialchars($comentario['categoria_nome']) ?>
                                </span>
                                <span>•</span>
                                <span>Por: <?= htmlspecialchars($comentario['noticia_autor_nome']) ?></span>
                            </div>

                            <div class="comentario-texto">
                                <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                            </div>

                            <div class="comentario-acoes">
                                <a href="../noticia.php?slug=<?= $comentario['noticia_slug'] ?>"
                                    class="btn btn-primary btn-sm" target="_blank">
                                    👁️ Ver Notícia
                                </a>

                                <?php if ($comentario['aprovado'] == 0): ?>
                                    <span class="btn btn-sm" style="background: #6c757d; color: white; cursor: default;">
                                        ⏳ Em moderação
                                    </span>
                                <?php endif; ?>

                                <a href="?excluir=<?= $comentario['id'] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Tem certeza que deseja excluir este comentário?')">
                                    🗑️ Excluir
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>