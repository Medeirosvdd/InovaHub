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
// Apenas notícias do próprio usuário (exceto para admin)
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

$total_minhas_noticias = ehAdmin($usuario) ?
    $pdo->query("SELECT COUNT(*) as total FROM noticias")->fetch()['total'] :
    $pdo->prepare("SELECT COUNT(*) as total FROM noticias WHERE autor = ?")->execute([$usuario['id']])->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Editor - InovaHub</title>
    <style>
        /* Mesmo CSS do admin, mas com cores diferentes */
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --secondary: #495057;
        }

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
    </style>
</head>

<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header" style="background: var(--primary);">
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

        <main class="main-content">
            <div class="header">
                <h1>✏️ Painel do Editor</h1>
                <div class="user-info">
                    <div class="user-avatar" style="background: var(--primary);">
                        <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                    </div>
                    <span>Olá, <?= $usuario['nome'] ?> (<?= $usuario['tipo'] ?>)</span>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card news">
                    <div class="stat-number"><?= $total_minhas_noticias ?></div>
                    <div class="stat-label">
                        <?= ehAdmin($usuario) ? 'Total de Notícias' : 'Minhas Notícias' ?>
                    </div>
                </div>
                <!-- Mais stats específicas para editor -->
            </div>

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