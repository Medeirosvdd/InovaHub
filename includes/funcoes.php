<?php
/**
 * Funções auxiliares do sistema InovaHub
 */

/**
 * Verifica se usuário está logado
 */
function usuarioLogado($pdo) {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND ativo = 1");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Verifica se usuário é admin
 */
function ehAdmin($usuario) {
    return $usuario && $usuario['tipo'] === 'admin';
}

/**
 * Verifica se usuário é editor
 */
function ehEditor($usuario) {
    return $usuario && in_array($usuario['tipo'], ['admin', 'editor']);
}

/**
 * Gera slug a partir de um texto
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Formata data para formato brasileiro
 */
function formatarData($data) {
    return date('d/m/Y \à\s H:i', strtotime($data));
}

/**
 * Resume texto para preview
 */
function resumoTexto($texto, $limite = 150) {
    $texto = strip_tags($texto);
    if (strlen($texto) <= $limite) {
        return $texto;
    }
    
    $texto = substr($texto, 0, $limite);
    $ultimo_espaco = strrpos($texto, ' ');
    
    if ($ultimo_espaco !== false) {
        $texto = substr($texto, 0, $ultimo_espaco);
    }
    
    return $texto . '...';
}

/**
 * Incrementa visualização da notícia
 */
function incrementarVisualizacao($pdo, $noticia_id) {
    // Incrementa na notícia
    $stmt = $pdo->prepare("UPDATE noticias SET visualizacoes = visualizacoes + 1 WHERE id = ?");
    $stmt->execute([$noticia_id]);
    
    // Registra na estatística diária
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare("
        INSERT INTO estatisticas (noticia_id, data, visualizacoes) 
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE visualizacoes = visualizacoes + 1
    ");
    $stmt->execute([$noticia_id, $hoje]);
}

/**
 * Upload seguro de imagem
 */
function uploadImagem($file, $pasta = 'noticias') {
    $diretorio = "uploads/$pasta/";
    
    // Cria diretório se não existir
    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0755, true);
    }
    
    // Verifica erro
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload da imagem');
    }
    
    // Verifica tipo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $tipos_permitidos)) {
        throw new Exception('Tipo de arquivo não permitido');
    }
    
    // Verifica tamanho (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Arquivo muito grande (máx. 5MB)');
    }
    
    // Gera nome único
    $extensao = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nome_arquivo = uniqid() . '.' . $extensao;
    $caminho_completo = $diretorio . $nome_arquivo;
    
    // Move arquivo
    if (!move_uploaded_file($file['tmp_name'], $caminho_completo)) {
        throw new Exception('Erro ao salvar arquivo');
    }
    
    return $nome_arquivo;
}

/**
 * Sanitiza dados do usuário
 */
function sanitizar($dados) {
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera paginação
 */
function gerarPaginacao($pagina_atual, $total_paginas, $url_base) {
    if ($total_paginas <= 1) return '';
    
    $html = '<div class="paginacao">';
    
    // Botão anterior
    if ($pagina_atual > 1) {
        $html .= '<a href="' . $url_base . ($pagina_atual - 1) . '" class="pagina anterior">‹ Anterior</a>';
    }
    
    // Páginas
    for ($i = 1; $i <= $total_paginas; $i++) {
        if ($i == $pagina_atual) {
            $html .= '<span class="pagina atual">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $url_base . $i . '" class="pagina">' . $i . '</a>';
        }
    }
    
    // Botão próximo
    if ($pagina_atual < $total_paginas) {
        $html .= '<a href="' . $url_base . ($pagina_atual + 1) . '" class="pagina proxima">Próxima ›</a>';
    }
    
    $html .= '</div>';
    return $html;
}
?>