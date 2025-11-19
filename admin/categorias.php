<?php
session_start();
require_once '../includes/conexao.php';

// VERIFICAÇÃO TEMPORÁRIA
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1;
    $_SESSION['is_admin'] = true;
    $_SESSION['usuario_nome'] = 'Administrador';
}

$mensagem = '';

// CRUD - Criar Categoria
if ($_POST && isset($_POST['acao']) && $_POST['acao'] == 'criar') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $cor = filter_input(INPUT_POST, 'cor', FILTER_SANITIZE_STRING);

    if ($nome) {
        // Gerar slug automaticamente
        $slug = gerarSlug($nome);

        $sql = "INSERT INTO categorias (nome, slug, descricao, cor) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$nome, $slug, $descricao, $cor])) {
            $mensagem = "<div class='alert alert-success'>Categoria criada com sucesso!</div>";
        } else {
            $mensagem = "<div class='alert alert-danger'>Erro ao criar categoria.</div>";
        }
    }
}

// CRUD - Editar Categoria
if ($_POST && isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $cor = filter_input(INPUT_POST, 'cor', FILTER_SANITIZE_STRING);

    if ($id && $nome) {
        // Gerar slug automaticamente
        $slug = gerarSlug($nome);

        $sql = "UPDATE categorias SET nome = ?, slug = ?, descricao = ?, cor = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$nome, $slug, $descricao, $cor, $id])) {
            $mensagem = "<div class='alert alert-success'>Categoria atualizada com sucesso!</div>";
        } else {
            $mensagem = "<div class='alert alert-danger'>Erro ao atualizar categoria.</div>";
        }
    }
}

// CRUD - Excluir Categoria
if (isset($_GET['excluir'])) {
    $id = filter_input(INPUT_GET, 'excluir', FILTER_VALIDATE_INT);

    if ($id) {
        try {
            $sql_check = "SELECT COUNT(*) as total FROM noticias WHERE categoria_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$id]);
            $resultado = $stmt_check->fetch();

            if ($resultado['total'] == 0) {
                $sql = "DELETE FROM categorias WHERE id = ?";
                $stmt = $pdo->prepare($sql);

                if ($stmt->execute([$id])) {
                    $mensagem = "<div class='alert alert-success'>Categoria excluída com sucesso!</div>";
                }
            } else {
                $mensagem = "<div class='alert alert-warning'>Não é possível excluir categoria com notícias vinculadas.</div>";
            }
        } catch (Exception $e) {
            $sql = "DELETE FROM categorias WHERE id = ?";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$id])) {
                $mensagem = "<div class='alert alert-success'>Categoria excluída com sucesso!</div>";
            }
        }
    }
}

// Função para gerar slug
function gerarSlug($texto)
{
    $slug = preg_replace('/[^a-z0-9]/i', '-', $texto);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return strtolower($slug);
}

