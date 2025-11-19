<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!$usuario) {
    header('Location: ../auth/login.php');
    exit();
}

// Se for editor ou admin, redireciona
if (podePublicar($usuario)) {
    if (ehAdmin($usuario)) {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../editor/index.php');
    }
    exit();
}

// Buscar estatísticas do usuário (apenas comentários, sem curtidas)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_comentarios 
    FROM comentarios 
    WHERE usuario_id = ? AND aprovado = 1
");
$stmt->execute([$usuario['id']]);
$estatisticas = $stmt->fetch();

// Buscar últimos comentários
$ultimos_comentarios = $pdo->prepare("
    SELECT n.titulo, n.slug, c.comentario, c.criado_em, c.aprovado 
    FROM comentarios c 
    JOIN noticias n ON c.noticia_id = n.id 
    WHERE c.usuario_id = ? 
    ORDER BY c.criado_em DESC 
    LIMIT 3
");
$ultimos_comentarios->execute([$usuario['id']]);
$comentarios = $ultimos_comentarios->fetchAll();

// Buscar notícias mais recentes para sugestões
$noticias_recentes = $pdo->query("
    SELECT titulo, slug, resumo, data 
    FROM noticias 
    WHERE status = 'publicado' 
    ORDER BY data DESC 
    LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - InovaHub</title>
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
            --info: #17a2b8;
            --purple: #6f42c1;
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

        /* User Profile Grid */
        .user-profile {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 968px) {
            .user-profile {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 5px solid var(--primary);
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(196, 23, 12, 0.3);
        }

        .profile-card h2 {
            color: var(--secondary);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .user-info {
            text-align: left;
            margin: 20px 0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: var(--secondary);
            font-weight: 600;
        }

        .user-type {
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        /* Actions Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }

        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .action-card h3 {
            color: var(--secondary);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .action-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Activities Section */
        .activities-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .section-header h2 {
            color: var(--secondary);
            font-size: 1.5rem;
        }

        .activities-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        @media (max-width: 768px) {
            .activities-grid {
                grid-template-columns: 1fr;
            }
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .activity-icon {
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 5px;
            font-size: 0.95rem;
        }

        .activity-meta {
            font-size: 0.8rem;
            color: #666;
        }

        .activity-text {
            font-size: 0.9rem;
            color: #555;
            margin-top: 5px;
            line-height: 1.4;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        /* News Suggestions */
        .news-suggestions {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .news-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .news-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .news-card h4 {
            color: var(--secondary);
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .news-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .news-meta {
            font-size: 0.8rem;
            color: #888;
        }

        /* Upgrade Banner */
        .upgrade-banner {
            background: linear-gradient(135deg, var(--secondary) 0%, #1a2530 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-top: 30px;
        }

        .upgrade-banner h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: white;
        }

        .upgrade-banner p {
            margin-bottom: 20px;
            opacity: 0.9;
            font-size: 1rem;
        }

        .btn-light {
            background: white;
            color: var(--secondary);
        }

        .btn-light:hover {
            background: #f8f9fa;
            color: var(--secondary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state .icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .profile-card,
            .activities-section,
            .upgrade-banner {
                padding: 20px;
            }

            .user-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>👤 Minha Conta</h1>
            <p>Gerencie seu perfil e acompanhe suas atividades</p>
        </div>

        <div class="user-profile">
            <!-- Perfil do Usuário -->
            <div class="profile-card">
                <div class="user-avatar">
                    <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                </div>
                <h2><?= htmlspecialchars($usuario['nome']) ?></h2>
                <span class="user-type">👤 Usuário</span>

                <div class="user-info">
                    <div class="info-item">
                        <span class="info-label">📧 Email:</span>
                        <span class="info-value"><?= $usuario['email'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">📅 Membro desde:</span>
                        <span class="info-value"><?= date('d/m/Y', strtotime($usuario['criado_em'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">🆔 ID:</span>
                        <span class="info-value">#<?= $usuario['id'] ?></span>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $estatisticas['total_comentarios'] ?? 0 ?></div>
                        <div class="stat-label">Comentários</div>
                    </div>
                </div>

                <a href="editar_perfil.php" class="btn">✏️ Editar Perfil</a>
            </div>

            <!-- Ações Rápidas -->
            <div class="actions-grid">
                <div class="action-card">
                    <div class="action-icon">💬</div>
                    <h3>Meus Comentários</h3>
                    <p>Revise e gerencie seus comentários</p>
                    <a href="meus_comentarios.php" class="btn btn-outline">Ver Comentários</a>
                </div>

                <div class="action-card">
                    <div class="action-icon">⚙️</div>
                    <h3>Configurações</h3>
                    <p>Altere suas preferências e senha</p>
                    <a href="configuracoes.php" class="btn btn-outline">Configurar</a>
                </div>

                <div class="action-card">
                    <div class="action-icon">📊</div>
                    <h3>Minhas Estatísticas</h3>
                    <p>Veja seu histórico de atividades</p>
                    <a href="estatisticas.php" class="btn btn-outline">Ver Estatísticas</a>
                </div>

                <div class="action-card">
                    <div class="action-icon">🔔</div>
                    <h3>Notificações</h3>
                    <p>Configure suas preferências</p>
                    <a href="notificacoes.php" class="btn btn-outline">Gerenciar</a>
                </div>
            </div>
        </div>

        <!-- Últimas Atividades -->
        <div class="activities-section">
            <div class="section-header">
                <h2>📈 Suas Atividades</h2>
                <a href="meus_comentarios.php" class="btn btn-outline">Ver Todos</a>
            </div>

            <div class="activities-grid">
                <!-- Últimos Comentários -->
                <div class="activity-list">
                    <h3 style="margin-bottom: 15px; color: var(--secondary);">💬 Comentários Recentes</h3>
                    <?php if (!empty($comentarios)): ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="activity-item">
                                <div class="activity-icon">💬</div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <a href="../noticia.php?slug=<?= $comentario['slug'] ?>" style="color: inherit; text-decoration: none;">
                                            <?= htmlspecialchars($comentario['titulo']) ?>
                                        </a>
                                    </div>
                                    <div class="activity-text">
                                        <?= htmlspecialchars(resumoTexto($comentario['comentario'], 80)) ?>
                                    </div>
                                    <div class="activity-meta">
                                        <?= formatarData($comentario['criado_em']) ?>
                                        <span class="status-badge <?= $comentario['aprovado'] ? 'status-approved' : 'status-pending' ?>">
                                            <?= $comentario['aprovado'] ? 'Aprovado' : 'Pendente' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="icon">💬</div>
                            <p>Nenhum comentário ainda</p>
                            <p style="font-size: 0.9rem; margin-top: 10px;">
                                <a href="../index.php" style="color: var(--primary);">Explore as notícias</a> e deixe seu primeiro comentário!
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sugestões de Notícias -->
                <div class="activity-list">
                    <h3 style="margin-bottom: 15px; color: var(--secondary);">📰 Notícias para Você</h3>
                    <?php if (!empty($noticias_recentes)): ?>
                        <?php foreach ($noticias_recentes as $noticia): ?>
                            <div class="activity-item">
                                <div class="activity-icon">📰</div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <a href="../noticia.php?slug=<?= $noticia['slug'] ?>" style="color: inherit; text-decoration: none;">
                                            <?= htmlspecialchars($noticia['titulo']) ?>
                                        </a>
                                    </div>
                                    <div class="activity-text">
                                        <?= htmlspecialchars(resumoTexto($noticia['resumo'], 80)) ?>
                                    </div>
                                    <div class="activity-meta">
                                        <?= formatarData($noticia['data']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="icon">📰</div>
                            <p>Nenhuma notícia disponível</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Banner de Upgrade -->
        <div class="upgrade-banner">
            <h3>🚀 Quer Publicar Notícias?</h3>
            <p>Torne-se um editor e compartilhe suas notícias com a comunidade InovaHub</p>
            <a href="../contato.php?assunto=virar-editor" class="btn btn-light">📧 Solicitar Upgrade</a>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>

</html>