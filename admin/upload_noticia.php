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
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $resumo = trim($_POST['resumo']);
    $texto = trim($_POST['noticia']);
    $categoria = intval($_POST['categoria']);
    $status = $_POST['status'];
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $imagem_arquivo = $_FILES['imagem'] ?? null;

    // Validações
    if (empty($titulo) || empty($texto) || empty($resumo)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (strlen($resumo) > 150) {
        $erro = "O resumo deve ter no máximo 150 caracteres.";
    } else {
        try {
            // Processar upload da imagem
            $imagem_blob = null;
            $tipo_imagem = null;

            // Se enviou arquivo
            if ($imagem_arquivo && $imagem_arquivo['error'] === UPLOAD_ERR_OK) {
                $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $tamanhoMaximo = 5 * 1024 * 1024; // 5MB

                if (!in_array($imagem_arquivo['type'], $tiposPermitidos)) {
                    throw new Exception('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
                }

                if ($imagem_arquivo['size'] > $tamanhoMaximo) {
                    throw new Exception('Arquivo muito grande. Máximo 5MB.');
                }

                // Ler o arquivo e converter para BLOB
                $imagem_blob = file_get_contents($imagem_arquivo['tmp_name']);
                $tipo_imagem = $imagem_arquivo['type'];

                if ($imagem_blob === false) {
                    throw new Exception('Erro ao ler o arquivo da imagem.');
                }
            }

            // Gerar slug automático
            $slug_base = gerarSlug($titulo);
            $slug = $slug_base;
            $contador = 1;

            // Verificar se o slug já existe
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM noticias WHERE slug = ?");
                $stmt->execute([$slug]);
                $existe = $stmt->fetch();

                if (!$existe) {
                    break;
                }

                $slug = $slug_base . '-' . $contador;
                $contador++;

                if ($contador > 50) {
                    $slug = $slug_base . '-' . uniqid();
                    break;
                }
            }

            // Definir data de publicação se for publicada
            $publicada_em = $status === 'publicada' ? date('Y-m-d H:i:s') : null;

            // Inserir no banco com a imagem como BLOB
            $stmt = $pdo->prepare("
                INSERT INTO noticias 
                (titulo, slug, resumo, noticia, autor, imagem, tipo_imagem, categoria, status, destaque, publicada_em) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $titulo,
                $slug,
                $resumo,
                $texto,
                $usuario['id'],
                $imagem_blob,  // IMAGEM COMO BLOB
                $tipo_imagem,  // TIPO DA IMAGEM
                $categoria,
                $status,
                $destaque,
                $publicada_em
            ]);

            $sucesso = "Notícia publicada com sucesso!";

            // Limpar formulário em caso de sucesso
            $_POST = [];
            $_FILES = [];
        } catch (Exception $e) {
            $erro = "Erro: " . $e->getMessage();
        }
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
        .form-group input[type="file"],
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
        .form-group input[type="file"]:focus,
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

        .sucesso {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
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

        .preview-imagem {
            max-width: 300px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            display: none;
            border: 2px solid #e0e0e0;
        }

        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .upload-area:hover {
            border-color: #c4170c;
            background: #fff5f5;
        }

        .upload-area.dragover {
            border-color: #c4170c;
            background: #fff5f5;
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

            <?php if ($sucesso): ?>
                <div class="sucesso"><?= $sucesso ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <!-- Título -->
                <div class="form-group">
                    <label for="titulo" class="required">Título da Notícia</label>
                    <input type="text" id="titulo" name="titulo" required
                        value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
                        placeholder="Digite um título atraente para sua notícia">
                </div>

                <!-- Resumo -->
                <div class="form-group">
                    <label for="resumo" class="required">Resumo</label>
                    <textarea id="resumo" name="resumo" required
                        placeholder="Breve resumo que aparecerá na lista de notícias (máx. 150 caracteres)"><?= htmlspecialchars($_POST['resumo'] ?? '') ?></textarea>
                    <div class="form-help">Este texto aparecerá como preview nas listagens. <span id="contador-resumo">0/150</span> caracteres</div>
                </div>

                <!-- Imagem -->
                <div class="form-group">
                    <label for="imagem">Imagem de Capa</label>
                    <div class="upload-area" id="upload-area">
                        <div style="margin-bottom: 15px;">
                            <span style="font-size: 48px;">📁</span>
                        </div>
                        <p style="margin-bottom: 15px; font-weight: 500;">Clique para selecionar ou arraste uma imagem</p>
                        <input type="file" id="imagem" name="imagem"
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                            style="display: none;">
                        <button type="button" onclick="document.getElementById('imagem').click()"
                            style="background: #c4170c; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                            Selecionar Imagem
                        </button>
                        <div class="form-help" style="margin-top: 15px;">
                            Formatos: JPG, PNG, GIF, WebP. Máximo: 5MB
                        </div>
                    </div>
                    <img id="preview" class="preview-imagem" alt="Preview da imagem">
                    <div id="info-arquivo" class="form-help" style="display: none;"></div>
                </div>

                <!-- Configurações -->
                <div class="form-row">
                    <!-- Categoria -->
                    <div class="form-group">
                        <label for="categoria" class="required">Categoria</label>
                        <select id="categoria" name="categoria" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= ($_POST['categoria'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
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

                <!-- Destaque -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="destaque" name="destaque" value="1"
                            <?= ($_POST['destaque'] ?? '') ? 'checked' : '' ?>>
                        <label for="destaque">⭐ Destacar esta notícia</label>
                    </div>
                    <div class="form-help">Notícias em destaque aparecem primeiro na página inicial</div>
                </div>

                <!-- Conteúdo -->
                <div class="form-group">
                    <label for="noticia" class="required">Conteúdo da Notícia</label>
                    <textarea id="noticia" name="noticia" required
                        placeholder="Digite o conteúdo completo da sua notícia aqui..."><?= htmlspecialchars($_POST['noticia'] ?? '') ?></textarea>
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
        // Elementos DOM
        const uploadArea = document.getElementById('upload-area');
        const fileInput = document.getElementById('imagem');
        const preview = document.getElementById('preview');
        const infoArquivo = document.getElementById('info-arquivo');

        // Clique na área de upload
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                processarArquivo(e.dataTransfer.files[0]);
            }
        });

        // Mudança no input de arquivo
        fileInput.addEventListener('change', function(e) {
            if (this.files.length) {
                processarArquivo(this.files[0]);
            }
        });

        // Processar arquivo selecionado
        function processarArquivo(file) {
            // Validar tipo
            const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!tiposPermitidos.includes(file.type)) {
                alert('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
                return;
            }

            // Validar tamanho (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Arquivo muito grande. Máximo 5MB.');
                return;
            }

            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';

                // Atualizar informações do arquivo
                const tamanhoMB = (file.size / (1024 * 1024)).toFixed(2);
                infoArquivo.innerHTML = `
                    <strong>Arquivo:</strong> ${file.name}<br>
                    <strong>Tamanho:</strong> ${tamanhoMB} MB<br>
                    <strong>Tipo:</strong> ${file.type}
                `;
                infoArquivo.style.display = 'block';

                // Atualizar texto da área de upload
                uploadArea.innerHTML = `
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 36px;">✅</span>
                    </div>
                    <p style="margin-bottom: 10px; font-weight: 500; color: #28a745;">Imagem selecionada!</p>
                    <button type="button" onclick="document.getElementById('imagem').click()" 
                            style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        Trocar Imagem
                    </button>
                `;
            };
            reader.readAsDataURL(file);
        }

        // Contador de caracteres para o resumo
        const resumoTextarea = document.getElementById('resumo');
        const contadorResumo = document.getElementById('contador-resumo');

        function atualizarContadorResumo() {
            const length = resumoTextarea.value.length;
            contadorResumo.textContent = `${length}/150 caracteres`;

            if (length > 150) {
                contadorResumo.style.color = '#dc3545';
            } else {
                contadorResumo.style.color = '#666';
            }
        }

        resumoTextarea.addEventListener('input', function() {
            const length = this.value.length;
            if (length > 150) {
                this.value = this.value.substring(0, 150);
            }
            atualizarContadorResumo();
        });

        // Inicializar contador
        atualizarContadorResumo();

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