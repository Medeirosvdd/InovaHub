<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

if (!$usuario || !ehAdmin($usuario)) {
    header('Location: ../index.php');
    exit();
}

// Buscar todas as notícias
$noticias = $pdo->query("
    SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome 
    FROM noticias n 
    JOIN usuarios u ON n.autor = u.id 
    JOIN categorias c ON n.categoria = c.id 
    ORDER BY n.data DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Notícias - Admin</title>
    <!-- Usar o mesmo CSS do index.php admin -->
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar (mesma do index.php) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>🚀 InovaHub</h1>
                <p>Painel Administrativo</p>
            </div>
            <nav class="nav-links">
                <a href="index.php">📊 Dashboard</a>
                <a href="noticias.php" class="active">📰 Gerenciar Notícias</a>
                <a href="usuarios.php">👥 Gerenciar Usuários</a>
                <a href="categorias.php">📂 Gerenciar Categorias</a>
                <a href="comentarios.php">💬 Moderar Comentários</a>
                <a href="../index.php">🏠 Voltar ao Site</a>
                <a href="../auth/logout.php">🚪 Sair</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>📰 Gerenciar Notícias</h1>
                <a href="../noticias/nova_noticia.php" class="btn btn-primary">➕ Nova Notícia</a>
            </div>

            <div class="section">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Categoria</th>
                            <th>Data</th>
                            <th>Visualizações</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noticias as $noticia): ?>
                            <tr>
                                <td><?= htmlspecialchars($noticia['titulo']) ?></td>
                                <td><?= $noticia['autor_nome'] ?></td>
                                <td><?= $noticia['categoria_nome'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($noticia['data'])) ?></td>
                                <td><?= $noticia['visualizacoes'] ?></td>
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
        </main>
    </div>
</body>

</html>