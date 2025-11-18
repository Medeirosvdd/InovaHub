-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 18/11/2025 às 20:07
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `inovahub`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `cor` varchar(7) DEFAULT '#007bff',
  `descricao` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `slug`, `cor`, `descricao`, `criado_em`) VALUES
(1, 'Tecnologia', 'tecnologia', '#007bff', 'Notícias sobre tecnologia em geral', '2025-11-18 15:21:31'),
(2, 'Startups', 'startups', '#28a745', 'Ecossistema de startups e empreendedorismo', '2025-11-18 15:21:31'),
(3, 'Inovação', 'inovacao', '#ffc107', 'Inovações e tendências do mercado', '2025-11-18 15:21:31'),
(4, 'Inteligência Artificial', 'inteligencia-artificial', '#dc3545', 'IA, machine learning e deep learning', '2025-11-18 15:21:31'),
(5, 'Hardware', 'hardware', '#6f42c1', 'Dispositivos, componentes e gadgets', '2025-11-18 15:21:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `noticia_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `aprovado` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comentarios`
--

INSERT INTO `comentarios` (`id`, `noticia_id`, `usuario_id`, `comentario`, `aprovado`, `criado_em`) VALUES
(1, 1, 2, 'Excelente artigo! A IA realmente está mudando tudo no mercado de trabalho.', 1, '2025-11-18 15:21:31'),
(2, 1, 3, 'Precisamos nos adaptar rapidamente a essas mudanças. Ótima reflexão!', 1, '2025-11-18 15:21:31'),
(3, 2, 2, 'Parabéns à equipe da TechCommerce! Ótima notícia para o ecossistema brasileiro.', 1, '2025-11-18 15:21:31'),
(4, 3, 3, 'Finalmente um smartphone dobrável com preço acessível! Ansioso para testar.', 1, '2025-11-18 15:21:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estatisticas`
--

CREATE TABLE `estatisticas` (
  `id` int(11) NOT NULL,
  `noticia_id` int(11) NOT NULL,
  `visualizacoes` int(11) DEFAULT 0,
  `data` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `estatisticas`
--

INSERT INTO `estatisticas` (`id`, `noticia_id`, `visualizacoes`, `data`) VALUES
(1, 1, 45, '2025-11-18'),
(2, 1, 35, '2025-11-17'),
(3, 2, 25, '2025-11-18'),
(4, 3, 15, '2025-11-18'),
(5, 4, 30, '2025-11-18'),
(6, 5, 40, '2025-11-18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `noticias`
--

CREATE TABLE `noticias` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `resumo` text NOT NULL,
  `noticia` longtext NOT NULL,
  `imagem` varchar(255) DEFAULT 'noticia.jpg',
  `autor` int(11) NOT NULL,
  `categoria` int(11) NOT NULL,
  `status` enum('rascunho','publicada','arquivada') DEFAULT 'rascunho',
  `visualizacoes` int(11) DEFAULT 0,
  `destaque` tinyint(1) DEFAULT 0,
  `data` timestamp NOT NULL DEFAULT current_timestamp(),
  `publicada_em` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `noticias`
--

INSERT INTO `noticias` (`id`, `titulo`, `slug`, `resumo`, `noticia`, `imagem`, `autor`, `categoria`, `status`, `visualizacoes`, `destaque`, `data`, `publicada_em`, `criado_em`, `atualizado_em`) VALUES
(1, 'Inteligência Artificial revoluciona mercado de trabalho', 'ia-revoluciona-mercado-trabalho', 'A IA está transformando como trabalhamos e criando novas oportunidades em diversas áreas profissionais.', 'A inteligência artificial está causando uma transformação significativa no mercado de trabalho. Novas profissões estão surgindo enquanto outras se adaptam às ferramentas de IA. Empresas que adotam essas tecnologias estão vendo aumentos de produtividade de até 40%.\n\nEspecialistas afirmam que o futuro do trabalho será uma colaboração entre humanos e máquinas, onde a IA cuidará das tarefas repetitivas e os profissionais se concentrarão em atividades estratégicas e criativas.', 'noticia-ia.jpg', 1, 4, 'publicada', 158, 0, '2025-11-18 15:21:31', NULL, '2025-11-18 15:21:31', '2025-11-18 17:41:24'),
(2, 'Startup brasileira recebe investimento de R$ 50 milhões', 'startup-brasileira-investimento', 'A TechCommerce, startup brasileira especializada em e-commerce, recebeu investimento recorde de fundo internacional.', 'A TechCommerce, startup brasileira especializada em soluções para e-commerce, anunciou hoje uma rodada de investimento série B de R$ 50 milhões liderada pelo fundo internacional TechVentures. O valor será utilizado para expansão na América Latina e desenvolvimento de novas funcionalidades de IA para sua plataforma.\n\n\"Esse investimento valida nossa estratégia e nos posiciona como líderes no segmento de e-commerce na região\", afirmou o CEO da empresa.', 'startup-investimento.jpg', 1, 2, 'publicada', 90, 0, '2025-11-18 15:21:31', NULL, '2025-11-18 15:21:31', '2025-11-18 15:25:10'),
(3, 'Novo smartphone com tela dobrável é lançado', 'smartphone-tela-dobravel-lancamento', 'A marca TechFlex anunciou hoje o lançamento do seu mais novo smartphone com tela dobrável com preço competitivo.', 'A marca TechFlex anunciou hoje o lançamento do seu mais novo smartphone com tela dobrável. O dispositivo promete revolucionar o mercado com sua tecnologia de display flexível e preço significativamente mais baixo que os concorrentes.\n\nO smartphone possui uma tela de 7,8 polegadas que pode ser dobrada ao meio, transformando-se em um dispositivo compacto. A empresa afirma que a tecnologia utilizada é mais durável que as versões anteriores e oferece melhor qualidade de imagem.', 'smartphone-dobravel.jpg', 1, 5, 'publicada', 46, 0, '2025-11-18 15:21:31', NULL, '2025-11-18 15:21:31', '2025-11-18 15:25:02'),
(4, 'Novas ferramentas de IA transformam desenvolvimento web', 'ia-ferramentas-desenvolvimento-web', 'Plataformas de IA estão revolucionando como desenvolvedores criam aplicações e sites.', 'As novas ferramentas de IA estão transformando como trabalhamos no desenvolvimento web. Plataformas como GitHub Copilot e ChatGPT estão acelerando significativamente o processo de codificação.\n\nDesenvolvedores relatam aumentos de produtividade de até 55% ao utilizar essas ferramentas para gerar código, debugar e documentar projetos. O futuro do desenvolvimento parece ser uma colaboração entre programadores humanos e assistentes de IA.', 'ia-ferramentas.jpg', 2, 4, 'publicada', 79, 0, '2025-11-18 15:21:31', NULL, '2025-11-18 15:21:31', '2025-11-18 18:31:01'),
(5, 'Empresa anuncia chip revolucionário para dispositivos móveis', 'chip-revolucionario-dispositivos-moveis', 'Nova tecnologia promete aumentar em 300% a eficiência energética de smartphones.', 'Uma fabricante líder em semicondutores anunciou hoje um chip revolucionário que promete transformar o mercado de dispositivos móveis. A nova tecnologia utiliza arquitetura inovadora que reduz o consumo de energia em até 70% enquanto dobra o desempenho.\n\nO chip deve chegar ao mercado no segundo semestre de 2024 e já está sendo testado por grandes fabricantes de smartphones. Especialistas afirmam que esta inovação pode estender a bateria dos dispositivos para até 3 dias de uso moderado.', 'chip-revolucionario.jpg', 1, 5, 'publicada', 115, 0, '2025-11-18 15:21:31', NULL, '2025-11-18 15:21:31', '2025-11-18 16:24:32'),
(7, 'Brasil avança na conectividade escolar e testa IA anti-roubo em smartphones', 'brasil-avan-a-na-conectividade-escolar-e-testa-ia-anti-roubo-em-smartphones-691cb48ed8376', 'País implementa estratégia para levar internet de alta velocidade a todas as escolas até 2026, enquanto Google testa no Brasil novo recurso de IA para', 'O Brasil intensifica dois avanços tecnológicos paralelos que impactam educação e segurança digital.\r\n\r\nPrimeiro, o programa National Strategy for Connected Schools (Estratégia Nacional de Escolas Conectadas) começou a ser executado para garantir que todas as escolas estaduais sejam conectadas à internet até o fim de 2026. \r\nDatacom\r\n+3\r\nITU\r\n+3\r\nAgência Brasil\r\n+3\r\n\r\nSegundo dados da pesquisa TIC Educação 2022, 94% das escolas de ensino básico no país já têm acesso à internet, mas só cerca de 58% têm computadores ou dispositivos para uso dos alunos. \r\nCetic.br\r\n\r\nEm complemento, o programa Aprender Conectado já conectou mais de 5 mil escolas públicas com internet de alta velocidade, incluindo regiões remotas, e está instalado em parceria com empresas de infraestrutura. \r\nTI Inside\r\n+1\r\n\r\nEm segundo plano, a Google anunciou que o Brasil será o primeiro país a testar no mercado um novo recurso de inteligência artificial que ajuda a travar smartphones Android em casos suspeitos de furto. O sistema inclui três modos de bloqueio: detecção por movimento típico de furto, bloqueio remoto via número de telefone e encerramento automático se o aparelho ficar offline por muito tempo. \r\nReuters\r\n+2\r\nPhoneArena\r\n+2\r\n\r\nA escolha do Brasil se justifica pelo número elevado de celulares furtados: em 2022 quase 1 milhão de aparelhos foram roubados no país. \r\nReuters\r\n+1\r\n\r\nImpactos esperados:\r\n\r\nNa educação: espera-se reduzir o fosso digital entre escolas urbanas e rurais, trazendo mais professores e alunos com acesso consistente à internet e plataformas online.\r\n\r\nNa segurança digital: o novo recurso da Google pode ampliar a proteção dos usuários de Android no Brasil, reduzindo fraudes e perdas de dados com dispositivos roubados.\r\n\r\nEm termos de infraestrutura: os projetos exigem investimentos em fibra óptica, satélite, equipamentos de rede, e energia alternativa em locais remotos — o que significa que há desafios logísticos e de manutenção pela frente.\r\n\r\nDesafios apontados:\r\n\r\nMesmo com 94% de escolas conectadas, muitas ainda não possuem dispositivos suficientes ou internet de qualidade para todos os alunos. \r\nCetic.br\r\n\r\nNas regiões mais remotas ou de difícil acesso, cabe superar obstáculos como falta de fibra, energia instável, baixo orçamento. Exemplos do programa apontam uso de satélite e geradores solares. \r\nTI Inside\r\n\r\nNo lado da segurança móvel, a adoção massiva do recurso da Google dependerá de compatibilidade de aparelhos, interesse dos usuários e do efetivo bloqueio de aparelhos roubados.', 'https://www.oficinadanet.com.br/media/post/55359/1200/bloqueio-offline-android-ia.jpg', 1, 1, 'publicada', 3, 1, '2025-11-18 18:01:50', '2025-11-18 22:01:50', '2025-11-18 18:01:50', '2025-11-18 18:39:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'avatar.jpg',
  `tipo` enum('admin','editor','usuario') DEFAULT 'usuario',
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `avatar`, `tipo`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, 'Administrador', 'admin@inovahub.com', '$2y$10$Ub2T0Wdpr.Q4X7e/V29cRuzeAT1noVBNnFV8LSrrLRJHeFDLMOMKG', 'avatar.jpg', 'admin', 'ativo', '2025-11-18 15:21:31', '2025-11-18 17:19:57'),
(2, 'João Editor', 'editor@email.com', '$2y$10$Ub2T0Wdpr.Q4X7e/V29cRuzeAT1noVBNnFV8LSrrLRJHeFDLMOMKG', 'avatar.jpg', 'editor', 'ativo', '2025-11-18 15:21:31', '2025-11-18 17:25:28'),
(3, 'Maria Usuária', 'usuario@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'avatar.jpg', 'usuario', 'ativo', '2025-11-18 15:21:31', '2025-11-18 15:21:31'),
(4, 'augusto', 'exemplo@gmail.com', '$2y$10$Ub2T0Wdpr.Q4X7e/V29cRuzeAT1noVBNnFV8LSrrLRJHeFDLMOMKG', 'avatar.jpg', 'usuario', 'ativo', '2025-11-18 15:23:28', '2025-11-18 15:23:28');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Índices de tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `noticia_id` (`noticia_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `estatisticas`
--
ALTER TABLE `estatisticas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `noticia_id` (`noticia_id`);

--
-- Índices de tabela `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `autor` (`autor`),
  ADD KEY `categoria` (`categoria`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `estatisticas`
--
ALTER TABLE `estatisticas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `estatisticas`
--
ALTER TABLE `estatisticas`
  ADD CONSTRAINT `estatisticas_ibfk_1` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`);

--
-- Restrições para tabelas `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `noticias_ibfk_1` FOREIGN KEY (`autor`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `noticias_ibfk_2` FOREIGN KEY (`categoria`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