// Buscar categorias
try {
    $sql_categorias = "SELECT * FROM categorias ORDER BY nome";
    $categorias = $pdo->query($sql_categorias)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categorias = [];
    $mensagem = "<div class='alert alert-warning'>Tabela de categorias vazia ou não encontrada.</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - InovaHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #c4170c;
            --primary-dark: #a6140b;
            --secondary: #2c3e50;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        body {
            background: #f5f5f5;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--secondary);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            background: var(--primary);
            text-align: center;
        }

        .sidebar-header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }

        .nav-links {
            padding: 20px 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--primary);
        }

        .nav-links i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--secondary);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Section */
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .section-header h2 {
            color: var(--secondary);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--secondary);
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .category-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-edit {
            background: #17a2b8;
            color: white;
        }

        .btn-edit:hover {
            background: #138496;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .btn-view {
            background: #6c757d;
            color: white;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: var(--secondary);
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="color"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-group input[type="color"] {
            height: 45px;
            padding: 5px;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left-color: var(--warning);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>🚀 InovaHub</h1>
                <p>Painel Administrativo</p>
            </div>

            <nav class="nav-links">
                <a href="../dashboard.php">📊 Dashboard</a>
                <a href="noticias.php">📰 Gerenciar Notícias</a>
                <a href="usuarios.php">👥 Gerenciar Usuários</a>
                <a href="categorias.php" class="active">📂 Gerenciar Categorias</a>
                <a href="comentarios.php">💬 Moderar Comentários</a>
                <a href="../index.php">🏠 Voltar ao Site</a>
                <a href="../auth/logout.php">🚪 Sair</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>📂 Gerenciar Categorias</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['usuario_nome'], 0, 1)) ?>
                    </div>
                    <span>Olá, <?= $_SESSION['usuario_nome'] ?></span>
                </div>
            </div>

            <?= $mensagem ?>

            <div class="section">
                <div class="section-header">
                    <h2>Lista de Categorias</h2>
                    <button class="btn btn-primary" onclick="abrirModal()">
                        📝 Nova Categoria
                    </button>
                </div>

                <?php if (empty($categorias)): ?>
                    <div class="text-center py-4">
                        <p>Nenhuma categoria cadastrada.</p>
                        <p class="text-muted small">Use o botão "Nova Categoria" para adicionar a primeira.</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Slug</th>
                                <th>Descrição</th>
                                <th>Cor</th>
                                <th>Data Criação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $categoria): ?>
                                <tr>
                                    <td>
                                        <span class="category-badge" style="background-color: <?= $categoria['cor'] ?>">
                                            <?= htmlspecialchars($categoria['nome']) ?>
                                        </span>
                                    </td>
                                    <td><code><?= htmlspecialchars($categoria['slug']) ?></code></td>
                                    <td><?= htmlspecialchars($categoria['descricao'] ?? '-') ?></td>
                                    <td>
                                        <input type="color" value="<?= $categoria['cor'] ?>" disabled
                                            style="width: 30px; height: 30px; border: none; background: none; cursor: default;">
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($categoria['criado_em'])) ?></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-edit btn-sm"
                                            onclick="editarCategoria(<?= $categoria['id'] ?>, '<?= addslashes($categoria['nome']) ?>', '<?= addslashes($categoria['descricao'] ?? '') ?>', '<?= $categoria['cor'] ?>')">
                                            ✏️
                                        </button>
                                        <a href="?excluir=<?= $categoria['id'] ?>" class="btn btn-delete btn-sm"
                                            onclick="return confirm('Tem certeza que deseja excluir a categoria \'<?= addslashes($categoria['nome']) ?>\'?')">
                                            🗑️
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Categoria -->
    <div class="modal-overlay" id="modalCategoria">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h3 id="modalTitulo">Nova Categoria</h3>
                    <button type="button" class="close-btn" onclick="fecharModal()">×</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="categoriaId">
                    <input type="hidden" name="acao" id="acao" value="criar">

                    <div class="form-group">
                        <label for="nome">Nome da Categoria *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"
                            placeholder="Descrição opcional da categoria..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="cor">Cor de Identificação</label>
                        <input type="color" class="form-control" id="cor" name="cor" value="#007bff"
                            title="Escolha uma cor" style="width: 70px; height: 40px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Categoria</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalCategoria').classList.add('active');
        }

        function fecharModal() {
            document.getElementById('modalCategoria').classList.remove('active');
            // Reset form
            document.getElementById('categoriaId').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('descricao').value = '';
            document.getElementById('cor').value = '#007bff';
            document.getElementById('acao').value = 'criar';
            document.getElementById('modalTitulo').textContent = 'Nova Categoria';
        }

        function editarCategoria(id, nome, descricao, cor) {
            document.getElementById('categoriaId').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('descricao').value = descricao || '';
            document.getElementById('cor').value = cor;
            document.getElementById('acao').value = 'editar';
            document.getElementById('modalTitulo').textContent = 'Editar Categoria';
            abrirModal();
        }

        // Fechar modal ao clicar fora
        document.getElementById('modalCategoria').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
    </script>
</body>

</html>