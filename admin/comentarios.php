<?php
session_start();
require '../includes/conexao.php';
require '../includes/funcoes.php';

// Verificar se é admin
if (!usuarioLogado($pdo) || !podePublicar(usuarioLogado($pdo))) {
    header('Location: ../auth/login.php');
    exit();
}

$mensagem = '';

// Processar ações (aprovar/rejeitar/deletar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $comentario_id = intval($_POST['comentario_id']);
    $acao = $_POST['acao'];

    try {
        if ($acao === 'aprovar') {
            $stmt = $pdo->prepare("UPDATE comentarios SET aprovado = 1 WHERE id = ?");
            $stmt->execute([$comentario_id]);
            $mensagem = '<div class="alert success">Comentário aprovado com sucesso!</div>';
        } elseif ($acao === 'rejeitar') {
            $stmt = $pdo->prepare("DELETE FROM comentarios WHERE id = ?");
            $stmt->execute([$comentario_id]);
            $mensagem = '<div class="alert error">Comentário rejeitado e removido!</div>';
        } elseif ($acao === 'deletar') {
            $stmt = $pdo->prepare("DELETE FROM comentarios WHERE id = ?");
            $stmt->execute([$comentario_id]);
            $mensagem = '<div class="alert error">Comentário deletado permanentemente!</div>';
        } elseif ($acao === 'aprovar_todos') {
            $stmt = $pdo->prepare("UPDATE comentarios SET aprovado = 1 WHERE aprovado = 0");
            $stmt->execute();
            $mensagem = '<div class="alert success">Todos os comentários pendentes foram aprovados!</div>';
        }
    } catch (Exception $e) {
        $mensagem = '<div class="alert error">Erro: ' . $e->getMessage() . '</div>';
    }
}

