<?php
require '../includes/verifica_login.php';
require '../includes/conexao.php';
require '../includes/funcoes.php';

$usuario = usuarioLogado($pdo);

// Só editores e admins podem publicar
if (!podePublicar($usuario)) {
    header('Location: ../index.php');
    exit();
}

// Buscar categorias
$categorias = $pdo->query("SELECT id, nome FROM categorias")->fetchAll();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $resumo = $_POST['resumo'];
    $texto = $_POST['noticia'];
    $categoria = $_POST['categoria'];
    $status = $_POST['status'];
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $img = $_POST['imagem'] ?: 'noticia.jpg';

    if ($titulo === '' || $texto === '' || $resumo === '') {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        // Gerar slug automático
        $slug = gerarSlug($titulo);

        // Definir data de publicação se for publicada
        $publicada_em = $status === 'publicada' ? date('Y-m-d H:i:s') : null;

        $stmt = $pdo->prepare("
            INSERT INTO noticias 
            (titulo, slug, resumo, noticia, autor, imagem, categoria, status, destaque, publicada_em) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([$titulo, $slug, $resumo, $texto, $usuario['id'], $img, $categoria, $status, $destaque, $publicada_em]);

        header("Location: ../editor/index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Notícia - InovaHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-noticia {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c4170c;
            box-shadow: 0 0 0 3px rgba(196, 23, 12, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group textarea#noticia {
            min-height: 300px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #c4170c 0%, #a6140b 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(196, 23, 12, 0.3);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-left: 15px;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        .required::after {
            content: " *";
            color: #c4170c;
        }

        .form-help {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="form-noticia">
            <h1 style="color: #c4170c; margin-bottom: 30px; text-align: center;">📝 Publicar Nova Notícia</h1>

            <?php if ($erro): ?>
                <div class="erro"><?= $erro ?></div>
            <?php endif; ?>

            <form method="post">
                <!-- Título -->
                <div class="form-group">
                    <label for="titulo" class="required">Título da Notícia</label>
                    <input type="text" id="titulo" name="titulo" required
                        placeholder="Digite um título atraente para sua notícia">
                </div>

                <!-- Resumo -->
                <div class="form-group">
                    <label for="resumo" class="required">Resumo</label>
                    <textarea id="resumo" name="resumo" required
                        placeholder="Breve resumo que aparecerá na lista de notícias (máx. 150 caracteres)"></textarea>
                    <div class="form-help">Este texto aparecerá como preview nas listagens.</div>
                </div>

                <!-- Configurações -->
                <div class="form-row">
                    <!-- Categoria -->
                    <div class="form-group">
                        <label for="categoria" class="required">Categoria</label>
                        <select id="categoria" name="categoria" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="status" class="required">Status</label>
                        <select id="status" name="status" required>
                            <option value="rascunho">📋 Rascunho</option>
                            <option value="publicada" selected>🚀 Publicada</option>
                            <option value="arquivada">📁 Arquivada</option>
                        </select>
                    </div>
                </div>

                <!-- Imagem e Destaque -->
                <div class="form-row">
                    <!-- Imagem -->
                    <div class="form-group">
                        <label for="imagem">Imagem de Capa</label>
                        <input type="text" id="imagem" name="imagem"
                            placeholder="URL da imagem (deixe em branco para padrão)">
                        <div class="form-help">Ex: imagem.jpg (deve estar na pasta uploads/noticias/)</div>
                    </div>

                    <!-- Destaque -->
                    <div class="form-group">
                        <label>Configurações</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="destaque" name="destaque" value="1">
                            <label for="destaque">⭐ Destacar esta notícia</label>
                        </div>
                        <div class="form-help">Notícias em destaque aparecem primeiro na página inicial</div>
                    </div>
                </div>

                <!-- Conteúdo -->
                <div class="form-group">
                    <label for="noticia" class="required">Conteúdo da Notícia</label>
                    <textarea id="noticia" name="noticia" required
                        placeholder="Digite o conteúdo completo da sua notícia aqui..."></textarea>
                    <div class="form-help">Use parágrafos para melhor formatação.</div>
                </div>

                <!-- Botões -->
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn-submit">🚀 Publicar Notícia</button>
                    <a href="../editor/index.php" class="btn-cancel">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Contador de caracteres para o resumo
        const resumoTextarea = document.getElementById('resumo');
        const resumoHelp = resumoTextarea.nextElementSibling;

        resumoTextarea.addEventListener('input', function() {
            const length = this.value.length;
            if (length > 150) {
                this.value = this.value.substring(0, 150);
                resumoHelp.innerHTML = '<span style="color: #dc3545;">Máximo de 150 caracteres atingido!</span>';
            } else {
                resumoHelp.innerHTML = `${length}/150 caracteres - Este texto aparecerá como preview nas listagens.`;
            }
        });

        // Preview do título no slug
        document.getElementById('titulo').addEventListener('input', function() {
            const slugPreview = this.value.toLowerCase()
                .replace(/[^a-z0-9-]/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');

            if (!document.getElementById('slug-help')) {
                const help = document.createElement('div');
                help.id = 'slug-help';
                help.className = 'form-help';
                help.style.marginTop = '5px';
                this.parentNode.appendChild(help);
            }

            document.getElementById('slug-help').textContent = `Slug gerado: ${slugPreview}`;
        });
    </script>
</body>

</html>