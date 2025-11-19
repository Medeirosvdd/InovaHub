<?php
session_start();
require 'includes/conexao.php';
require 'includes/funcoes.php';

// FUNÇÃO PARA BUSCAR IMAGENS
function getImagemNoticia($nome_imagem)
{
    if (empty($nome_imagem) || $nome_imagem === 'noticia.jpg') {
        return "assets/img/defaults/noticia.jpg";
    }

    $caminho_imagem = "uploads/noticias/" . $nome_imagem;

    if (file_exists($caminho_imagem)) {
        return $caminho_imagem;
    } else {
        return "assets/img/defaults/noticia.jpg";
    }
}

// Buscar notícia pelo slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit();
}

// Buscar notícia
$stmt = $pdo->prepare("
    SELECT n.*, u.nome AS autor_nome, u.avatar AS autor_avatar, 
           c.nome AS categoria_nome, c.cor AS categoria_cor, c.slug AS categoria_slug
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    JOIN categorias c ON c.id = n.categoria
    WHERE n.slug = ? AND n.status = 'publicada'
");

$stmt->execute([$slug]);
$noticia = $stmt->fetch();

if (!$noticia) {
    header('HTTP/1.0 404 Not Found');
    die('Notícia não encontrada');
}

// Incrementar visualização
incrementarVisualizacao($pdo, $noticia['id']);

// Buscar notícias relacionadas
$stmt = $pdo->prepare("
    SELECT n.*, u.nome AS autor_nome
    FROM noticias n
    JOIN usuarios u ON u.id = n.autor
    WHERE n.categoria = ? AND n.id != ? AND n.status = 'publicada'
    ORDER BY n.data DESC
    LIMIT 3
");
$stmt->execute([$noticia['categoria'], $noticia['id']]);
$relacionadas = $stmt->fetchAll();

// Buscar comentários aprovados
$stmt = $pdo->prepare("
    SELECT c.*, u.nome AS usuario_nome, u.avatar AS usuario_avatar
    FROM comentarios c
    JOIN usuarios u ON u.id = c.usuario_id
    WHERE c.noticia_id = ? AND c.aprovado = 1
    ORDER BY c.criado_em DESC
");
$stmt->execute([$noticia['id']]);
$comentarios = $stmt->fetchAll();

$usuario = usuarioLogado($pdo);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($noticia['titulo']) ?> - InovaHub</title>
    <meta name="description" content="<?= htmlspecialchars($noticia['resumo']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($noticia['titulo']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($noticia['resumo']) ?>">
    <meta property="og:image" content="<?= getImagemNoticia($noticia['imagem']) ?>">
    <meta property="og:type" content="article">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .noticia-header {
            background: var(--white);
            padding: var(--space-8) 0;
            margin-bottom: var(--space-6);
        }

        .noticia-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 var(--space-4);
        }

        .noticia-categoria {
            display: inline-block;
            background: <?= $noticia['categoria_cor'] ?>;
            color: var(--white);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius);
            font-size: var(--font-size-sm);
            font-weight: 500;
            margin-bottom: var(--space-4);
        }

        .noticia-titulo {
            font-size: var(--font-size-4xl);
            line-height: 1.2;
            margin-bottom: var(--space-4);
            color: var(--primary-text);
        }

        .noticia-meta {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            margin-bottom: var(--space-6);
            color: var(--secondary-text);
            font-size: var(--font-size-sm);
        }

        .autor-info {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .autor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .noticia-imagem-destaque {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
        }

        .noticia-conteudo {
            background: var(--white);
            padding: var(--space-8);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-8);
            line-height: 1.8;
            font-size: var(--font-size-lg);
        }

        .noticia-conteudo h2,
        .noticia-conteudo h3 {
            margin: var(--space-6) 0 var(--space-3);
            color: var(--primary-text);
        }

        .noticia-conteudo p {
            margin-bottom: var(--space-4);
        }

        .noticia-conteudo img {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius);
            margin: var(--space-4) 0;
        }

        .noticia-conteudo blockquote {
            border-left: 4px solid var(--primary-red);
            padding-left: var(--space-4);
            margin: var(--space-4) 0;
            font-style: italic;
            color: var(--secondary-text);
        }

        .compartilhar {
            display: flex;
            gap: var(--space-3);
            margin: var(--space-6) 0;
        }

        .btn-compartilhar {
            padding: var(--space-2) var(--space-4);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            background: var(--white);
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-compartilhar:hover {
            background: var(--light-gray);
        }

        .comentarios-section {
            background: var(--white);
            padding: var(--space-8);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-8);
        }

        .form-comentario {
            margin-bottom: var(--space-6);
        }

        .form-comentario textarea {
            width: 100%;
            padding: var(--space-4);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            resize: vertical;
            min-height: 100px;
            margin-bottom: var(--space-3);
        }

        .btn-enviar-comentario {
            background: var(--primary-red);
            color: var(--white);
            border: none;
            padding: var(--space-3) var(--space-6);
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 500;
        }

        .lista-comentarios {
            display: flex;
            flex-direction: column;
            gap: var(--space-4);
        }

        .comentario-item {
            padding: var(--space-4);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
        }

        .comentario-header {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-2);
        }

        .comentario-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .comentario-data {
            font-size: var(--font-size-sm);
            color: var(--secondary-text);
        }

        .relacionadas-section {
            margin-bottom: var(--space-8);
        }

        @media (max-width: 768px) {
            .noticia-titulo {
                font-size: var(--font-size-3xl);
            }

            .noticia-imagem-destaque {
                height: 250px;
            }

            .noticia-conteudo {
                padding: var(--space-4);
                font-size: var(--font-size-base);
            }
        }

        .mensagem-success {
            background: #d4edda;
            color: #155724;
            padding: var(--space-3);
            border-radius: var(--radius);
            margin: var(--space-3) 0;
            border: 1px solid #c3e6cb;
        }

        .mensagem-error {
            background: #f8d7da;
            color: #721c24;
            padding: var(--space-3);
            border-radius: var(--radius);
            margin: var(--space-3) 0;
            border: 1px solid #f5c6cb;
        }

        #btn-enviar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .comentario-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .comentarios-pendentes {
            margin-bottom: var(--space-6);
            padding: var(--space-4);
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: var(--radius);
        }

        .comentario-item.pendente {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            opacity: 0.8;
        }

        .comentario-item.pendente small {
            color: #856404;
            font-style: italic;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <article class="noticia-page">
        <header class="noticia-header">
            <div class="noticia-container">
                <a href="noticias/categoria.php?cat=<?= $noticia['categoria_slug'] ?>" class="noticia-categoria">
                    <?= $noticia['categoria_nome'] ?>
                </a>
                <h1 class="noticia-titulo"><?= htmlspecialchars($noticia['titulo']) ?></h1>

                <div class="noticia-meta">
                    <div class="autor-info">
                        <img src="uploads/avatars/<?= $noticia['autor_avatar'] ?>" alt="<?= $noticia['autor_nome'] ?>" class="autor-avatar" onerror="this.src='assets/img/defaults/avatar.jpg'">
                        <span>Por <?= $noticia['autor_nome'] ?></span>
                    </div>
                    <span class="data"><?= formatarData($noticia['data']) ?></span>
                    <span class="visualizacoes">👁️ <?= $noticia['visualizacoes'] ?> visualizações</span>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="layout-principal">
                <div class="conteudo-principal">
                    <!-- IMAGEM DA NOTÍCIA -->
                    <img src="<?= getImagemNoticia($noticia['imagem']) ?>" alt="<?= htmlspecialchars($noticia['titulo']) ?>" class="noticia-imagem-destaque">

                    <div class="noticia-conteudo">
                        <?= nl2br(htmlspecialchars($noticia['noticia'])) ?>
                    </div>

                    <div class="compartilhar">
                        <button class="btn-compartilhar" onclick="compartilhar('facebook')">📘 Facebook</button>
                        <button class="btn-compartilhar" onclick="compartilhar('twitter')">🐦 Twitter</button>
                        <button class="btn-compartilhar" onclick="compartilhar('whatsapp')">💬 WhatsApp</button>
                        <button class="btn-compartilhar" onclick="compartilhar('linkedin')">💼 LinkedIn</button>
                    </div>

                    <!-- Comentários -->
                    <section class="comentarios-section" id="comentarios">
                        <h2>💬 Comentários (<span id="total-comentarios"><?= count($comentarios) ?></span>)</h2>

                        <?php if ($usuario): ?>
                            <form class="form-comentario" id="form-comentario" method="POST">
                                <input type="hidden" name="noticia_id" value="<?= $noticia['id'] ?>">
                                <textarea name="comentario" id="texto-comentario" placeholder="Deixe seu comentário..." required></textarea>
                                <button type="submit" class="btn-enviar-comentario" id="btn-enviar">
                                    <span id="btn-texto">Enviar Comentário</span>
                                    <span id="btn-carregando" style="display: none;">⏳ Enviando...</span>
                                </button>
                            </form>

                            <div id="mensagem-comentario" style="display: none;"></div>

                            <?php
                            // Mostrar comentários pendentes do usuário atual
                            $stmt_pendentes = $pdo->prepare("
                                SELECT * FROM comentarios 
                                WHERE noticia_id = ? AND usuario_id = ? AND aprovado = 0
                                ORDER BY criado_em DESC
                            ");
                            $stmt_pendentes->execute([$noticia['id'], $usuario['id']]);
                            $comentarios_pendentes = $stmt_pendentes->fetchAll();

                            if (!empty($comentarios_pendentes)): ?>
                                <div class="comentarios-pendentes">
                                    <h4>⏳ Seus comentários pendentes de aprovação:</h4>
                                    <?php foreach ($comentarios_pendentes as $pendente): ?>
                                        <div class="comentario-item pendente">
                                            <div class="comentario-header">
                                                <strong><?= $usuario['nome'] ?></strong>
                                                <span class="comentario-data"><?= formatarData($pendente['criado_em']) ?></span>
                                            </div>
                                            <p><?= nl2br(htmlspecialchars($pendente['comentario'])) ?></p>
                                            <small><em>Aguardando aprovação da moderação</em></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><a href="auth/login.php">Faça login</a> para comentar.</p>
                        <?php endif; ?>

                        <div class="lista-comentarios" id="lista-comentarios">
                            <?php foreach ($comentarios as $comentario): ?>
                                <div class="comentario-item">
                                    <div class="comentario-header">
                                        <img src="uploads/avatars/<?= $comentario['usuario_avatar'] ?>" alt="<?= $comentario['usuario_nome'] ?>" class="comentario-avatar" onerror="this.src='assets/img/defaults/avatar.jpg'">
                                        <strong><?= $comentario['usuario_nome'] ?></strong>
                                        <span class="comentario-data"><?= formatarData($comentario['criado_em']) ?></span>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($comentarios)): ?>
                                <p id="sem-comentarios">Seja o primeiro a comentar!</p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <aside class="sidebar">
                    <!-- Notícias Relacionadas -->
                    <?php if (!empty($relacionadas)): ?>
                        <section class="sidebar-widget">
                            <h3>📖 Relacionadas</h3>
                            <div class="mais-lidas-list">
                                <?php foreach ($relacionadas as $rel): ?>
                                    <article class="mais-lida-item">
                                        <div class="conteudo">
                                            <a href="noticia.php?slug=<?= $rel['slug'] ?>">
                                                <h4><?= htmlspecialchars($rel['titulo']) ?></h4>
                                            </a>
                                            <div class="meta">
                                                <span class="data"><?= formatarData($rel['data']) ?></span>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Newsletter -->
                    <section class="sidebar-widget newsletter-widget">
                        <h3>📧 Newsletter</h3>
                        <p>Receba as principais notícias de tecnologia.</p>
                        <form class="newsletter-form">
                            <input type="email" placeholder="Seu email" required>
                            <button type="submit">Assinar</button>
                        </form>
                    </section>
                </aside>
            </div>
        </div>
    </article>

    <?php include 'includes/footer.php'; ?>

    <script>
        function compartilhar(rede) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent('<?= addslashes($noticia['titulo']) ?>');
            let shareUrl = '';

            switch (rede) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${title} ${url}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
                    break;
            }

            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    </script>

    <script>
        document.getElementById('form-comentario').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const btnEnviar = document.getElementById('btn-enviar');
            const btnTexto = document.getElementById('btn-texto');
            const btnCarregando = document.getElementById('btn-carregando');
            const mensagemDiv = document.getElementById('mensagem-comentario');
            const textoComentario = document.getElementById('texto-comentario');

            // Mostrar loading
            btnTexto.style.display = 'none';
            btnCarregando.style.display = 'inline';
            btnEnviar.disabled = true;
            mensagemDiv.style.display = 'none';

            // Preparar dados do formulário
            const formData = new FormData(form);

            // Enviar via AJAX
            fetch('includes/comentar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        // Limpar formulário
                        textoComentario.value = '';

                        // Mostrar mensagem de sucesso
                        mostrarMensagem(data.mensagem, 'success');

                        // Se o comentário foi aprovado automaticamente, adicionar à lista
                        if (data.comentario.aprovado) {
                            adicionarComentarioNaLista(data.comentario);
                        } else {
                            // Se precisa de aprovação, mostrar na seção de pendentes
                            atualizarComentariosPendentes(data.comentario);
                        }
                    } else {
                        // Mostrar mensagem de erro
                        mostrarMensagem(data.erro || 'Erro ao enviar comentário', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarMensagem('Erro de conexão. Tente novamente.', 'error');
                })
                .finally(() => {
                    // Restaurar botão
                    btnTexto.style.display = 'inline';
                    btnCarregando.style.display = 'none';
                    btnEnviar.disabled = false;
                });
        });

        function mostrarMensagem(mensagem, tipo) {
            const mensagemDiv = document.getElementById('mensagem-comentario');
            mensagemDiv.textContent = mensagem;
            mensagemDiv.className = `mensagem-${tipo}`;
            mensagemDiv.style.display = 'block';

            // Auto-esconder após 5 segundos
            setTimeout(() => {
                mensagemDiv.style.display = 'none';
            }, 5000);
        }

        function adicionarComentarioNaLista(comentario) {
            const listaComentarios = document.getElementById('lista-comentarios');
            const semComentarios = document.getElementById('sem-comentarios');

            // Remover mensagem "Seja o primeiro"
            if (semComentarios) {
                semComentarios.remove();
            }

            // Criar elemento do comentário
            const comentarioHTML = `
                <div class="comentario-item">
                    <div class="comentario-header">
                        <div class="comentario-avatar" style="
                            width: 32px; 
                            height: 32px; 
                            border-radius: 50%; 
                            background: #007bff; 
                            color: white; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center; 
                            font-weight: bold;
                            margin-right: 10px;
                        ">${comentario.avatar}</div>
                        <strong>${comentario.usuario_nome}</strong>
                        <span class="comentario-data">${comentario.criado_em}</span>
                    </div>
                    <p>${comentario.comentario.replace(/\n/g, '<br>')}</p>
                </div>
            `;

            // Adicionar no início da lista
            listaComentarios.insertAdjacentHTML('afterbegin', comentarioHTML);

            // Atualizar contador
            const totalElement = document.getElementById('total-comentarios');
            const totalAtual = parseInt(totalElement.textContent) || 0;
            totalElement.textContent = totalAtual + 1;
        }

        function atualizarComentariosPendentes(comentario) {
            // Aqui você pode implementar a atualização da seção de comentários pendentes
            // Por enquanto, vamos apenas mostrar a mensagem de sucesso
            console.log('Comentário pendente adicionado:', comentario);
        }

        // CSS para as mensagens
        const style = document.createElement('style');
        style.textContent = `
            .mensagem-success {
                background: #d4edda;
                color: #155724;
                padding: 10px;
                border-radius: 4px;
                margin: 10px 0;
                border: 1px solid #c3e6cb;
            }
            
            .mensagem-error {
                background: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 4px;
                margin: 10px 0;
                border: 1px solid #f5c6cb;
            }
            
            #btn-enviar:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>