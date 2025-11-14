<?php
session_start();
require 'includes/conexao.php';
require 'includes/funcoes.php';

$termo = $_GET['q'] ?? '';
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$limite = 10;
$offset = ($pagina - 1) * $limite;

$usuario = usuarioLogado($pdo);

// Buscar notícias
$where = "n.status = 'publicada'";
$params = [];

if (!empty($termo)) {
    $where .= " AND (n.titulo LIKE ? OR n.resumo LIKE ? OR n.noticia LIKE ?)";
    $like_termo = "%$termo%";
    $params = [$like_termo, $like_termo, $like_termo];
}

// Total de resultados
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM noticias n 
    WHERE $where
");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_paginas = ceil($total / $limite);

// Buscar notícias
$stmt = $pdo->prepare("
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome, c.cor AS categoria_cor
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE $where
    ORDER BY n.data DESC
    LIMIT $limite OFFSET $offset
");
$stmt->execute($params);
$noticias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar: "<?= htmlspecialchars($termo) ?>" - InovaHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <section class="busca-resultados">
            <h1 class="titulo-busca">
                🔍 Resultados da busca
                <?php if (!empty($termo)): ?>
                    para "<?= htmlspecialchars($termo) ?>"
                <?php endif; ?>
            </h1>

            <p class="total-resultados">
                <?= $total ?> resultado(s) encontrado(s)
            </p>

            <?php if (empty($noticias)): ?>
                <div class="nenhum-resultado">
                    <h2>Nenhuma notícia encontrada</h2>
                    <p>Tente buscar com outros termos ou <a href="index.php">voltar para a página inicial</a>.</p>
                </div>
            <?php else: ?>
                <div class="lista-resultados">
                    <?php foreach ($noticias as $noticia): ?>
                        <article class="resultado-item">
                            <a href="noticia.php?slug=<?= $noticia['slug'] ?>" class="resultado-link">
                                <div class="resultado-imagem">
                                    <img src="uploads/noticias/<?= $noticia['imagem'] ?>" alt="<?= htmlspecialchars($noticia['titulo']) ?>">
                                </div>
                                <div class="resultado-conteudo">
                                    <span class="categoria-badge" style="background: <?= $noticia['categoria_cor'] ?>">
                                        <?= $noticia['categoria_nome'] ?>
                                    </span>
                                    <h2><?= htmlspecialchars($noticia['titulo']) ?></h2>
                                    <p class="resultado-resumo"><?= htmlspecialchars($noticia['resumo']) ?></p>
                                    <div class="resultado-meta">
                                        <span class="autor">Por <?= $noticia['autor_nome'] ?></span>
                                        <span class="data"><?= formatarData($noticia['data']) ?></span>
                                        <span class="visualizacoes">👁️ <?= $noticia['visualizacoes'] ?></span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação -->
                <?= gerarPaginacao($pagina, $total_paginas, "buscar.php?q=" . urlencode($termo) . "&pagina=") ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <style>
        .busca-resultados {
            padding: var(--space-8) 0;
        }

        .titulo-busca {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--space-2);
            color: var(--primary-text);
        }

        .total-resultados {
            color: var(--secondary-text);
            margin-bottom: var(--space-6);
        }

        .nenhum-resultado {
            text-align: center;
            padding: var(--space-12) 0;
        }

        .nenhum-resultado h2 {
            color: var(--secondary-text);
            margin-bottom: var(--space-4);
        }

        .lista-resultados {
            display: flex;
            flex-direction: column;
            gap: var(--space-6);
        }

        .resultado-item {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .resultado-link {
            display: grid;
            grid-template-columns: 200px 1fr;
            text-decoration: none;
            color: inherit;
        }

        .resultado-imagem {
            height: 150px;
        }

        .resultado-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .resultado-conteudo {
            padding: var(--space-6);
        }

        .resultado-conteudo h2 {
            font-size: var(--font-size-xl);
            margin: var(--space-2) 0 var(--space-3);
            line-height: 1.4;
        }

        .resultado-resumo {
            color: var(--secondary-text);
            margin-bottom: var(--space-3);
            line-height: 1.5;
        }

        .resultado-meta {
            display: flex;
            gap: var(--space-4);
            font-size: var(--font-size-sm);
            color: var(--secondary-text);
        }

        @media (max-width: 768px) {
            .resultado-link {
                grid-template-columns: 1fr;
            }

            .resultado-imagem {
                height: 200px;
            }
        }
    </style>
</body>

</html>