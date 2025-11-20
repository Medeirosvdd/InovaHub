<?php
session_start();
require 'includes/conexao.php';
require 'includes/funcoes.php';

$termo = $_GET['q'] ?? '';
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$limite = 9;
$offset = ($pagina - 1) * $limite;

$usuario = usuarioLogado($pdo);

// Buscar categorias para o menu
$categorias = $pdo->query("SELECT id, nome, slug, cor FROM categorias ORDER BY nome")->fetchAll();

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
$sql_noticias = "
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome, c.cor AS categoria_cor
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE $where
    ORDER BY n.data DESC
    LIMIT " . intval($limite) . " OFFSET " . intval($offset);

$stmt = $pdo->prepare($sql_noticias);
$stmt->execute($params);
$noticias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar: "<?= htmlspecialchars($termo) ?>" - InovaHub</title>
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

        /* Header */
        .topo {
            background-color: var(--primary);
            color: white;
            padding: 10px 0;
            border-bottom: 3px solid var(--primary-dark);
        }

        .topo-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: -1px;
        }

        .logo a {
            color: white;
            text-decoration: none;
        }

        .nav-principal {
            display: flex;
            gap: 20px;
        }

        .nav-principal a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .nav-principal a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-greeting {
            color: white;
        }

        .auth-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-login,
        .btn-register {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-login {
            color: white;
            border: 1px solid white;
            background: none;
        }

        .btn-register {
            background-color: white;
            color: var(--primary);
        }

        .btn-login:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .btn-register:hover {
            background-color: #f0f0f0;
        }

        /* Busca Section */
        .busca-section {
            background-color: white;
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .busca-form {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
        }

        .busca-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-right: none;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
            transition: border-color 0.2s;
        }

        .busca-form input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-busca {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-busca:hover {
            background-color: var(--primary-dark);
        }

        /* Resultados */
        .busca-resultados {
            padding: 40px 0;
        }

        .titulo-busca {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--secondary);
            text-align: center;
        }

        .total-resultados {
            color: #666;
            text-align: center;
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        .nenhum-resultado {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            margin: 40px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .nenhum-resultado h2 {
            color: #666;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .nenhum-resultado p {
            color: #888;
            margin-bottom: 30px;
        }

        /* Grid de Notícias */
        .grid-noticias {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .noticia-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }

        .noticia-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .noticia-imagem {
            height: 200px;
            position: relative;
            overflow: hidden;
        }

        .noticia-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .noticia-card:hover .noticia-imagem img {
            transform: scale(1.05);
        }

        .categoria-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .noticia-conteudo {
            padding: 25px;
        }

        .noticia-conteudo h3 {
            font-size: 18px;
            margin-bottom: 12px;
            line-height: 1.4;
            color: #333;
        }

        .noticia-resumo {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .noticia-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #888;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .noticia-autor {
            font-weight: 600;
            color: var(--primary);
        }

        .noticia-data {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Paginação */
        .paginacao {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 50px 0;
            padding: 20px;
        }

        .pagina-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagina-item:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .pagina-item.ativa {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .pagina-item.desabilitada {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagina-item.desabilitada:hover {
            border-color: #e0e0e0;
            color: #666;
            transform: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(196, 23, 12, 0.3);
        }

        /* Footer */
        .footer {
            background: #333;
            color: white;
            padding: 40px 0 20px;
            margin-top: 40px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h3,
        .footer-section h4 {
            margin-bottom: 20px;
            color: white;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 10px;
        }

        .footer-section a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #444;
            padding-top: 20px;
            text-align: center;
            color: #999;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .topo-container {
                flex-direction: column;
                gap: 15px;
            }

            .nav-principal {
                flex-wrap: wrap;
                justify-content: center;
            }

            .grid-noticias {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .titulo-busca {
                font-size: 2rem;
            }

            .paginacao {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }

            .titulo-busca {
                font-size: 1.8rem;
            }

            .pagina-item {
                min-width: 35px;
                height: 35px;
                padding: 0 10px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="topo">
        <div class="topo-container">
            <div class="logo-area">
                <h1 class="logo">
                    <a href="index.php">InovaHub</a>
                </h1>
            </div>

            <nav class="nav-principal">
                <a href="index.php">Início</a>
                <?php foreach ($categorias as $cat): ?>
                    <a href="noticias/categoria.php?cat=<?= $cat['slug'] ?>" style="color: <?= $cat['cor'] ?>">
                        <?= $cat['nome'] ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="user-area">
                <?php if ($usuario): ?>
                    <span class="user-greeting">Olá, <?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                    <?php if (ehAdmin($usuario)): ?>
                        <a href="admin/index.php" class="btn-login">Painel Admin</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="btn-login">Sair</a>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="auth/login.php" class="btn-login">Entrar</a>
                        <a href="auth/registro.php" class="btn-register">Cadastrar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Barra de busca -->
    <section class="busca-section">
        <div class="container">
            <form method="GET" action="buscar.php" class="busca-form">
                <input type="text" name="q" value="<?= htmlspecialchars($termo) ?>" placeholder="Buscar notícias..." required>
                <button type="submit" class="btn-busca">🔍 Pesquisar</button>
            </form>
        </div>
    </section>

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
                    <h2>📭 Nenhuma notícia encontrada</h2>
                    <p>Tente buscar com outros termos ou voltar para a página inicial.</p>
                    <a href="index.php" class="btn-primary">
                        <span>🏠</span>
                        Voltar para a Página Inicial
                    </a>
                </div>
            <?php else: ?>
                <div class="grid-noticias">
                    <?php foreach ($noticias as $noticia): ?>
                        <article class="noticia-card">
                            <a href="noticia.php?slug=<?= $noticia['slug'] ?>">
                                <div class="noticia-imagem">
                                    <img src="uploads/noticias/<?= $noticia['imagem'] ?>"
                                        alt="<?= htmlspecialchars($noticia['titulo']) ?>"
                                        onerror="this.src='assets/img/defaults/noticia.jpg'">
                                    <span class="categoria-badge" style="background: <?= $noticia['categoria_cor'] ?>">
                                        <?= $noticia['categoria_nome'] ?>
                                    </span>
                                </div>
                                <div class="noticia-conteudo">
                                    <h3><?= htmlspecialchars($noticia['titulo']) ?></h3>
                                    <p class="noticia-resumo"><?= htmlspecialchars($noticia['resumo']) ?></p>
                                    <div class="noticia-meta">
                                        <span class="noticia-autor"><?= $noticia['autor_nome'] ?></span>
                                        <span class="noticia-data">
                                            <span>📅</span>
                                            <span><?= formatarData($noticia['data']) ?></span>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <div class="paginacao">
                        <!-- Página Anterior -->
                        <?php if ($pagina > 1): ?>
                            <a href="buscar.php?q=<?= urlencode($termo) ?>&pagina=<?= $pagina - 1 ?>" class="pagina-item">
                                ← Anterior
                            </a>
                        <?php else: ?>
                            <span class="pagina-item desabilitada">← Anterior</span>
                        <?php endif; ?>

                        <!-- Números das Páginas -->
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <?php if ($i == $pagina): ?>
                                <span class="pagina-item ativa"><?= $i ?></span>
                            <?php else: ?>
                                <a href="buscar.php?q=<?= urlencode($termo) ?>&pagina=<?= $i ?>" class="pagina-item">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Próxima Página -->
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="buscar.php?q=<?= urlencode($termo) ?>&pagina=<?= $pagina + 1 ?>" class="pagina-item">
                                Próxima →
                            </a>
                        <?php else: ?>
                            <span class="pagina-item desabilitada">Próxima →</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
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
                            <li>
                                <a href="noticias/categoria.php?cat=<?= $cat['slug'] ?>" style="color: <?= $cat['cor'] ?>">
                                    <?= $cat['nome'] ?>
                                </a>
                            </li>
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
</body>

</html>