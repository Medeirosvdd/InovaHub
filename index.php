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

    // Buscar notícias por categoria (para sidebar)
    $noticias_por_categoria = [];
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
        $noticias = $stmt->fetchAll();
        if (!empty($noticias)) {
            $noticias_por_categoria[$cat['nome']] = $noticias;
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


            /* Responsividade do modal */
            @media (max-width: 480px) {
                .modal-content {
                    width: 95%;
                    margin: 5% auto;
                }

                .modal-header {
                    padding: 20px;
                }

                .modal-body {
                    padding: 20px;
                }

                .modal-header h2 {
                    font-size: 24px;
                }
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
                    <script src="assets/js/theme.js"></script>

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
                    <?php foreach ($noticias_por_categoria as $categoria_nome => $noticias_cat): ?>
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
                        </section>
                    <?php endforeach; ?>
                </div>

                <!-- Sidebar -->
                <aside class="sidebar">
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
        </script>
        <script src="assets/js/theme.js"></script>

    </body>

    </html>