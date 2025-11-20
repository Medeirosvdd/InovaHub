<?php
session_start();
require 'includes/conexao.php';
require 'includes/funcoes.php';

// Processar login do modal
if ($_POST['modal_login'] ?? false) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $user = buscarUsuarioPorEmail($pdo, $email);

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_tipo'] = $user['tipo'];

        // Fechar modal via JavaScript
        echo "<script>
        document.getElementById('modalLogin').style.display = 'none';
        location.reload();
    </script>";
        exit();
    }
} else {
    $erro_login = "E-mail ou senha inválidos.";
}

// No início do index.php, onde processa o login do modal
if ($_POST['modal_login'] ?? false) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $user = buscarUsuarioPorEmail($pdo, $email);

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_tipo'] = $user['tipo'];

        // Fechar modal e recarregar a página (não redirecionar)
        echo "<script>
            document.getElementById('modalLogin').style.display = 'none';
            location.reload();
        </script>";
        exit();
    } else {
        $erro_login = "E-mail ou senha inválidos.";
    }
}

$usuario = usuarioLogado($pdo);

// Buscar categorias primeiro (para evitar erro)
$categorias = $pdo->query("SELECT id, nome, slug, cor FROM categorias ORDER BY nome")->fetchAll();

// Buscar manchete principal
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
$destaques = [];
if ($manchete) {
    $stmt = $pdo->prepare("
        SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome
        FROM noticias n
        JOIN usuarios u ON u.id = n.autor
        JOIN categorias c ON c.id = n.categoria
        WHERE n.status = 'publicada' AND n.id != ?
        ORDER BY n.data DESC 
        LIMIT 3
    ");
    $stmt->execute([$manchete['id']]);
    $destaques = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar TODAS as notícias por categoria (para sistema Ver Mais)
$todas_noticias_por_categoria = [];
foreach ($categorias as $cat) {
    $stmt = $pdo->prepare("
        SELECT n.*, u.nome AS autor_nome 
        FROM noticias n 
        JOIN usuarios u ON u.id = n.autor 
        WHERE n.categoria = ? AND n.status = 'publicada' 
        ORDER BY n.data DESC 
        LIMIT 6
    ");
    $stmt->execute([$cat['id']]);
    $noticias = $stmt->fetchAll();
    if (!empty($noticias)) {
        $todas_noticias_por_categoria[$cat['nome']] = $noticias;
    }
}

// Notícias mais lidas da semana
$mais_lidas = $pdo->query("
    SELECT n.*, u.nome AS autor_nome, c.nome AS categoria_nome,
        COALESCE(SUM(e.visualizacoes), 0) as total_visualizacoes
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    LEFT JOIN estatisticas e ON e.noticia_id = n.id AND e.data >= CURDATE() - INTERVAL 7 DAY
    WHERE n.status = 'publicada'
    GROUP BY n.id
    ORDER BY total_visualizacoes DESC, n.data DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Função para gerar slug
function slugify($text)
{
    $slug = preg_replace('/[^a-z0-9]/i', '-', $text);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return strtolower($slug);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InovaHub - Tecnologia e Inovação</title>
    <meta name="description" content="Portal de notícias sobre tecnologia, inovação, startups e inteligência artificial. Fique por dentro das últimas novidades do mundo tech.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/theme.css">
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

        /* Manchete principal */
        .manchete-principal {
            margin: 40px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .manchete-link {
            display: grid;
            grid-template-columns: 1fr 1fr;
            text-decoration: none;
            color: inherit;
        }

        .manchete-imagem {
            position: relative;
            height: 400px;
        }

        .manchete-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .categoria-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #c4170c;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .manchete-conteudo {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .manchete-titulo {
            font-size: 36px;
            line-height: 1.2;
            margin-bottom: 20px;
            color: #333;
        }

        .manchete-resumo {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        /* === TEMA ESCURO === */
        /* === TEMA ESCURO === */
body.dark-mode {
    background-color: #1a1a1a;
    color: #e0e0e0;
}

body.dark-mode .topo {
    background-color: #2d1b1b;
    border-bottom-color: #3d2222;
}

body.dark-mode .busca-section {
    background-color: #2d2d2d;
    border-bottom-color: #404040;
}

body.dark-mode .busca-form input {
    background-color: #404040;
    border-color: #555;
    color: #e0e0e0;
}

body.dark-mode .busca-form input:focus {
    border-color: #c4170c;
}

body.dark-mode .manchete-principal,
body.dark-mode .destaque-card,
body.dark-mode .noticia-card,
body.dark-mode .sidebar-widget {
    background-color: #2d2d2d;
    color: #e0e0e0;
}

body.dark-mode .manchete-titulo,
body.dark-mode .destaque-conteudo h3,
body.dark-mode .noticia-conteudo h3 {
    color: #ffffff;
}

body.dark-mode .manchete-resumo,
body.dark-mode .noticia-resumo {
    color: #b0b0b0;
}

body.dark-mode .user-dropdown {
    background: #2d2d2d;
    border: 1px solid #404040;
}

body.dark-mode .dropdown-item {
    color: #e0e0e0;
    border-bottom-color: #404040;
}

body.dark-mode .dropdown-item:hover {
    background-color: #404040;
}

body.dark-mode .footer {
    background: #1a1a1a;
}

body.dark-mode .footer-section a {
    color: #b0b0b0;
}

body.dark-mode .footer-section a:hover {
    color: #ffffff;
}

body.dark-mode .modal-content {
    background: linear-gradient(135deg, #2d1b1b 0%, #3d2222 100%);
}

body.dark-mode .modal-body {
    background: #2d2d2d;
    color: #e0e0e0;
}

body.dark-mode .login-form input {
    background: #404040;
    border-color: #555;
    color: #e0e0e0;
}

body.dark-mode .login-form input:focus {
    background: #4a4a4a;
    border-color: #c4170c;
}

body.dark-mode .login-form input::placeholder {
    color: #999;
}

body.dark-mode .social-link {
    background: #404040;
    color: #e0e0e0;
}

body.dark-mode .social-link:hover {
    background: #555;
}

body.dark-mode .manchete-meta,
body.dark-mode .destaque-meta,
body.dark-mode .noticia-meta,
body.dark-mode .mais-lida-item .meta {
    color: #b0b0b0;
}

body.dark-mode .newsletter-form input {
    background: #404040;
    border-color: #555;
    color: #e0e0e0;
}

body.dark-mode .newsletter-form input::placeholder {
    color: #999;
}

/* Transições suaves para todos os elementos */
body,
body.dark-mode .topo,
body.dark-mode .busca-section,
body.dark-mode .manchete-principal,
body.dark-mode .destaque-card,
body.dark-mode .noticia-card,
body.dark-mode .sidebar-widget,
body.dark-mode .footer,
body.dark-mode .modal-body,
body.dark-mode .manchete-titulo,
body.dark-mode .manchete-resumo,
body.dark-mode .manchete-meta,
body.dark-mode .destaque-meta,
body.dark-mode .noticia-meta,
body.dark-mode .noticia-resumo,
body.dark-mode .mais-lida-item .meta {
    transition: all 0.3s ease;
}
        .manchete-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #666;
        }

        /* Destaques secundários */
        .destaques-secundarios {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }

        .destaque-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .destaque-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .destaque-imagem {
            height: 200px;
        }

        .destaque-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .destaque-conteudo {
            padding: 20px;
        }

        .destaque-conteudo h3 {
            font-size: 18px;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .destaque-meta {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
        }

        /* Layout principal */
        .layout-principal {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        /* Conteúdo principal */
        .categoria-section {
            margin-bottom: 40px;
        }

        .titulo-categoria {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c4170c;
            font-size: 24px;
        }

        .ver-tudo {
            font-size: 14px;
            color: #c4170c;
            text-decoration: none;
            font-weight: 500;
        }

        .ver-tudo:hover {
            text-decoration: underline;
        }

        .grid-noticias {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }

        .noticia-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .noticia-card:hover {
            transform: translateY(-2px);
        }

        .noticia-imagem {
            height: 180px;
        }

        .noticia-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .noticia-conteudo {
            padding: 20px;
        }

        .noticia-conteudo h3 {
            font-size: 18px;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .noticia-resumo {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .noticia-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
        }

        /* Sistema Ver Mais */
        .noticias-adicionais {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .noticias-adicionais.mostrar {
            display: block;
        }

        .ver-mais-container {
            text-align: center;
            margin: 40px 0;
            padding: 20px;
        }

        .btn-ver-mais {
            background: linear-gradient(135deg, #c4170c 0%, #a6140b 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(196, 23, 12, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-ver-mais:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(196, 23, 12, 0.4);
            background: linear-gradient(135deg, #d5180d 0%, #b7150c 100%);
        }

        .btn-ver-mais:active {
            transform: translateY(-1px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Sidebar */
        .sidebar-widget {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .sidebar-widget h3 {
            margin-bottom: 20px;
            color: #c4170c;
            font-size: 18px;
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
            border-bottom: 1px solid #eee;
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

        /* Widget de Clima na Sidebar */
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
            border-radius: 4px;
            font-size: 14px;
        }

        .newsletter-form button {
            background: #c4170c;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .social-links {
            display: flex;
            gap: 10px;
        }

        .social-link {
            flex: 1;
            padding: 10px;
            text-align: center;
            background: #f5f5f5;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .social-link:hover {
            background: #e0e0e0;
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

        /* Estados vazios */
        .nenhuma-noticia {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 8px;
            margin: 40px 0;
        }

        .nenhuma-noticia h2 {
            color: #666;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: #c4170c;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            margin-top: 20px;
        }

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
        }

        .modal-content {
            background: white;
            margin: 15% auto;
            padding: 30px;
            width: 400px;
            border-radius: 8px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
        }

        .login-form .form-group {
            margin-bottom: 1rem;
        }

        .login-form input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
        }

        .btn-login-modal {
            width: 100%;
            padding: 12px;
            background: #c4170c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        /* Modal melhorado */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            opacity: 1;
        }

        .modal-content {
            background: linear-gradient(135deg, #c4170c 0%, #a6140b 100%);
            margin: 10% auto;
            padding: 0;
            width: 400px;
            border-radius: 15px;
            position: relative;
            transform: translateY(-50px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .modal.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #ffa726, #4ecdc4);
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-header h2 {
            color: white;
            margin: 0;
            font-size: 28px;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .modal-body {
            padding: 30px;
            background: white;
        }

        .login-form .form-group {
            margin-bottom: 20px;
        }

        .login-form input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .login-form input:focus {
            outline: none;
            border-color: #c4170c;
            background: white;
            box-shadow: 0 0 0 3px rgba(196, 23, 12, 0.1);
            transform: translateY(-2px);
        }

        .login-form input::placeholder {
            color: #999;
        }

        .btn-login-modal {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #c4170c 0%, #a6140b 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(196, 23, 12, 0.4);
            background: linear-gradient(135deg, #d5180d 0%, #b7150c 100%);
        }

        .btn-login-modal:active {
            transform: translateY(0);
        }

        .erro {
            background: rgba(248, 215, 218, 0.9);
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #dc3545;
            animation: shake 0.5s ease-in-out;
        }

        /* Responsividade */
        @media (max-width: 1024px) {
            .manchete-link {
                grid-template-columns: 1fr;
            }

            .manchete-imagem {
                height: 300px;
            }

            .destaques-secundarios {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-noticias {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr 1fr;
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

            .destaques-secundarios {
                grid-template-columns: 1fr;
            }

            .layout-principal {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .manchete-titulo {
                font-size: 28px;
            }

            .modal-content {
                width: 90%;
                margin: 10% auto;
            }

            .clima-navbar-expandido {
                display: none;
            }

            .btn-ver-mais {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }

            .manchete-titulo {
                font-size: 24px;
            }

            .manchete-conteudo {
                padding: 20px;
            }

            .manchete-meta {
                flex-direction: column;
                gap: 10px;
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
                            <!-- Link do dashboard baseado no tipo de usuário -->
                            <?php if (ehAdmin($usuario)): ?>
                                <a href="admin/index.php" class="dropdown-item">⚙️ Painel Admin</a>
                            <?php elseif (ehEditor($usuario)): ?>
                                <a href="editor/index.php" class="dropdown-item">✏️ Painel Editor</a>
                            <?php else: ?>
                                <a href="usuario/dashboard.php" class="dropdown-item">👤 Minha Conta</a>
                            <?php endif; ?>

                            <!-- Só mostra "Publicar" para editores e admins -->
                            <?php if (podePublicar($usuario)): ?>
                                <a href="noticias/nova_noticia.php" class="dropdown-item">➕ Publicar Notícia</a>
                            <?php endif; ?>

                            <a href="auth/logout.php" class="dropdown-item">🚪 Sair</a>
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

        <!-- Modal Login -->
        <div id="modalLogin" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="modal-header">
                    <h2>🔐 Acessar Conta</h2>
                </div>
                <div class="modal-body">
                    <?php if (isset($erro_login)): ?>
                        <div class="erro"><?= $erro_login ?></div>
                    <?php endif; ?>

                    <form method="post" class="login-form">
                        <input type="hidden" name="modal_login" value="1">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="📧 Seu email" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="senha" placeholder="🔒 Sua senha" required>
                        </div>
                        <button type="submit" class="btn-login-modal">🎯 Entrar na Conta</button>
                    </form>

                    <div style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
                        <p>Não tem conta? <a href="auth/cadastro.php" style="color: #c4170c; font-weight: 500;">Cadastre-se aqui</a></p>
                    </div>
                </div>
            </div>
        </div>
        <script src="assets/js/modal.js"></script>

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
        <?php if ($manchete): ?>
            <!-- Manchete Principal -->
            <section class="manchete-principal">
                <a href="noticia.php?slug=<?= $manchete['slug'] ?>" class="manchete-link">
                    <div class="manchete-imagem">
                        <img src="uploads/noticias/<?= $manchete['imagem'] ?>"
                            alt="<?= htmlspecialchars($manchete['titulo']) ?>"
                            onerror="this.src='assets/img/defaults/noticia.jpg'">
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
            <?php if (!empty($destaques)): ?>
                <section class="destaques-secundarios">
                    <?php foreach ($destaques as $destaque): ?>
                        <article class="destaque-card">
                            <a href="noticia.php?slug=<?= $destaque['slug'] ?>">
                                <div class="destaque-imagem">
                                    <img src="uploads/noticias/<?= $destaque['imagem'] ?>"
                                        alt="<?= htmlspecialchars($destaque['titulo']) ?>"
                                        onerror="this.src='assets/img/defaults/noticia.jpg'">
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
            <?php endif; ?>
        <?php else: ?>
            <!-- Se não houver notícias -->
            <section class="nenhuma-noticia">
                <h2>📭 Nenhuma notícia publicada ainda</h2>
                <p>Seja o primeiro a publicar uma notícia!</p>
                <?php if ($usuario && ehEditor($usuario)): ?>
                    <a href="noticias/nova_noticia.php" class="btn-primary">📝 Publicar Primeira Notícia</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <div class="layout-principal">
            <!-- Conteúdo Principal -->
            <div class="conteudo-principal">
                <!-- Notícias por Categoria -->
                <?php foreach ($todas_noticias_por_categoria as $categoria_nome => $noticias_cat): ?>
                    <section class="categoria-section">
                        <h2 class="titulo-categoria">
                            <?= $categoria_nome ?>
                            <a href="noticias/categoria.php?cat=<?= slugify($categoria_nome) ?>" class="ver-tudo">
                                Ver tudo →
                            </a>
                        </h2>

                        <!-- Notícias Visíveis (primeiras 3) -->
                        <div class="grid-noticias">
                            <?php foreach (array_slice($noticias_cat, 0, 3) as $noticia): ?>
                                <article class="noticia-card">
                                    <a href="noticia.php?slug=<?= $noticia['slug'] ?>">
                                        <div class="noticia-imagem">
                                            <img src="uploads/noticias/<?= $noticia['imagem'] ?>"
                                                alt="<?= htmlspecialchars($noticia['titulo']) ?>"
                                                onerror="this.src='assets/img/defaults/noticia.jpg'">
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

                        <!-- Notícias Adicionais (ocultas inicialmente) -->
                        <?php if (count($noticias_cat) > 3): ?>
                            <div class="noticias-adicionais" id="noticias-<?= slugify($categoria_nome) ?>">
                                <div class="grid-noticias">
                                    <?php foreach (array_slice($noticias_cat, 3) as $noticia): ?>
                                        <article class="noticia-card">
                                            <a href="noticia.php?slug=<?= $noticia['slug'] ?>">
                                                <div class="noticia-imagem">
                                                    <img src="uploads/noticias/<?= $noticia['imagem'] ?>"
                                                        alt="<?= htmlspecialchars($noticia['titulo']) ?>"
                                                        onerror="this.src='assets/img/defaults/noticia.jpg'">
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
                            </div>

                            <!-- Botão Ver Mais -->
                            <div class="ver-mais-container">
                                <button class="btn-ver-mais" onclick="toggleNoticias('<?= slugify($categoria_nome) ?>', this)">
                                    <span>📰</span>
                                    <span class="btn-text">Ver Mais Notícias</span>
                                    <span>⬇️</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
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

                <!-- Mais Lidas -->
                <?php if (!empty($mais_lidas)): ?>
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
                                            <span class="visualizacoes"><?= $noticia['total_visualizacoes'] ?> visualizações</span>
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

    <script>
        // Menu mobile toggle
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('nav-principal').classList.toggle('active');
        });

        // Sistema de Clima
        function buscarClima() {
            const climaConteudo = document.getElementById('clima-conteudo');
            climaConteudo.innerHTML = '<div class="clima-carregando"><p>📍 Detectando sua localização...</p></div>';

            // Mostrar loading no navbar
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
                            // Fallback para coordenadas fixas (São Paulo)
                            try {
                                const fallbackResponse = await fetch('https://api.open-meteo.com/v1/forecast?latitude=-23.55&longitude=-46.63&current_weather=true');
                                const fallbackData = await fallbackResponse.json();
                                exibirClima(fallbackData, -23.55, -46.63, 'São Paulo');
                            } catch (fallbackError) {
                                console.error('Erro no fallback:', fallbackError);
                                climaConteudo.innerHTML = `
                                <div class="clima-erro">
                                    <p>🌤️ Serviço de clima indisponível</p>
                                    <p style="font-size: 12px; margin-top: 10px;">Tente atualizar a página</p>
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

        // Função fallback para buscar por cidade
        async function buscarClimaPorCidade(cidade) {
            const climaConteudo = document.getElementById('clima-conteudo');

            // Coordenadas fixas para cidades brasileiras
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
                climaConteudo.innerHTML = `
                    <div class="clima-erro">
                        <p>🌤️ Clima de ${cidade}</p>
                        <p style="font-size: 12px; margin-top: 10px;">Serviço temporariamente indisponível</p>
                    </div>
                `;
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
            const direcaoVento = data.current_weather.winddirection;

            const icone = obterIconeClima(codigoTempo);
            const descricao = obterDescricaoClima(codigoTempo);

            let localizacao = cidadeForcada ? `${cidadeForcada}` : 'Sua Localização';

            // Sidebar
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
                        <div class="valor">${direcaoVento}°</div>
                        <div class="label">Direção</div>
                    </div>
                    <div class="clima-info">
                        <div class="valor">${Math.round(temperatura + 2)}°C</div>
                        <div class="label">Sensação</div>
                    </div>
                    <div class="clima-info">
                        <div class="valor">${Math.round(temperatura + 5)}%</div>
                        <div class="label">Umidade</div>
                    </div>
                </div>
            `;

            // Navbar
            atualizarClimaNavbar(data, cidadeForcada);
        }

        function atualizarClimaNavbar(data, cidadeForcada = null) {
            if (!data.current_weather) return;

            const temperatura = Math.round(data.current_weather.temperature);
            const codigoTempo = data.current_weather.weathercode;
            const velocidadeVento = data.current_weather.windspeed;

            const icone = obterIconeClima(codigoTempo);
            const localizacao = cidadeForcada || 'Sua Localização';

            // Atualizar navbar expandido
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
            // Simula abertura de modal ou página de clima completo
            alert('🌤️ Clima completo: ' + document.getElementById('climaNavbarLocal').textContent);
        }

        // Sistema Ver Mais para notícias
        function toggleNoticias(categoriaId, botao) {
            const noticiasAdicionais = document.getElementById(`noticias-${categoriaId}`);
            const textoBotao = botao.querySelector('.btn-text');
            const iconeBotao = botao.querySelector('span:last-child');

            if (noticiasAdicionais.classList.contains('mostrar')) {
                // Esconder
                noticiasAdicionais.classList.remove('mostrar');
                textoBotao.textContent = 'Ver Mais Notícias';
                iconeBotao.textContent = '⬇️';
                botao.style.background = 'linear-gradient(135deg, #c4170c 0%, #a6140b 100%)';
            } else {
                // Mostrar
                noticiasAdicionais.classList.add('mostrar');
                textoBotao.textContent = 'Ver Menos';
                iconeBotao.textContent = '⬆️';
                botao.style.background = 'linear-gradient(135deg, #2c3e50 0%, #34495e 100%)';

                // Scroll suave para as notícias
                noticiasAdicionais.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Buscar clima automaticamente ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(buscarClima, 1000);
        });
    </script>
    <script src="assets/js/theme.js"></script>
</body>

</html>