// Buscar comentários pendentes
$stmt = $pdo->prepare("
    SELECT c.*, 
           u.nome as usuario_nome, 
           u.email as usuario_email,
           u.tipo as usuario_tipo,
           n.titulo as noticia_titulo, 
           n.slug as noticia_slug,
           cat.nome as categoria_nome
    FROM comentarios c
    JOIN usuarios u ON c.usuario_id = u.id
    JOIN noticias n ON c.noticia_id = n.id
    JOIN categorias cat ON n.categoria = cat.id
    WHERE c.aprovado = 0
    ORDER BY c.criado_em DESC
");
$stmt->execute();
$comentarios_pendentes = $stmt->fetchAll();

// Buscar comentários aprovados recentemente
$stmt_aprovados = $pdo->prepare("
    SELECT c.*, 
           u.nome as usuario_nome, 
           u.tipo as usuario_tipo,
           n.titulo as noticia_titulo,
           n.slug as noticia_slug
    FROM comentarios c
    JOIN usuarios u ON c.usuario_id = u.id
    JOIN noticias n ON c.noticia_id = n.id
    WHERE c.aprovado = 1
    ORDER BY c.criado_em DESC
    LIMIT 20
");
$stmt_aprovados->execute();
$comentarios_aprovados = $stmt_aprovados->fetchAll();

// Estatísticas
$total_pendentes = count($comentarios_pendentes);
$stmt_total = $pdo->query("SELECT COUNT(*) as total FROM comentarios WHERE aprovado = 1");
$total_aprovados = $stmt_total->fetch()['total'];
$stmt_total_geral = $pdo->query("SELECT COUNT(*) as total FROM comentarios");
$total_geral = $stmt_total_geral->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderação de Comentários - InovaHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-6);
        }

        .admin-header {
            background: var(--white);
            padding: var(--space-6);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
            box-shadow: var(--shadow);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .stat-card {
            background: var(--white);
            padding: var(--space-4);
            border-radius: var(--radius);
            text-align: center;
            box-shadow: var(--shadow);
        }

        .stat-number {
            font-size: var(--font-size-3xl);
            font-weight: bold;
            color: var(--primary-red);
        }

        .stat-label {
            color: var(--secondary-text);
            font-size: var(--font-size-sm);
        }

        .comentarios-section {
            background: var(--white);
            padding: var(--space-6);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-4);
            flex-wrap: wrap;
            gap: var(--space-3);
        }

        .comentario-item {
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: var(--space-4);
            margin-bottom: var(--space-4);
            background: var(--light-gray);
        }

        .comentario-item.aprovado {
            background: var(--white);
            border-left: 4px solid var(--success);
        }

        .comentario-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--space-2);
            flex-wrap: wrap;
            gap: var(--space-2);
        }

        .comentario-meta {
            display: flex;
            gap: var(--space-3);
            font-size: var(--font-size-sm);
            color: var(--secondary-text);
            flex-wrap: wrap;
        }

        .comentario-usuario {
            font-weight: 600;
            color: var(--primary-text);
        }

        .comentario-noticia {
            color: var(--primary-red);
            text-decoration: none;
            font-size: var(--font-size-sm);
        }

        .comentario-noticia:hover {
            text-decoration: underline;
        }

        .comentario-texto {
            margin: var(--space-3) 0;
            line-height: 1.6;
            padding: var(--space-3);
            background: var(--white);
            border-radius: var(--radius);
            white-space: pre-wrap;
        }

        .comentario-acoes {
            display: flex;
            gap: var(--space-2);
            margin-top: var(--space-3);
        }

        .btn {
            padding: var(--space-2) var(--space-3);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: var(--font-size-sm);
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-aprovar {
            background: var(--success);
            color: var(--white);
        }

        .btn-rejeitar {
            background: var(--danger);
            color: var(--white);
        }

        .btn-deletar {
            background: #6c757d;
            color: var(--white);
        }

        .btn-aprovar-todos {
            background: var(--primary-blue);
            color: var(--white);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .alert {
            padding: var(--space-3);
            border-radius: var(--radius);
            margin-bottom: var(--space-4);
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .empty-state {
            text-align: center;
            padding: var(--space-8);
            color: var(--secondary-text);
        }

        .badge {
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius);
            font-size: var(--font-size-xs);
            font-weight: 600;
        }

        .badge-pendente {
            background: #fff3cd;
            color: #856404;
        }

        .badge-usuario {
            background: #e2e3e5;
            color: #383d41;
        }

        .badge-editor {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-admin {
            background: #d4edda;
            color: #155724;
        }

        .filtros {
            display: flex;
            gap: var(--space-3);
            margin-bottom: var(--space-4);
            flex-wrap: wrap;
        }

        .filtro-btn {
            padding: var(--space-2) var(--space-3);
            border: 1px solid var(--border-color);
            background: var(--white);
            border-radius: var(--radius);
            cursor: pointer;
            text-decoration: none;
            color: var(--primary-text);
            font-size: var(--font-size-sm);
        }

        .filtro-btn.active {
            background: var(--primary-red);
            color: var(--white);
            border-color: var(--primary-red);
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="admin-container">
        <div class="admin-header">
            <h1>🛡️ Moderação de Comentários</h1>
            <p>Gerencie e aprove os comentários dos usuários</p>
        </div>

        <?= $mensagem ?>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_pendentes ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_aprovados ?></div>
                <div class="stat-label">Aprovados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_geral ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>

        <!-- Comentários Pendentes -->
        <section class="comentarios-section">
            <div class="section-header">
                <h2>⏳ Comentários Pendentes (<?= $total_pendentes ?>)</h2>
                <?php if ($total_pendentes > 0): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="acao" value="aprovar_todos">
                        <button type="submit" class="btn btn-aprovar-todos" onclick="return confirm('Deseja aprovar TODOS os comentários pendentes?')">
                            ✅ Aprovar Todos
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($comentarios_pendentes)): ?>
                <div class="empty-state">
                    <h3>🎉 Nenhum comentário pendente!</h3>
                    <p>Todos os comentários foram moderados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($comentarios_pendentes as $comentario): ?>
                    <div class="comentario-item">
                        <div class="comentario-header">
                            <div class="comentario-meta">
                                <span class="comentario-usuario">
                                    <?= htmlspecialchars($comentario['usuario_nome']) ?>
                                    <span class="badge badge-<?= $comentario['usuario_tipo'] ?>">
                                        <?= $comentario['usuario_tipo'] ?>
                                    </span>
                                </span>
                                <span>•</span>
                                <span><?= formatarData($comentario['criado_em']) ?></span>
                                <span>•</span>
                                <span class="badge badge-pendente">Pendente</span>
                            </div>
                            <a href="../noticia.php?slug=<?= $comentario['noticia_slug'] ?>" class="comentario-noticia" target="_blank">
                                📖 <?= htmlspecialchars($comentario['noticia_titulo']) ?>
                            </a>
                        </div>

                        <div class="comentario-texto">
                            <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                        </div>

                        <div class="comentario-acoes">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                <input type="hidden" name="acao" value="aprovar">
                                <button type="submit" class="btn btn-aprovar">✅ Aprovar</button>
                            </form>

                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                <input type="hidden" name="acao" value="rejeitar">
                                <button type="submit" class="btn btn-rejeitar" onclick="return confirm('Tem certeza que deseja rejeitar este comentário?')">
                                    ❌ Rejeitar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Comentários Aprovados Recentemente -->
        <section class="comentarios-section">
            <div class="section-header">
                <h2>✅ Comentários Aprovados (<?= count($comentarios_aprovados) ?>)</h2>
                <small>Mostrando os 20 mais recentes</small>
            </div>

            <?php if (empty($comentarios_aprovados)): ?>
                <div class="empty-state">
                    <p>Nenhum comentário aprovado ainda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($comentarios_aprovados as $comentario): ?>
                    <div class="comentario-item aprovado">
                        <div class="comentario-header">
                            <div class="comentario-meta">
                                <span class="comentario-usuario">
                                    <?= htmlspecialchars($comentario['usuario_nome']) ?>
                                    <span class="badge badge-<?= $comentario['usuario_tipo'] ?>">
                                        <?= $comentario['usuario_tipo'] ?>
                                    </span>
                                </span>
                                <span>•</span>
                                <span><?= formatarData($comentario['criado_em']) ?></span>
                                <span>•</span>
                                <span class="badge" style="background: #d4edda; color: #155724;">Aprovado</span>
                            </div>
                            <a href="../noticia.php?slug=<?= $comentario['noticia_slug'] ?? '' ?>" class="comentario-noticia" target="_blank">
                                📖 <?= htmlspecialchars($comentario['noticia_titulo']) ?>
                            </a>
                        </div>

                        <div class="comentario-texto">
                            <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                        </div>

                        <div class="comentario-acoes">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                <input type="hidden" name="acao" value="deletar">
                                <button type="submit" class="btn btn-deletar" onclick="return confirm('Tem certeza que deseja DELETAR PERMANENTEMENTE este comentário? Esta ação não pode ser desfeita!')">
                                    🗑️ Deletar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Auto-refresh a cada 30 segundos se houver comentários pendentes
        <?php if ($total_pendentes > 0): ?>
            setTimeout(() => {
                window.location.reload();
            }, 30000);
        <?php endif; ?>

        // Função para filtrar comentários (se quiser adicionar mais filtros depois)
        function filtrarComentarios(tipo) {
            // Implementação para filtros futuros
            console.log('Filtrar por:', tipo);
        }
    </script>
</body>

</html>