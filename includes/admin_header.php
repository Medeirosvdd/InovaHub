<?php

/**
 * Header do painel administrativo
 */
?>
<header class="admin-header">
    <div class="admin-nav">
        <div class="nav-brand">
            <a href="index.php" class="brand-link">
                <span class="brand-icon">⚡</span>
                <span class="brand-text">InovaHub Admin</span>
            </a>
        </div>

        <nav class="admin-menu">
            <a href="index.php" class="menu-item active">📊 Dashboard</a>
            <a href="noticias.php" class="menu-item">📝 Notícias</a>
            <a href="usuarios.php" class="menu-item">👥 Usuários</a>
            <a href="categorias.php" class="menu-item">📂 Categorias</a>
            <a href="comentarios.php" class="menu-item">💬 Comentários</a>
        </nav>

        <div class="admin-user">
            <div class="user-dropdown">
                <button class="user-toggle">
                    <span class="user-avatar">👤</span>
                    <span class="user-name"><?= htmlspecialchars($usuario['nome']) ?></span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                <div class="dropdown-menu">
                    <a href="../usuario/dashboard.php" class="dropdown-item">👨‍💻 Meu Perfil</a>
                    <a href="../index.php" class="dropdown-item">🌐 Ver Site</a>
                    <div class="dropdown-divider"></div>
                    <a href="../auth/logout.php" class="dropdown-item logout">🚪 Sair</a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
    .admin-header {
        background: #1a1a1a;
        color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .admin-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        height: 70px;
    }

    .brand-link {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: white;
        font-size: 1.3rem;
        font-weight: bold;
    }

    .brand-icon {
        font-size: 1.5rem;
    }

    .admin-menu {
        display: flex;
        gap: 5px;
    }

    .menu-item {
        padding: 12px 20px;
        text-decoration: none;
        color: #ccc;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .menu-item:hover,
    .menu-item.active {
        background: #c4170c;
        color: white;
    }

    .admin-user {
        position: relative;
    }

    .user-toggle {
        display: flex;
        align-items: center;
        gap: 10px;
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 6px;
        transition: background 0.3s ease;
    }

    .user-toggle:hover {
        background: #333;
    }

    .user-avatar {
        font-size: 1.2rem;
    }

    .user-name {
        font-weight: 500;
    }

    .dropdown-arrow {
        font-size: 0.8rem;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        min-width: 200px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
    }

    .user-dropdown:hover .dropdown-menu {
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
        transition: background 0.3s ease;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
    }

    .dropdown-item.logout {
        color: #c4170c;
        font-weight: 500;
    }

    .dropdown-divider {
        height: 1px;
        background: #eee;
        margin: 5px 0;
    }

    @media (max-width: 768px) {
        .admin-nav {
            flex-direction: column;
            height: auto;
            padding: 15px;
        }

        .admin-menu {
            margin: 15px 0;
            flex-wrap: wrap;
            justify-content: center;
        }

        .menu-item {
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .brand-text {
            display: none;
        }
    }
</style>