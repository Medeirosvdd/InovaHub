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
    // Converter para minúsculas
    $slug = strtolower($texto);

    // Substituir caracteres especiais
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);

    // Remover múltiplos hífens
    $slug = preg_replace('/-+/', '-', $slug);

    // Remover hífens do início e fim
    $slug = trim($slug, '-');

    // Se ficar vazio, usar timestamp
    if (empty($slug)) {
        $slug = 'noticia-' . time();
    }

    // ✅ VERIFICAR SE O SLUG JÁ EXISTE NO BANCO
    global $pdo;
    $slug_original = $slug;
    $contador = 1;

    // Verificar se o slug já existe
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM noticias WHERE slug = ?");
        $stmt->execute([$slug]);
        $existe = $stmt->fetch();

        if (!$existe) {
            break; // Slug único, pode usar
        }

        // Se existe, adicionar número no final
        $slug = $slug_original . '-' . $contador;
        $contador++;

        // Prevenir loop infinito
        if ($contador > 100) {
            $slug = $slug_original . '-' . uniqid();
            break;
        }
    }

    return $slug;
}
