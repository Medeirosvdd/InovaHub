<?php

/**
 * Script de instalação do banco de dados InovaHub
 */

// Configurações
$host = 'localhost';
$dbname = 'inovahub';
$username = 'root';
$password = '';

try {
    // Conectar sem selecionar banco
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar banco de dados
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");

    echo "✅ Banco de dados criado com sucesso!\n";

    // SQL completo
    $sql = "
    -- Tabela de usuários
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        tipo ENUM('admin', 'editor', 'usuario') DEFAULT 'usuario',
        avatar VARCHAR(255) DEFAULT 'default.jpg',
        bio TEXT,
        ultimo_login TIMESTAMP NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ativo BOOLEAN DEFAULT TRUE
    );

    -- Tabela de categorias
    CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) UNIQUE NOT NULL,
        slug VARCHAR(60) UNIQUE NOT NULL,
        cor VARCHAR(7) DEFAULT '#c4170c',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Tabela de notícias
    CREATE TABLE IF NOT EXISTS noticias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(200) NOT NULL,
        slug VARCHAR(220) UNIQUE NOT NULL,
        resumo TEXT NOT NULL,
        noticia LONGTEXT NOT NULL,
        imagem VARCHAR(255) NOT NULL,
        autor INT NOT NULL,
        categoria INT NOT NULL,
        destaque BOOLEAN DEFAULT FALSE,
        visualizacoes INT DEFAULT 0,
        status ENUM('publicada', 'rascunho') DEFAULT 'publicada',
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (autor) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (categoria) REFERENCES categorias(id) ON DELETE CASCADE
    );

    -- Tabela de comentários
    CREATE TABLE IF NOT EXISTS comentarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        noticia_id INT NOT NULL,
        usuario_id INT NOT NULL,
        comentario TEXT NOT NULL,
        aprovado BOOLEAN DEFAULT FALSE,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    );

    -- Tabela de estatísticas
    CREATE TABLE IF NOT EXISTS estatisticas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        noticia_id INT NOT NULL,
        data DATE NOT NULL,
        visualizacoes INT DEFAULT 0,
        UNIQUE KEY unique_noticia_data (noticia_id, data),
        FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE
    );
    ";

    // Executar SQL
    $pdo->exec($sql);
    echo "✅ Tabelas criadas com sucesso!\n";

    // Inserir dados iniciais
    $pdo->exec("
        INSERT IGNORE INTO categorias (nome, slug, cor) VALUES 
        ('Tecnologia', 'tecnologia', '#c4170c'),
        ('Startups', 'startups', '#0066cc'),
        ('IA & Robótica', 'ia-robotica', '#00a859'),
        ('Hardware', 'hardware', '#ff6600'),
        ('Inovações', 'inovacoes', '#9933cc'),
        ('Mercado', 'mercado', '#ff3366')
    ");

    // Senha: admin123
    $pdo->exec("
        INSERT IGNORE INTO usuarios (nome, email, senha, tipo) VALUES 
        ('Administrador', 'admin@inovahub.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
    ");

    echo "✅ Dados iniciais inseridos!\n";
    echo "🎉 Instalação concluída com sucesso!\n";
    echo "📧 Login: admin@inovahub.com\n";
    echo "🔑 Senha: admin123\n";
} catch (PDOException $e) {
    die("❌ Erro na instalação: " . $e->getMessage());
}
