<?php
require '../includes/verifica_admin.php';

// Ações
$acao = $_GET['acao'] ?? '';
$id = $_GET['id'] ?? 0;

if ($acao === 'excluir' && $id) {
    $stmt = $pdo->prepare("DELETE FROM noticias WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['sucesso'] = "Notícia excluída com sucesso!";
    header('Location: noticias.php');
    exit();
}

if ($acao === 'toggle_destaque' && $id) {
    $stmt = $pdo->prepare("UPDATE noticias SET destaque = NOT destaque WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: noticias.php');
    exit();
}

// Paginação
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$limite = 15;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_autor = $_GET['autor'] ?? '';

$where = "1=1";
$params = [];

if ($filtro_status) {
    $where .= " AND n.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_categoria) {
    $where .= " AND n.categoria = ?";
    $params[] = $filtro_categoria;
}

if ($filtro_autor) {
    $where .= " AND n.autor = ?";
    $params[] = $filtro_autor;
}

// Total
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM noticias n WHERE $where");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_paginas = ceil($total / $limite);

// Notícias
$stmt = $pdo->prepare("
    SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE $where
    ORDER BY n.data DESC
    LIMIT $limite OFFSET $offset
");
$stmt->execute($params);
$noticias = $stmt->fetchAll();

// Filtros disponíveis
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$autores = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Notícias - InovaHub Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .admin-content {
            padding: 30px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .content-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            color: #333;
            font-size: 2rem;
        }

        .btn-primary {
            background: #c4170c;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #a6140b;
        }

        .filtros {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .filtros-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }

        .btn-group {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #0066cc;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-destaque {
            background: #28a745;
            color: white;
        }

        .btn-sm:hover {
            opacity: 0.8;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }

        .page-link.active {
            background: #c4170c;
            color: white;
            border-color: #c4170c;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>

<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="admin-content">
        <div class="content-header">
            <h1 class="page-title">📝 Gerenciar Notícias</h1>
            <a href="../noticias/nova_noticia.php" class="btn-primary">+ Nova Notícia</a>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form method="GET" class="filtros-form">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos os status</option>
                        <option value="publicada" <?= $filtro_status === 'publicada' ? 'selected' : '' ?>>Publicadas</option>
                        <option value="rascunho" <?= $filtro_status === 'rascunho' ? 'selected' : '' ?>>Rascunhos</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select name="categoria" class="form-select">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filtro_categoria == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Autor</label>
                    <select name="autor" class="form-select">
                        <option value="">Todos os autores</option>
                        <?php foreach ($autores as $autor): ?>
                            <option value="<?= $autor['id'] ?>" <?= $filtro_autor == $autor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($autor['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-primary">🔍 Filtrar</button>
                    <a href="noticias.php" class="btn-sm" style="background: #6c757d; color: white; margin-top: 5px;">🔄 Limpar</a>
                </div>
            </form>
        </div>

        <!-- Tabela de Notícias -->
        <div class="table-container">
            <?php if (empty($noticias)): ?>
                <div class="empty-state">
                    <h3>📭 Nenhuma notícia encontrada</h3>
                    <p>Não há notícias correspondentes aos filtros selecionados.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Categoria</th>
                            <th>Status</th>
                            <th>Visualizações</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noticias as $noticia): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($noticia['titulo']) ?></strong>
                                    <?php if ($noticia['destaque']): ?>
                                        <span class="badge badge-primary" style="margin-left: 5px;">Destaque</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $noticia['autor_nome'] ?></td>
                                <td><?= $noticia['categoria_nome'] ?></td>
                                <td>
                                    <span class="badge <?= $noticia['status'] === 'publicada' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $noticia['status'] ?>
                                    </span>
                                </td>
                                <td><?= $noticia['visualizacoes'] ?></td>
                                <td><?= formatarData($noticia['data']) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../noticia.php?slug=<?= $noticia['slug'] ?>" class="btn-sm" style="background: #17a2b8; color: white;" target="_blank">👁️</a>
                                        <a href="../noticias/editar_noticia.php?id=<?= $noticia['id'] ?>" class="btn-sm btn-edit">✏️</a>
                                        <a href="noticias.php?acao=toggle_destaque&id=<?= $noticia['id'] ?>" class="btn-sm btn-destaque">
                                            <?= $noticia['destaque'] ? '⭐' : '☆' ?>
                                        </a>
                                        <a href="noticias.php?acao=excluir&id=<?= $noticia['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Tem certeza que deseja excluir esta notícia?')">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="noticias.php?pagina=<?= $i ?>&status=<?= $filtro_status ?>&categoria=<?= $filtro_categoria ?>&autor=<?= $filtro_autor ?>"
                                class="page-link <?= $i == $pagina ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>