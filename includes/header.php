<?php
// Inicia sessão apenas se não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'conexao.php';
require_once 'funcoes.php';

$usuario = usuarioLogado($pdo);

// Buscar categorias para o menu
$categorias = $pdo->query("SELECT id, nome, slug, cor FROM categorias ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InovaHub</title>
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
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.2s;
            font-size: 14px;
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
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.2s;
            color: white;
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
            z-index: 1000;
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
            font-size: 14px;
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
            font-size: 14px;
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

        /* Responsividade */
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
                background: var(--primary);
                flex-direction: column;
                padding: 20px;
                z-index: 1000;
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

            .clima-navbar-expandido {
                display: none;
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

            <nav class="nav-principal" id="nav-principal">
                <a href="../index.php">🏠 Início</a>
                <?php foreach ($categorias as $cat): ?>
            
                <?php endforeach; ?>
            </nav>

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
                        <span class="user-greeting">👋 Olá, <?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                        <div class="user-dropdown">
                            <a href="../auth/logout.php" class="dropdown-item">🚪 Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="../auth/login.php" class="btn-login">Entrar</a>
                        <a href="../auth/cadastro.php" class="btn-register">Cadastrar</a>
                    </div>
                <?php endif; ?>

                <button class="btn-theme" id="btn-theme" title="Alternar tema">☀️</button>
            </div>
        </div>
    </header>

    <script>
        // Menu mobile toggle
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('nav-principal').classList.toggle('active');
        });

        // Sistema de Clima
        function buscarClima() {
            // Implementação do clima (igual às outras páginas)
            console.log('Buscando dados do clima...');
        }

        function abrirClimaCompleto() {
            alert('🌤️ Clima completo: ' + (document.getElementById('climaNavbarLocal')?.textContent || 'Localização'));
        }

        // Buscar clima automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(buscarClima, 1000);
        });

        // Tema claro/escuro
        document.getElementById('btn-theme').addEventListener('click', function() {
            const body = document.body;
            const isDark = body.classList.contains('dark-theme');

            if (isDark) {
                body.classList.remove('dark-theme');
                this.textContent = '☀️';
            } else {
                body.classList.add('dark-theme');
                this.textContent = '🌙';
            }
        });
    </script>
</body>

</html>