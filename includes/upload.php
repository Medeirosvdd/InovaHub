<?php

/**
 * Funções para upload seguro de arquivos
 */

function validarImagem($arquivo)
{
    // Verificar erro no upload
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo.');
    }

    // Verificar tipo MIME
    $tipos_permitidos = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $tipos_permitidos)) {
        throw new Exception('Tipo de arquivo não permitido. Use apenas JPG, PNG, GIF ou WebP.');
    }

    // Verificar tamanho (máx 5MB)
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        throw new Exception('Arquivo muito grande. Tamanho máximo: 5MB.');
    }

    return true;
}

function processarUpload($arquivo, $pasta_destino, $largura_maxima = 1200, $qualidade = 85)
{
    // Validar arquivo
    validarImagem($arquivo);

    // Criar diretório se não existir
    if (!is_dir($pasta_destino)) {
        mkdir($pasta_destino, 0755, true);
    }

    // Gerar nome único
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $nome_arquivo = uniqid() . '.' . $extensao;
    $caminho_completo = $pasta_destino . '/' . $nome_arquivo;

    // Mover arquivo temporário
    if (!move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
        throw new Exception('Erro ao salvar arquivo.');
    }

    // Otimizar imagem se for muito grande
    try {
        $caminho_completo = otimizarImagem($caminho_completo, $largura_maxima, $qualidade);
    } catch (Exception $e) {
        // Se der erro na otimização, manter a imagem original
        error_log("Erro na otimização: " . $e->getMessage());
    }

    return $nome_arquivo;
}

function otimizarImagem($caminho_imagem, $largura_maxima = 1200, $qualidade = 85)
{
    // Obter informações da imagem
    $info = getimagesize($caminho_imagem);
    $mime = $info['mime'];

    // Criar imagem a partir do arquivo
    switch ($mime) {
        case 'image/jpeg':
            $imagem = imagecreatefromjpeg($caminho_imagem);
            break;
        case 'image/png':
            $imagem = imagecreatefrompng($caminho_imagem);
            break;
        case 'image/gif':
            $imagem = imagecreatefromgif($caminho_imagem);
            break;
        case 'image/webp':
            $imagem = imagecreatefromwebp($caminho_imagem);
            break;
        default:
            throw new Exception('Tipo de imagem não suportado para otimização.');
    }

    if (!$imagem) {
        throw new Exception('Erro ao carregar imagem para otimização.');
    }

    // Obter dimensões atuais
    $largura_atual = imagesx($imagem);
    $altura_atual = imagesy($imagem);

    // Calcular novas dimensões se necessário
    if ($largura_atual > $largura_maxima) {
        $nova_largura = $largura_maxima;
        $nova_altura = intval($altura_atual * ($largura_maxima / $largura_atual));

        // Criar nova imagem redimensionada
        $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);

        // Preservar transparência para PNG e GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagecolortransparent($nova_imagem, imagecolorallocatealpha($nova_imagem, 0, 0, 0, 127));
            imagealphablending($nova_imagem, false);
            imagesavealpha($nova_imagem, true);
        }

        // Redimensionar
        imagecopyresampled(
            $nova_imagem,
            $imagem,
            0,
            0,
            0,
            0,
            $nova_largura,
            $nova_altura,
            $largura_atual,
            $altura_atual
        );

        // Salvar imagem otimizada
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($nova_imagem, $caminho_imagem, $qualidade);
                break;
            case 'image/png':
                imagepng($nova_imagem, $caminho_imagem, 9 - round($qualidade / 100 * 9));
                break;
            case 'image/gif':
                imagegif($nova_imagem, $caminho_imagem);
                break;
            case 'image/webp':
                imagewebp($nova_imagem, $caminho_imagem, $qualidade);
                break;
        }

        // Liberar memória
        imagedestroy($nova_imagem);
    }

    // Liberar memória da imagem original
    imagedestroy($imagem);

    return $caminho_imagem;
}

function excluirArquivo($caminho_arquivo)
{
    if (file_exists($caminho_arquivo) && is_file($caminho_arquivo)) {
        return unlink($caminho_arquivo);
    }
    return false;
}
