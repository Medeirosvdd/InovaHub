<?php
require '../includes/verifica_admin.php';

// Estatísticas para o dashboard
$estatisticas = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM noticias) as total_noticias,
        (SELECT COUNT(*) FROM noticias WHERE status = 'publicada') as noticias_publicadas,
        (SELECT COUNT(*) FROM noticias WHERE status = 'rascunho') as noticias_rascunho,
        (SELECT COUNT(*) FROM usuarios) as total_usuarios,
        (SELECT COUNT(*) FROM comentarios) as total_comentarios,
        (SELECT COUNT(*) FROM comentarios WHERE aprovado = 0) as comentarios_pendentes,
        (SELECT SUM(visualizacoes) FROM noticias) as total_visualizacoes
")->fetch(PDO::FETCH_ASSOC);

// Notícias mais visualizadas
$mais_visualizadas = $pdo->query("
    SELECT n.titulo, n.visualizacoes, u.nome as autor_nome
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    ORDER BY n.visualizacoes DESC
    LIMIT 5
")->fetchAll();

// Últimas notícias
$ultimas_noticias = $pdo->query("
    SELECT n.titulo, n.data, n.status, u.nome as autor_nome
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    ORDER BY n.data DESC
    LIMIT 5
")->fetchAll();

// Últimos usuários
$ultimos_usuarios = $pdo->query("
    SELECT nome, email, tipo, criado_em
    FROM usuarios
    ORDER BY criado_em DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - InovaHub</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .admin-dashboard {
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .dashboard-title {
            color: #c4170c;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .welcome-text {
            color: #666;
            font-size: 1.1rem;
        }
        
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #c4170c;
            display: block;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-title {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c4170c;
            font-size: 1.3rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: #c4170c;
            color: white;
            border-color: #c4170c;
            transform: translateY(-2px);
        }
        
        .action-icon {
            font-size: 1.5rem;
        }
        
        .list-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .item-title {
            font-weight: 500;
            color: #333;
        }
        
        .item-meta {
            font-size: 0.8rem;
            color: #666;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-publicada {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rascunho {
            background: #fff3cd;
            color: #856404;
        }
        
        .user-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .type-admin {
            background: #c4170c;
            color: white;
        }
        
        .type-editor {
            background: #0066cc;
            color: white;
        }
        
        .type-usuario {
            background: #28a745;
            color: white;
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .admin-dashboard {
                padding: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">📊 Dashboard Administrativo</h1>
            <p class="welcome-text">Bem-vindo de volta, <?= htmlspecialchars($usuario['nome']) ?>! Aqui está o resumo do seu portal.</p>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?= $estatisticas['total_noticias'] ?></span>
                <span class="stat-label">Total de Notícias</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $estatisticas['total_usuarios'] ?></span>
                <span class="stat-label">Usuários Cadastrados</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $estatisticas['total_visualizacoes'] ?></span>
                <span class="stat-label">Visualizações</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $estatisticas['comentarios_pendentes'] ?></span>
                <span class="stat-label">Comentários Pendentes</span>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Coluna Principal -->
            <div class="main-column">
                <!-- Ações Rápidas -->
                <div class="dashboard-card">
                    <h2 class="card-title">⚡ Ações Rápidas</h2>
                    <div class="quick-actions">
                        <a href="noticias.php" class="action-btn">
                            <span class="action-icon">📝</span>
                            <span>Gerenciar Notícias</span>
                        </a>
                        <a href="usuarios.php" class="action-btn">
                            <span class="action-icon">👥</span>
                            <span>Gerenciar Usuários</span>
                        </a>
                        <a href="categorias.php" class="action-btn">
                            <span class="action-icon">📂</span>
                            <span>Gerenciar Categorias</span>
                        </a>
                        <a href="comentarios.php" class="action-btn">
                            <span class="action-icon">💬</span>
                            <span>Moderar Comentários</span>
                        </a>
                        <a href="../noticias/nova_noticia.php" class="action-btn">
                            <span class="action-icon">✨</span>
                            <span>Nova Notícia</span>
                        </a>
                        <a href="../index.php" class="action-btn">
                            <span class="action-icon">🏠</span>
                            <span>Ver Site</span>
                        </a>
                    </div>
                </div>
                
                <!-- Últimas Notícias -->
                <div class="dashboard-card">
                    <h2 class="card-title">📰 Últimas Notícias</h2>
                    <div class="list-container">
                        <?php foreach ($ultimas_noticias as $noticia): ?>
                            <div class="list-item">
                                <div>
                                    <div class="item-title"><?= htmlspecialchars($noticia['titulo']) ?></div>
                                    <div class="item-meta">
                                        Por <?= $noticia['autor_nome'] ?> • 
                                        <?= formatarData($noticia['data']) ?>
                                    </div>
                                </div>
                                <span class="status-badge <?= $noticia['status'] === 'publicada' ? 'status-publicada' : 'status-rascunho' ?>">
                                    <?= $noticia['status'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar-column">
                <!-- Mais Visualizadas -->
                <div class="dashboard-card">
                    <h2 class="card-title">🔥 Mais Visualizadas</h2>
                    <div class="list-container">
                        <?php foreach ($mais_visualizadas as $noticia): ?>
                            <div class="list-item">
                                <div>
                                    <div class="item-title"><?= htmlspecialchars($noticia['titulo']) ?></div>
                                    <div class="item-meta">
                                        Por <?= $noticia['autor_nome'] ?>
                                    </div>
                                </div>
                                <span class="stat-number" style="font-size: 1rem;"><?= $noticia['visualizacoes'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Últimos Usuários -->
                <div class="dashboard-card">
                    <h2 class="card-title">👥 Últimos Usuários</h2>
                    <div class="list-container">
                        <?php foreach ($ultimos_usuarios as $user): ?>
                            <div class="list-item">
                                <div>
                                    <div class="item-title"><?= htmlspecialchars($user['nome']) ?></div>
                                    <div class="item-meta">
                                        <?= $user['email'] ?> • 
                                        <?= formatarData($user['criado_em']) ?>
                                    </div>
                                </div>
                                <span class="user-type type-<?= $user['tipo'] ?>">
                                    <?= $user['tipo'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Atualizar dashboard a cada 2 minutos
        setInterval(() => {
            window.location.reload();
        }, 120000);
    </script>
</body>
</html>