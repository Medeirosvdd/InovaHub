<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

// Buscar categorias primeiro
$categorias = $pdo->query("SELECT id, nome, slug, cor FROM categorias ORDER BY nome")->fetchAll();

// Obter categoria da URL
$categoria_slug = $_GET['cat'] ?? '';
$categoria = null;

// Encontrar a categoria pelo slug
foreach ($categorias as $cat) {
    if ($cat['slug'] === $categoria_slug) {
        $categoria = $cat;
        break;
    }
}

// Se categoria não encontrada, redirecionar
if (!$categoria) {
    header('Location: ../index.php');
    exit();
}

// Paginação
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$noticias_por_pagina = 9;
$offset = ($pagina - 1) * $noticias_por_pagina;

// CORREÇÃO: Usar valores diretamente na query (sem placeholders para LIMIT/OFFSET)
$sql = "
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome, c.cor AS categoria_cor
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE n.categoria = ? AND n.status = 'publicada'
    ORDER BY n.data DESC
    LIMIT " . intval($noticias_por_pagina) . " OFFSET " . intval($offset);

$stmt = $pdo->prepare($sql);
$stmt->execute([$categoria['id']]);
$noticias = $stmt->fetchAll();

// Buscar total de notícias para paginação
$stmt_total = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM noticias n 
    WHERE n.categoria = ? AND n.status = 'publicada'
");
$stmt_total->execute([$categoria['id']]);
$total_noticias = $stmt_total->fetch()['total'];
$total_paginas = ceil($total_noticias / $noticias_por_pagina);

