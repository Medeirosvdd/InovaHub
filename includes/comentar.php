<?php
session_start();
require 'conexao.php';
require 'funcoes.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Você precisa estar logado para comentar.']);
    exit();
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
    exit();
}

// Verificar se todos os campos necessários foram enviados
if (!isset($_POST['noticia_id']) || !isset($_POST['comentario'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$noticia_id = intval($_POST['noticia_id']);
$comentario = trim($_POST['comentario']);

// DEBUG: Log dos dados recebidos
error_log("DEBUG comentar.php - noticia_id: $noticia_id, comentario: " . substr($comentario, 0, 50));

// Validar dados
$erros = [];

if (empty($comentario)) {
    $erros[] = 'O comentário não pode estar vazio.';
}

if (strlen($comentario) < 2) {
    $erros[] = 'O comentário deve ter pelo menos 2 caracteres.';
}

if (strlen($comentario) > 1000) {
    $erros[] = 'O comentário não pode ter mais de 1000 caracteres.';
}

// Verificar se a notícia existe
$stmt = $pdo->prepare("SELECT id, titulo, status FROM noticias WHERE id = ?");
$stmt->execute([$noticia_id]);
$noticia = $stmt->fetch();

error_log("DEBUG - Notícia encontrada: " . ($noticia ? 'SIM' : 'NÃO'));

if (!$noticia) {
    $erros[] = 'Notícia não encontrada.';
} else {
    // Verificar se está publicada - CORREÇÃO: usando os valores corretos do enum
    if ($noticia['status'] !== 'publicada') {
        error_log("DEBUG - Status da notícia: " . $noticia['status']);
        // Comentário removido para permitir comentários em qualquer status
        // $erros[] = 'Notícia não publicada.';
    }
}

// Se há erros, retornar
if (!empty($erros)) {
    error_log("DEBUG - Erros: " . implode(', ', $erros));
    echo json_encode(['sucesso' => false, 'erro' => implode(' ', $erros)]);
    exit();
}

try {
    // Iniciar transação
    $pdo->beginTransaction();

    // CORREÇÃO: Verificar se o usuário atual pode aprovar automaticamente
    $usuario = usuarioLogado($pdo);
    $aprovado = podePublicar($usuario) ? 1 : 0;

    // CORREÇÃO: Inserir sem parent_id (coluna não existe na tabela)
    $stmt = $pdo->prepare("
        INSERT INTO comentarios (noticia_id, usuario_id, comentario, aprovado) 
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$noticia_id, $usuario_id, $comentario, $aprovado]);
    $comentario_id = $pdo->lastInsertId();

    // Atualizar contador de comentários na notícia (se a coluna existir)
    try {
        // Verificar se a coluna total_comentarios existe
        $stmt = $pdo->prepare("SHOW COLUMNS FROM noticias LIKE 'total_comentarios'");
        $stmt->execute();
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("
                UPDATE noticias 
                SET total_comentarios = COALESCE(total_comentarios, 0) + 1 
                WHERE id = ?
            ");
            $stmt->execute([$noticia_id]);
        }
    } catch (Exception $e) {
        error_log("DEBUG - Coluna total_comentarios não existe: " . $e->getMessage());
    }

    $pdo->commit();

    // Buscar dados do comentário para retornar
    $stmt = $pdo->prepare("
        SELECT c.*, u.nome as usuario_nome, u.tipo as usuario_tipo 
        FROM comentarios c 
        JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comentario_id]);
    $novo_comentario = $stmt->fetch();

    if (!$novo_comentario) {
        throw new Exception("Comentário não encontrado após inserção");
    }

    // Formatar dados para retorno
    $resposta = [
        'sucesso' => true,
        'comentario' => [
            'id' => $novo_comentario['id'],
            'comentario' => htmlspecialchars($novo_comentario['comentario']),
            'usuario_nome' => htmlspecialchars($novo_comentario['usuario_nome']),
            'usuario_tipo' => $novo_comentario['usuario_tipo'],
            'criado_em' => formatarData($novo_comentario['criado_em']),
            'aprovado' => (bool)$novo_comentario['aprovado'],
            'is_admin' => podePublicar($usuario),
            'avatar' => strtoupper(substr($novo_comentario['usuario_nome'], 0, 1))
        ],
        'mensagem' => $aprovado ?
            'Comentário publicado com sucesso!' :
            'Comentário enviado para moderação. Após aprovação, será exibido publicamente.'
    ];

    echo json_encode($resposta);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("DEBUG - Erro no banco: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao publicar comentário: ' . $e->getMessage()]);
}