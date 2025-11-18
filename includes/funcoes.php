<?php
function buscarUsuarioPorEmail($pdo, $email)
{
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function usuarioLogado($pdo)
{
    if (isset($_SESSION['usuario_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return false;
}

function formatarData($data)
{
    return date('d/m/Y \à\s H:i', strtotime($data));
}

function incrementarVisualizacao($pdo, $noticia_id)
{
    $stmt = $pdo->prepare("UPDATE noticias SET visualizacoes = visualizacoes + 1 WHERE id = ?");
    $stmt->execute([$noticia_id]);
}

function ehAdmin($usuario)
{
    return isset($usuario['tipo']) && $usuario['tipo'] === 'admin';
}

function ehEditor($usuario)
{
    return isset($usuario['tipo']) && ($usuario['tipo'] === 'editor' || $usuario['tipo'] === 'admin');
}

function resumoTexto($texto, $limite = 150)
{
    $texto = strip_tags($texto);
    if (strlen($texto) <= $limite) {
        return $texto;
    }
    return substr($texto, 0, $limite) . '...';
}

// No includes/funcoes.php
function podePublicar($usuario)
{
    return ehEditor($usuario); // Apenas editores e admins
}

function podeAcessarAdmin($usuario)
{
    return ehAdmin($usuario); // Apenas admins
}

function podeAcessarEditor($usuario)
{
    return ehEditor($usuario); // Apenas editores e admins
}

function gerarSlug($texto)
{
    $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($texto));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug . '-' . uniqid();
}
