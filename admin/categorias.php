<?php
session_start();
require_once '../includes/conexao.php';  // CAMINHO CORRETO!

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
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - InovaHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <style>* {
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

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #c4170c 0%, #a6140b 100%);
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(196, 23, 12, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 15px;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
            text-transform: none;
            letter-spacing: normal;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
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

        /* Modal */
        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: var(--secondary);
            color: white;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(196, 23, 12, 0.25);
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

            .form-row {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }
        }
    </style>
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="mb-4"><i class="fas fa-rocket me-2"></i>InovaHub Admin</h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="../dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        <a class="nav-link" href="noticias.php"><i class="fas fa-newspaper me-2"></i>Notícias</a>
                        <a class="nav-link active" href="categorias.php"><i class="fas fa-tags me-2"></i>Categorias</a>
                        <a class="nav-link" href="usuarios.php"><i class="fas fa-users me-2"></i>Usuários</a>
                        <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tags text-primary me-2"></i>Gerenciar Categorias</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                        <i class="fas fa-plus me-2"></i>Nova Categoria
                    </button>
                </div>

                <?php echo $mensagem; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Lista de Categorias</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($categorias)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Nenhuma categoria cadastrada.</p>
                                        <p class="text-muted small">Use o botão "Nova Categoria" para adicionar a primeira.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
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
                                                            <span class="category-badge" style="background-color: <?php echo $categoria['cor']; ?>">
                                                                <?php echo htmlspecialchars($categoria['nome']); ?>
                                                            </span>
                                                        </td>
                                                        <td><code><?php echo htmlspecialchars($categoria['slug']); ?></code></td>
                                                        <td><?php echo htmlspecialchars($categoria['descricao'] ?? '-'); ?></td>
                                                        <td><input type="color" value="<?php echo $categoria['cor']; ?>" class="form-control form-control-color" disabled style="width: 50px; height: 30px;"></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($categoria['criado_em'])); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                onclick="editarCategoria(<?php echo $categoria['id']; ?>, '<?php echo addslashes($categoria['nome']); ?>', '<?php echo addslashes($categoria['descricao'] ?? ''); ?>', '<?php echo $categoria['cor']; ?>')"
                                                                data-bs-toggle="modal" data-bs-target="#modalCategoria">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <a href="?excluir=<?php echo $categoria['id']; ?>"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Tem certeza que deseja excluir a categoria \'<?php echo addslashes($categoria['nome']); ?>\'?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Categoria -->
    <div class="modal fade" id="modalCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitulo">Nova Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="categoriaId">
                        <input type="hidden" name="acao" id="acao" value="criar">

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Descrição opcional da categoria..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="cor" class="form-label">Cor de Identificação</label>
                            <input type="color" class="form-control form-control-color" id="cor" name="cor" value="#007bff" title="Escolha uma cor" style="width: 70px; height: 40px;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarCategoria(id, nome, descricao, cor) {
            document.getElementById('categoriaId').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('descricao').value = descricao || '';
            document.getElementById('cor').value = cor;
            document.getElementById('acao').value = 'editar';
            document.getElementById('modalTitulo').textContent = 'Editar Categoria';
        }

        document.getElementById('modalCategoria').addEventListener('hidden.bs.modal', function() {
            document.getElementById('categoriaId').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('descricao').value = '';
            document.getElementById('cor').value = '#007bff';
            document.getElementById('acao').value = 'criar';
            document.getElementById('modalTitulo').textContent = 'Nova Categoria';
        });
    </script>
</body>

</html>