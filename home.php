<?php
session_start();
require 'includes/conexao.php';
require 'includes/funcoes.php';

$usuario = usuarioLogado($pdo);

// Buscar manchete principal (destaque ou última notícia)
$manchete = $pdo->query("
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome, c.cor AS categoria_cor
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE n.status = 'publicada'
    ORDER BY n.destaque DESC, n.data DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Buscar destaques (3 notícias mais recentes após a manchete)
$destaques = $pdo->query("
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE n.status = 'publicada' AND n.id != " . $manchete['id'] . "
    ORDER BY n.data DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

// Buscar notícias por categoria (para sidebar)
$noticias_por_categoria = [];
$categorias = $pdo->query("SELECT id, nome, slug FROM categorias ORDER BY nome")->fetchAll();

foreach ($categorias as $cat) {
    $stmt = $pdo->prepare("
        SELECT n.*, u.nome AS autor_nome 
        FROM noticias n 
        JOIN usuarios u ON u.id = n.autor 
        WHERE n.categoria = ? AND n.status = 'publicada' 
        ORDER BY n.data DESC 
        LIMIT 3
    ");
    $stmt->execute([$cat['id']]);
    $noticias_por_categoria[$cat['nome']] = $stmt->fetchAll();
}

// Notícias mais lidas da semana
$mais_lidas = $pdo->query("
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome,
           SUM(e.visualizacoes) as total_visualizacoes
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    LEFT JOIN estatisticas e ON e.noticia_id = n.id AND e.data >= CURDATE() - INTERVAL 7 DAY
    WHERE n.status = 'publicada'
    GROUP BY n.id
    ORDER BY total_visualizacoes DESC, n.data DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InovaHub - Tecnologia e Inovação</title>
    <meta name="description" content="Portal de notícias sobre tecnologia, inovação, startups e inteligência artificial. Fique por dentro das últimas novidades do mundo tech.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Header -->
    <header class="topo">
        <div class="topo-container">
            <div class="logo-area">
                <h1 class="logo">
                    <a href="index.php">InovaHub</a>
                </h1>
                <button class="menu-toggle" id="menu-toggle">☰</button>
            </div>

            <nav class="nav-principal" id="nav-principal">
                <a href="index.php" class="active">Início</a>
                <?php foreach ($categorias as $cat): ?>
                    <a href="noticias/categoria.php?cat=<?= $cat['slug'] ?>" style="color: <?= $cat['cor'] ?>">
                        <?= $cat['nome'] ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="user-area">
                <?php if ($usuario): ?>
                    <div class="user-menu">
                        <span class="user-greeting">Olá, <?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                        <div class="user-dropdown">
                            <a href="usuario/dashboard.php" class="dropdown-item">📊 Dashboard</a>
                            <a href="noticias/nova_noticia.php" class="dropdown-item">✏️ Publicar</a>
                            <?php if (ehAdmin($usuario)): ?>
                                <a href="admin/index.php" class="dropdown-item">⚙️ Admin</a>
                            <?php endif; ?>
                            <a href="auth/logout.php" class="dropdown-item">🚪 Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="auth/login.php" class="btn-login">Entrar</a>
                        <a href="auth/cadastro.php" class="btn-register">Cadastrar</a>
                    </div>
                <?php endif; ?>
                <button class="btn-theme" id="btn-theme" title="Alternar tema">🌙</button>
            </div>
        </div>
    </header>

    <!-- Barra de busca -->
    <section class="busca-section">
        <div class="container">
            <form method="GET" action="buscar.php" class="busca-form">
                <input type="text" name="q" placeholder="Buscar notícias, tecnologias, startups..." required>
                <button type="submit" class="btn-busca">🔍 Pesquisar</button>
            </form>
        </div>
    </section>

    <main class="container">
        <!-- Manchete Principal -->
        <section class="manchete-principal">
            <a href="noticia.php?slug=<?= $manchete['slug'] ?>" class="manchete-link">
                <div class="manchete-imagem">
                    <img src="uploads/noticias/<?= $manchete['imagem'] ?>" alt="<?= htmlspecialchars($manchete['titulo']) ?>">
                    <span class="categoria-badge" style="background: <?= $manchete['categoria_cor'] ?>">
                        <?= $manchete['categoria_nome'] ?>
                    </span>
                </div>
                <div class="manchete-conteudo">
                    <h1 class="manchete-titulo"><?= htmlspecialchars($manchete['titulo']) ?></h1>
                    <p class="manchete-resumo"><?= htmlspecialchars($manchete['resumo']) ?></p>
                    <div class="manchete-meta">
                        <span class="autor">Por <?= $manchete['autor_nome'] ?></span>
                        <span class="data"><?= formatarData($manchete['data']) ?></span>
                        <span class="visualizacoes">👁️ <?= $manchete['visualizacoes'] ?></span>
                    </div>
                </div>
            </a>
        </section>

        <!-- Destaques Secundários -->
        <section class="destaques-secundarios">
            <?php foreach ($destaques as $destaque): ?>
                <article class="destaque-card">
                    <a href="noticia.php?slug=<?= $destaque['slug'] ?>">
                        <div class="destaque-imagem">
                            <img src="uploads/noticias/<?= $destaque['imagem'] ?>" alt="<?= htmlspecialchars($destaque['titulo']) ?>">
                        </div>
                        <div class="destaque-conteudo">
                            <h3><?= htmlspecialchars($destaque['titulo']) ?></h3>
                            <div class="destaque-meta">
                                <span class="categoria"><?= $destaque['categoria_nome'] ?></span>
                                <span class="data"><?= formatarData($destaque['data']) ?></span>
                            </div>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </section>

        <div class="layout-principal">
            <!-- Conteúdo Principal -->
            <div class="conteudo-principal">
                <!-- Notícias por Categoria -->
                <?php foreach ($noticias_por_categoria as $categoria_nome => $noticias_cat): ?>
                    <?php if (!empty($noticias_cat)): ?>
                        <section class="categoria-section">
                            <h2 class="titulo-categoria">
                                <?= $categoria_nome ?>
                                <a href="noticias/categoria.php?cat=<?= slugify($categoria_nome) ?>" class="ver-tudo">
                                    Ver tudo →
                                </a>
                            </h2>
                            <div class="grid-noticias">
                                <?php foreach ($noticias_cat as $noticia): ?>
                                    <article class="noticia-card">
                                        <a href="noticia.php?slug=<?= $noticia['slug'] ?>">
                                            <div class="noticia-imagem">
                                                <img src="uploads/noticias/<?= $noticia['imagem'] ?>" alt="<?= htmlspecialchars($noticia['titulo']) ?>">
                                            </div>
                                            <div class="noticia-conteudo">
                                                <h3><?= htmlspecialchars($noticia['titulo']) ?></h3>
                                                <p class="noticia-resumo"><?= htmlspecialchars($noticia['resumo']) ?></p>
                                                <div class="noticia-meta">
                                                    <span class="autor"><?= $noticia['autor_nome'] ?></span>
                                                    <span class="data"><?= formatarData($noticia['data']) ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Mais Lidas -->
                <section class="sidebar-widget">
                    <h3>📈 Mais Lidas da Semana</h3>
                    <div class="mais-lidas-list">
                        <?php foreach ($mais_lidas as $index => $noticia): ?>
                            <article class="mais-lida-item">
                                <span class="ranking"><?= $index + 1 ?></span>
                                <div class="conteudo">
                                    <a href="noticia.php?slug=<?= $noticia['slug'] ?>">
                                        <h4><?= htmlspecialchars($noticia['titulo']) ?></h4>
                                    </a>
                                    <div class="meta">
                                        <span class="categoria"><?= $noticia['categoria_nome'] ?></span>
                                        <span class="visualizacoes"><?= $noticia['total_visualizacoes'] ?? 0 ?> visualizações</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Newsletter -->
                <section class="sidebar-widget newsletter-widget">
                    <h3>📧 Newsletter</h3>
                    <p>Receba as principais notícias de tecnologia no seu email.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Seu melhor email" required>
                        <button type="submit">Assinar</button>
                    </form>
                </section>

                <!-- Redes Sociais -->
                <section class="sidebar-widget">
                    <h3>🌐 Siga-nos</h3>
                    <div class="social-links">
                        <a href="#" class="social-link twitter">Twitter</a>
                        <a href="#" class="social-link linkedin">LinkedIn</a>
                        <a href="#" class="social-link instagram">Instagram</a>
                    </div>
                </section>
            </aside>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>InovaHub</h3>
                    <p>Seu portal de notícias sobre tecnologia, inovação e o futuro digital.</p>
                </div>
                <div class="footer-section">
                    <h4>Categorias</h4>
                    <ul>
                        <?php foreach ($categorias as $cat): ?>
                            <li><a href="noticias/categoria.php?cat=<?= $cat['slug'] ?>"><?= $cat['nome'] ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Links Úteis</h4>
                    <ul>
                        <li><a href="sobre.php">Sobre nós</a></li>
                        <li><a href="contato.php">Contato</a></li>
                        <li><a href="privacidade.php">Política de Privacidade</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 InovaHub. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>