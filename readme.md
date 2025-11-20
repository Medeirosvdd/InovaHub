# InovaHub

Plataforma de notícias e conteúdo sobre inovação e tecnologia.

## 📋 Sobre o Projeto

O InovaHub é um sistema web para publicação e compartilhamento de notícias sobre inovação, tecnologia e startups. A plataforma permite que usuários se cadastrem, publiquem conteúdo, comentem em notícias e interajam com a comunidade.

## 🏗️ Estrutura do Projeto

```
/novahub
│
├── admin/                    # Painel administrativo
│   ├── categorias.php
│   ├── comentarios.php
│   ├── editar_usuario.php
│   ├── excluir_noticia.php
│   ├── index.php
│   ├── noticias.php
│   └── upload_noticia.php
│
├── assets/                   # Recursos estáticos
│   ├── css/
│   │   ├── style.css
│   │   └── theme.css
│   ├── img/                  # Imagens gerais
│   └── js/
│       ├── comentarios.js
│       ├── modal.js
│       └── theme.js
│
├── auth/                     # Sistema de autenticação
│   ├── cadastro.php
│   ├── login.php
│   ├── logout.php
│   └── recuperar_senha.php
│
├── banco_de_dados/           # Estrutura do banco
│   └── novahub.sql
│
├── database/                 # Configurações do banco
│   ├── conexao.php
│   └── funcoes.php
│
├── editor/                   # Área do editor
│   ├── index.php
│   ├── minhas_noticias.php
│   └── upload_noticia.php
│
├── includes/                 # Arquivos inclusivos
│   ├── admin_header.php
│   ├── comentario.php
│   ├── conexao.php
│   ├── footer.php
│   ├── funcoes.php
│   ├── header.php
│   └── upload.php
│
├── imagens_noticias/         # Imagens das notícias
│   ├── chip-revolucionario.jpg
│   ├── noticia-1.jpg
│   ├── startup-investimento.jpg
│   └── smartphone-dobrável.jpg
│
├── noticias/                 # Sistema de notícias
│   ├── editar_noticia.php
│   ├── excluir_noticia.php
│   ├── index.php
│   └── nova_noticia.php
│
├── usuario/                  # Área do usuário
│   ├── dashboard.php
│   ├── editar_perfil.php
│   ├── index.php
│   ├── meus_comentarios.php
│   └── noticias.php
│
├── buscar.php               # Sistema de busca
├── index.php               # Página inicial
└── readme.md              # Documentação
```

## 👥 Perfis de Usuário

### 👤 Usuário Comum
- Visualizar notícias
- Comentar em publicações
- Editar perfil pessoal
- Gerenciar próprios comentários

### ✍️ Editor/Colaborador
- Todas as funções do usuário comum
- Publicar notícias
- Editar próprias notícias
- Gerenciar conteúdo próprio

### ⚡ Administrador
- Todas as funções anteriores
- Gerenciar todos os usuários
- Moderar todos os comentários
- Gerenciar categorias
- Administrar todo o conteúdo

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP
- **Frontend**: HTML, CSS, JavaScript
- **Banco de Dados**: MySQL
- **Estilo**: CSS personalizado
- **Funcionalidades**: Sistema de upload de imagens, comentários, autenticação

## 📋 Pré-requisitos

- Servidor web (Apache, Nginx)
- PHP 7.4+
- MySQL 5.7+
- Navegador moderno

## 🚀 Instalação

1. **Configure o ambiente web**
   - Coloque os arquivos na pasta do servidor web
   - Configure as permissões para upload de imagens

2. **Configure o banco de dados**
   ```sql
   -- Importe o arquivo banco_de_dados/novahub.sql
   -- ou execute as queries de criação do banco
   ```

3. **Configure a conexão com o banco**
   - Edite `database/conexao.php` e `includes/conexao.php`
   - Configure host, usuário, senha e nome do banco

4. **Configure upload de imagens**
   - Verifique permissões da pasta `imagens_noticias/`
   - Configure tamanho máximo de upload no PHP

## 🔧 Configuração

### Arquivos de Conexão
Edite os seguintes arquivos com suas credenciais do banco:

```php
// database/conexao.php e includes/conexao.php
$host = "localhost";
$usuario = "seu_usuario";
$senha = "sua_senha";
$banco = "novahub";
```

### Configuração do Servidor
- PHP: habilite extensões MySQL e file_uploads
- Apache: mod_rewrite para URLs amigáveis (opcional)
- Permissões: pasta imagens_noticias com permissão de escrita

## 🎯 Funcionalidades Principais

### 📰 Sistema de Notícias
- Publicação de notícias com imagens
- Categorização de conteúdo
- Edição e exclusão de notícias
- Sistema de busca

### 💬 Sistema de Comentários
- Comentários em notícias
- Moderação de comentários
- Gestão de comentários por usuário

### 👤 Sistema de Usuários
- Cadastro e login seguro
- Perfis de usuário
- Recuperação de senha
- Dashboard personalizado

### 🖼️ Upload de Imagens
- Upload seguro de imagens
- Redimensionamento automático
- Validação de tipos de arquivo

## 🔒 Segurança

- Validação de entrada de dados
- Proteção contra SQL injection
- Sanitização de uploads
- Sistema de autenticação seguro
- Controle de acesso por níveis

## 🐛 Solução de Problemas

### Problemas Comuns

1. **Erro de conexão com o banco**
   - Verifique credenciais no arquivo de conexão
   - Confirme se o banco foi importado corretamente

2. **Upload de imagens não funciona**
   - Verifique permissões da pasta `imagens_noticias/`
   - Confirme configurações do PHP para upload

3. **Páginas em branco**
   - Habilite display_errors no PHP para debugging
   - Verifique logs de erro do servidor

## 🤝 Contribuindo

1. Faça fork do projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📞 Suporte

Em caso de dúvidas ou problemas, verifique:

1. Logs de erro do PHP e servidor web
2. Permissões de arquivos e pastas
3. Configurações do banco de dados

## 📄 Licença

Este projeto está sob licença MIT. Veja o arquivo LICENSE para mais detalhes.

---

**InovaHub** - Conectando ideias, impulsionando inovações! 🚀