// Buscar notícias em destaque para sidebar
$stmt_destaques = $pdo->prepare("
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE n.categoria = ? AND n.status = 'publicada' AND n.destaque = 1
    ORDER BY n.data DESC
    LIMIT 5
");
$stmt_destaques->execute([$categoria['id']]);
$destaques = $stmt_destaques->fetchAll();

// Buscar notícias mais lidas
$stmt_mais_lidas = $pdo->prepare("
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE n.categoria = ? AND n.status = 'publicada'
    ORDER BY n.visualizacoes DESC
    LIMIT 5
");
$stmt_mais_lidas->execute([$categoria['id']]);
$mais_lidas = $stmt_mais_lidas->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($categoria['nome']) ?> - InovaHub</title>
    <meta name="description" content="Notícias sobre <?= htmlspecialchars($categoria['nome']) ?> - Fique por dentro das últimas novidades no InovaHub">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.5;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Cabeçalho */
        .topo {
            background-color: #c4170c;
            color: white;
            padding: 10px 0;
            border-bottom: 3px solid #a6140b;
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

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .nav-principal {
            display: flex;
            gap: 20px;
        }

        .nav-principal a {
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }

        .nav-principal a:hover,
        .nav-principal a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-menu {
            position: relative;
        }

        .user-greeting {
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }

        .user-greeting:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .user-menu:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f5f5f5;
        }

        .dropdown-item:last-child {
            border-bottom: none;
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
            cursor: pointer;
        }

        .btn-register {
            background-color: white;
            color: #c4170c;
        }

        .btn-login:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .btn-register:hover {
            background-color: #f0f0f0;
        }

        .btn-theme {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-theme:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Clima no Navbar */
        .clima-navbar-expandido {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 25px;
            color: white;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .clima-navbar-expandido:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .clima-navbar-conteudo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .clima-navbar-principal {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clima-navbar-icone {
            font-size: 20px;
        }

        .clima-navbar-temperatura {
            font-size: 16px;
            font-weight: 700;
        }

        .clima-navbar-detalhes {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            opacity: 0.9;
        }

        .clima-navbar-info {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .clima-navbar-local {
            font-size: 11px;
            opacity: 0.8;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .clima-navbar-carregando {
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0.8;
        }

        .clima-navbar-erro {
            opacity: 0.7;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Barra de busca */
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
            border-color: #c4170c;
        }

        .btn-busca {
            background-color: #c4170c;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-busca:hover {
            background-color: #a6140b;
        }

        /* Cabeçalho da Categoria */
        .categoria-header {
            background: white;
            margin: 40px 0 30px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 6px solid <?= $categoria['cor'] ?>;
        }

        .categoria-titulo {
            font-size: 42px;
            font-weight: 800;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .categoria-descricao {
            font-size: 18px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .categoria-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            font-size: 14px;
            color: #888;
        }

        .categoria-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Layout principal */
        .layout-principal {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        /* Grid de Notícias */
        .grid-noticias-categoria {
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
            background: <?= $categoria['cor'] ?>;
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
            color: #c4170c;
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
            border-color: #c4170c;
            color: #c4170c;
            transform: translateY(-2px);
        }

        .pagina-item.ativa {
            background: #c4170c;
            border-color: #c4170c;
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

        /* Estados vazios */
        .nenhuma-noticia {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            margin: 40px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .nenhuma-noticia h2 {
            color: #666;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .nenhuma-noticia p {
            color: #888;
            margin-bottom: 30px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #c4170c 0%, #a6140b 100%);
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

        /* Sidebar */
        .sidebar-widget {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .sidebar-widget h3 {
            margin-bottom: 20px;
            color: #c4170c;
            font-size: 18px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .mais-lidas-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .mais-lida-item {
            display: flex;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .mais-lida-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .ranking {
            background: #c4170c;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .mais-lida-item .conteudo h4 {
            font-size: 14px;
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .mais-lida-item .meta {
            font-size: 12px;
            color: #666;
            display: flex;
            gap: 10px;
        }

        /* Widget de Clima */
        .clima-widget {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(116, 185, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .clima-widget::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .clima-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .clima-titulo {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .clima-local {
            font-size: 14px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .clima-principal {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .clima-temperatura {
            font-size: 48px;
            font-weight: 700;
            line-height: 1;
        }

        .clima-icone {
            font-size: 64px;
            text-align: center;
        }

        .clima-descricao {
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
            text-transform: capitalize;
        }

        .clima-detalhes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .clima-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .clima-info .valor {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .clima-info .label {
            font-size: 12px;
            opacity: 0.8;
        }

        .clima-carregando {
            text-align: center;
            padding: 20px;
        }

        .clima-erro {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .clima-atualizar {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }

        .clima-atualizar:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .newsletter-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .newsletter-form input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .newsletter-form button {
            background: #c4170c;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .newsletter-form button:hover {
            background: #a6140b;
            transform: translateY(-2px);
        }

        .social-links {
            display: flex;
            gap: 10px;
        }

        .social-link {
            flex: 1;
            padding: 12px;
            text-align: center;
            background: #f5f5f5;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
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
        @media (max-width: 1024px) {
            .layout-principal {
                grid-template-columns: 1fr;
            }

            .grid-noticias-categoria {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }

            .clima-navbar-detalhes {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .nav-principal {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #c4170c;
                flex-direction: column;
                padding: 20px;
            }

            .nav-principal.active {
                display: flex;
            }

            .user-area {
                flex-direction: column;
                gap: 10px;
            }

            .auth-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn-login,
            .btn-register {
                text-align: center;
            }

            .categoria-header {
                padding: 30px 20px;
                margin: 20px 0;
            }

            .categoria-titulo {
                font-size: 32px;
            }

            .categoria-stats {
                flex-direction: column;
                gap: 10px;
            }

            .grid-noticias-categoria {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .clima-navbar-expandido {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }

            .categoria-titulo {
                font-size: 28px;
            }

            .categoria-descricao {
                font-size: 16px;
            }

            .paginacao {
                flex-wrap: wrap;
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
                    <a href="../index.php">InovaHub</a>
                </h1>
                <button class="menu-toggle" id="menu-toggle">☰</button>
            </div>

        

            <div class="user-area">
                <!-- Clima no Navbar -->
                <div class="clima-navbar-expandido" id="climaNavbar" onclick="abrirClimaCompleto()">
                    <div class="clima-navbar-conteudo">
                        <div class="clima-navbar-principal">
                            <span class="clima-navbar-icone" id="climaNavbarIcone">🌤️</span>
                            <span class="clima-navbar-temperatura" id="climaNavbarTemp">--°C</span>
                        </div>
                        <div class="clima-navbar-detalhes">
                            <div class="clima-navbar-info">
                                <span>💨</span>
                                <span id="climaNavbarVento">--</span>
                            </div>
                            <div class="clima-navbar-info">
                                <span>💧</span>
                                <span id="climaNavbarUmidade">--%</span>
                            </div>
                        </div>
                        <div class="clima-navbar-local" id="climaNavbarLocal">📍 Localização</div>
                    </div>
                </div>

                <?php if ($usuario): ?>
                    <div class="user-menu">
                        <span class="user-greeting">Olá, <?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                        <div class="user-dropdown">
                            <?php if (ehAdmin($usuario)): ?>
                                <a href="../admin/index.php" class="dropdown-item">⚙️ Painel Admin</a>
                            <?php elseif (ehEditor($usuario)): ?>
                                <a href="../editor/index.php" class="dropdown-item">✏️ Painel Editor</a>
                            <?php else: ?>
                                <a href="../usuario/dashboard.php" class="dropdown-item">👤 Minha Conta</a>
                            <?php endif; ?>

                            <?php if (podePublicar($usuario)): ?>
                                <a href="../noticias/nova_noticia.php" class="dropdown-item">➕ Publicar Notícia</a>
                            <?php endif; ?>

                            <a href="../auth/logout.php" class="dropdown-item">🚪 Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <button class="btn-login" id="btnAbrirModal">Conta</button>
                    </div>
                <?php endif; ?>

                <button class="btn-theme" id="btn-theme" title="Alternar tema">☀️</button>
            </div>
        </div>
    </header>

    <!-- Barra de busca -->
    <section class="busca-section">
        <div class="container">
            <form method="GET" action="../buscar.php" class="busca-form">
                <input type="text" name="q" placeholder="Buscar notícias de <?= htmlspecialchars($categoria['nome']) ?>..." required>
                <button type="submit" class="btn-busca">🔍 Pesquisar</button>
            </form>
        </div>
    </section>

    <main class="container">
        <!-- Cabeçalho da Categoria -->
        <section class="categoria-header">
            <h1 class="categoria-titulo">
                <span style="color: <?= $categoria['cor'] ?>">●</span>
                <?= htmlspecialchars($categoria['nome']) ?>
            </h1>
            <p class="categoria-descricao">
                Todas as notícias sobre <?= htmlspecialchars($categoria['nome']) ?> em um só lugar.
                Fique por dentro das últimas novidades e tendências.
            </p>
            <div class="categoria-stats">
                <div class="categoria-stat">
                    <span>📰</span>
                    <span><?= $total_noticias ?> notícias</span>
                </div>
                <div class="categoria-stat">
                    <span>📄</span>
                    <span>Página <?= $pagina ?> de <?= $total_paginas ?></span>
                </div>
            </div>
        </section>

        <div class="layout-principal">
            <!-- Conteúdo Principal -->
            <div class="conteudo-principal">
                <?php if (!empty($noticias)): ?>
                    <!-- Grid de Notícias -->
                    <div class="grid-noticias-categoria">
                        <?php foreach ($noticias as $noticia): ?>
                            <article class="noticia-card">
                                <a href="../noticia.php?slug=<?= $noticia['slug'] ?>">
                                    <div class="noticia-imagem">
                                        <img src="../uploads/noticias/<?= $noticia['imagem'] ?>"
                                            alt="<?= htmlspecialchars($noticia['titulo']) ?>"
                                            onerror="this.src='../assets/img/defaults/noticia.jpg'">
                                        <span class="categoria-badge"><?= $noticia['categoria_nome'] ?></span>
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
                                <a href="?cat=<?= $categoria_slug ?>&pagina=<?= $pagina - 1 ?>" class="pagina-item">
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
                                    <a href="?cat=<?= $categoria_slug ?>&pagina=<?= $i ?>" class="pagina-item">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Próxima Página -->
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="?cat=<?= $categoria_slug ?>&pagina=<?= $pagina + 1 ?>" class="pagina-item">
                                    Próxima →
                                </a>
                            <?php else: ?>
                                <span class="pagina-item desabilitada">Próxima →</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Se não houver notícias -->
                    <section class="nenhuma-noticia">
                        <h2>📭 Nenhuma notícia encontrada</h2>
                        <p>Não há notícias publicadas na categoria <?= htmlspecialchars($categoria['nome']) ?> ainda.</p>
                        <a href="../index.php" class="btn-primary">
                            <span>🏠</span>
                            Voltar para a Página Inicial
                        </a>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Widget de Clima -->
                <section class="clima-widget">
                    <div class="clima-header">
                        <h3 class="clima-titulo">🌤️ Clima Agora</h3>
                        <button class="clima-atualizar" onclick="buscarClima()">🔄</button>
                    </div>

                    <div id="clima-conteudo">
                        <div class="clima-carregando">
                            <p>📍 Detectando sua localização...</p>
                        </div>
                    </div>
                </section>

                <!-- Destaques da Categoria -->
                <?php if (!empty($destaques)): ?>
                    <section class="sidebar-widget">
                        <h3>🔥 Em Destaque</h3>
                        <div class="mais-lidas-list">
                            <?php foreach ($destaques as $index => $noticia): ?>
                                <article class="mais-lida-item">
                                    <span class="ranking"><?= $index + 1 ?></span>
                                    <div class="conteudo">
                                        <a href="../noticia.php?slug=<?= $noticia['slug'] ?>">
                                            <h4><?= htmlspecialchars($noticia['titulo']) ?></h4>
                                        </a>
                                        <div class="meta">
                                            <span class="autor"><?= $noticia['autor_nome'] ?></span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Mais Lidas -->
                <?php if (!empty($mais_lidas)): ?>
                    <section class="sidebar-widget">
                        <h3>📈 Mais Lidas</h3>
                        <div class="mais-lidas-list">
                            <?php foreach ($mais_lidas as $index => $noticia): ?>
                                <article class="mais-lida-item">
                                    <span class="ranking"><?= $index + 1 ?></span>
                                    <div class="conteudo">
                                        <a href="../noticia.php?slug=<?= $noticia['slug'] ?>">
                                            <h4><?= htmlspecialchars($noticia['titulo']) ?></h4>
                                        </a>
                                        <div class="meta">
                                            <span class="categoria"><?= $noticia['categoria_nome'] ?></span>
                                            <span class="visualizacoes"><?= $noticia['total_visualizacoes'] ?> views</span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Newsletter -->
                <section class="sidebar-widget newsletter-widget">
                    <h3>📧 Newsletter</h3>
                    <p>Receba as principais notícias de <?= htmlspecialchars($categoria['nome']) ?> no seu email.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Seu melhor email" required>
                        <button type="submit">Assinar</button>
                    </form>
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
                            <li>
                                <a href="categoria.php?cat=<?= $cat['slug'] ?>" style="color: <?= $cat['cor'] ?>">
                                    <?= $cat['nome'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Links Úteis</h4>
                    <ul>
                        <li><a href="../sobre.php">Sobre nós</a></li>
                        <li><a href="../contato.php">Contato</a></li>
                        <li><a href="../privacidade.php">Política de Privacidade</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 InovaHub. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Menu mobile toggle
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('nav-principal').classList.toggle('active');
        });

        // Sistema de Clima (mesmo código do index.php)
        function buscarClima() {
            const climaConteudo = document.getElementById('clima-conteudo');
            climaConteudo.innerHTML = '<div class="clima-carregando"><p>📍 Detectando sua localização...</p></div>';

            document.getElementById('climaNavbar').innerHTML = `
                <div class="clima-navbar-carregando">
                    <span>🔄</span>
                    <span>Carregando clima...</span>
                </div>
            `;

            if (!navigator.geolocation) {
                climaConteudo.innerHTML = '<div class="clima-erro"><p>❌ Seu navegador não suporta geolocalização</p></div>';
                mostrarErroClimaNavbar();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                        const lat = position.coords.latitude.toFixed(4);
                        const lon = position.coords.longitude.toFixed(4);

                        try {
                            const response = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true&timezone=auto`);

                            if (!response.ok) throw new Error(`API retornou status ${response.status}`);

                            const data = await response.json();
                            exibirClima(data, lat, lon);

                        } catch (error) {
                            console.error('Erro ao buscar clima:', error);
                            try {
                                const fallbackResponse = await fetch('https://api.open-meteo.com/v1/forecast?latitude=-23.55&longitude=-46.63&current_weather=true');
                                const fallbackData = await fallbackResponse.json();
                                exibirClima(fallbackData, -23.55, -46.63, 'São Paulo');
                            } catch (fallbackError) {
                                console.error('Erro no fallback:', fallbackError);
                                climaConteudo.innerHTML = `
                                <div class="clima-erro">
                                    <p>🌤️ Serviço de clima indisponível</p>
                                    <button class="clima-atualizar" onclick="buscarClima()" style="margin-top: 10px;">🔄 Tentar Novamente</button>
                                </div>
                            `;
                                mostrarErroClimaNavbar();
                            }
                        }
                    },
                    (error) => {
                        console.error('Erro de geolocalização:', error);
                        let mensagem = 'Erro ao obter localização';

                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                mensagem = 'Permissão de localização negada';
                                buscarClimaPorCidade('São Paulo');
                                return;
                            case error.POSITION_UNAVAILABLE:
                                mensagem = 'Localização indisponível';
                                buscarClimaPorCidade('Rio de Janeiro');
                                return;
                            case error.TIMEOUT:
                                mensagem = 'Tempo esgotado';
                                buscarClimaPorCidade('Brasília');
                                return;
                        }

                        climaConteudo.innerHTML = `<div class="clima-erro"><p>📍 ${mensagem}</p></div>`;
                        mostrarErroClimaNavbar();
                    }, {
                        timeout: 10000,
                        enableHighAccuracy: false
                    }
            );
        }

        async function buscarClimaPorCidade(cidade) {
            const coordenadas = {
                'São Paulo': {
                    lat: -23.55,
                    lon: -46.63
                },
                'Rio de Janeiro': {
                    lat: -22.91,
                    lon: -43.17
                },
                'Brasília': {
                    lat: -15.78,
                    lon: -47.93
                }
            };

            const coord = coordenadas[cidade] || coordenadas['São Paulo'];

            try {
                const weatherResponse = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${coord.lat}&longitude=${coord.lon}&current_weather=true`);
                const weatherData = await weatherResponse.json();
                exibirClima(weatherData, coord.lat, coord.lon, cidade);
            } catch (error) {
                mostrarErroClimaNavbar();
            }
        }

        function exibirClima(data, lat, lon, cidadeForcada = null) {
            const climaConteudo = document.getElementById('clima-conteudo');

            if (!data.current_weather) {
                climaConteudo.innerHTML = `
                    <div class="clima-erro">
                        <p>❌ Dados do clima indisponíveis</p>
                        <button class="clima-atualizar" onclick="buscarClima()">🔄 Tentar Novamente</button>
                    </div>
                `;
                mostrarErroClimaNavbar();
                return;
            }

            const temperatura = Math.round(data.current_weather.temperature);
            const codigoTempo = data.current_weather.weathercode;
            const velocidadeVento = data.current_weather.windspeed;

            const icone = obterIconeClima(codigoTempo);
            const descricao = obterDescricaoClima(codigoTempo);
            const localizacao = cidadeForcada || 'Sua Localização';

            climaConteudo.innerHTML = `
                <div class="clima-local">
                    <span>📍 ${localizacao}</span>
                </div>
                <div class="clima-principal">
                    <div class="clima-temperatura">${temperatura}°C</div>
                    <div class="clima-icone">${icone}</div>
                </div>
                <div class="clima-descricao">${descricao}</div>
                <div class="clima-detalhes">
                    <div class="clima-info">
                        <div class="valor">${velocidadeVento} km/h</div>
                        <div class="label">Vento</div>
                    </div>
                    <div class="clima-info">
                        <div class="valor">${Math.round(temperatura + 2)}°C</div>
                        <div class="label">Sensação</div>
                    </div>
                </div>
            `;

            atualizarClimaNavbar(data, cidadeForcada);
        }

        function atualizarClimaNavbar(data, cidadeForcada = null) {
            if (!data.current_weather) return;

            const temperatura = Math.round(data.current_weather.temperature);
            const codigoTempo = data.current_weather.weathercode;
            const velocidadeVento = data.current_weather.windspeed;

            const icone = obterIconeClima(codigoTempo);
            const localizacao = cidadeForcada || 'Sua Localização';

            document.getElementById('climaNavbar').innerHTML = `
                <div class="clima-navbar-conteudo">
                    <div class="clima-navbar-principal">
                        <span class="clima-navbar-icone">${icone}</span>
                        <span class="clima-navbar-temperatura">${temperatura}°C</span>
                    </div>
                    <div class="clima-navbar-detalhes">
                        <div class="clima-navbar-info">
                            <span>💨</span>
                            <span>${velocidadeVento}km/h</span>
                        </div>
                        <div class="clima-navbar-info">
                            <span>💧</span>
                            <span>${Math.round(temperatura + 5)}%</span>
                        </div>
                    </div>
                    <div class="clima-navbar-local">📍 ${localizacao}</div>
                </div>
            `;
        }

        function mostrarErroClimaNavbar() {
            document.getElementById('climaNavbar').innerHTML = `
                <div class="clima-navbar-erro">
                    <span>❌</span>
                    <span>Clima</span>
                </div>
            `;
        }

        function obterIconeClima(codigo) {
            const icones = {
                0: '☀️',
                1: '🌤️',
                2: '⛅',
                3: '☁️',
                45: '🌫️',
                48: '🌫️',
                51: '🌦️',
                53: '🌦️',
                55: '🌦️',
                61: '🌧️',
                63: '🌧️',
                65: '🌧️',
                80: '🌦️',
                81: '🌦️',
                82: '🌦️',
                95: '⛈️',
                96: '⛈️',
                99: '⛈️'
            };
            return icones[codigo] || '🌤️';
        }

        function obterDescricaoClima(codigo) {
            const descricoes = {
                0: 'Céu limpo',
                1: 'Principalmente limpo',
                2: 'Parcialmente nublado',
                3: 'Nublado',
                45: 'Nevoeiro',
                48: 'Nevoeiro com geada',
                51: 'Chuvisco leve',
                53: 'Chuvisco moderado',
                55: 'Chuvisco denso',
                61: 'Chuva leve',
                63: 'Chuva moderada',
                65: 'Chuva forte',
                80: 'Aguaceiros leves',
                81: 'Aguaceiros moderados',
                82: 'Aguaceiros fortes',
                95: 'Tempestade',
                96: 'Tempestade com granizo',
                99: 'Tempestade forte com granizo'
            };
            return descricoes[codigo] || 'Condições climáticas';
        }

        function abrirClimaCompleto() {
            alert('🌤️ Clima completo: ' + document.getElementById('climaNavbarLocal').textContent);
        }

        // Buscar clima automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(buscarClima, 1000);
        });
    </script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>