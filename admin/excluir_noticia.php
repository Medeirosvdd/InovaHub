<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

// Verificar se é admin
if (!$usuario || !ehAdmin($usuario)) {
    header('Location: ../index.php');
    exit();
}

// Verificar se o ID foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['erro'] = "ID da notícia não especificado.";
    header('Location: noticias.php');
    exit();
}

$noticia_id = intval($_GET['id']);

// Buscar informações da notícia para confirmar
$stmt = $pdo->prepare("
    SELECT n.*, u.nome as autor_nome 
    FROM noticias n 
    JOIN usuarios u ON n.autor = u.id 
    WHERE n.id = ?
");
$stmt->execute([$noticia_id]);
$noticia = $stmt->fetch();

if (!$noticia) {
    $_SESSION['erro'] = "Notícia não encontrada.";
    header('Location: noticias.php');
    exit();
}

// Processar exclusão se confirmada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    try {
        // Iniciar transação para garantir consistência
        $pdo->beginTransaction();

        // 1. Primeiro, excluir comentários associados (se houver)
        $pdo->prepare("DELETE FROM comentarios WHERE noticia_id = ?")->execute([$noticia_id]);

        // 2. Excluir estatísticas (se houver)
        $pdo->prepare("DELETE FROM estatisticas WHERE noticia_id = ?")->execute([$noticia_id]);

        // 3. Excluir a notícia
        $stmt = $pdo->prepare("DELETE FROM noticias WHERE id = ?");
        $stmt->execute([$noticia_id]);

        $pdo->commit();

        // Excluir imagem se existir
        if (!empty($noticia['imagem']) && $noticia['imagem'] !== 'default.jpg') {
            $caminho_imagem = "../uploads/noticias/" . $noticia['imagem'];
            if (file_exists($caminho_imagem)) {
                unlink($caminho_imagem);
            }
        }

        $_SESSION['sucesso'] = "Notícia excluída com sucesso!";
        header('Location: noticias.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao excluir notícia: " . $e->getMessage();
        header('Location: noticias.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Exclusão - InovaHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .confirmacao-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .icone-perigo {
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .info-noticia {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: left;
        }

        .info-noticia h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .detalhes {
            color: #666;
            font-size: 14px;
        }

        .botoes {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            flex: 1;
        }

        .btn-perigo {
            background: #dc3545;
            color: white;
        }

        .btn-perigo:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-cancelar {
            background: #6c757d;
            color: white;
        }

        .btn-cancelar:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .aviso {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="confirmacao-container">
        <div class="icone-perigo">⚠️</div>
        <h1>Confirmar Exclusão</h1>

        <div class="aviso">
            <strong>Atenção!</strong> Esta ação não pode ser desfeita. A notícia e todos os dados associados serão permanentemente excluídos.
        </div>

        <div class="info-noticia">
            <h3><?= htmlspecialchars($noticia['titulo']) ?></h3>
            <div class="detalhes">
                <p><strong>Autor:</strong> <?= $noticia['autor_nome'] ?></p>
                <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($noticia['data'])) ?></p>
                <p><strong>Visualizações:</strong> <?= $noticia['visualizacoes'] ?></p>
            </div>
        </div>

        <form method="post">
            <div class="botoes">
                <button type="submit" name="confirmar" class="btn btn-perigo" onclick="return confirm('Tem certeza absoluta? Esta ação é irreversível!')">
                    🗑️ Excluir Permanentemente
                </button>
                <a href="noticias.php" class="btn btn-cancelar">↩️ Cancelar</a>
            </div>
        </form>
    </div>
</body>

</html>