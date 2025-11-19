// assets/js/comentarios.js
class GerenciadorComentarios {
    constructor() {
        this.form = document.getElementById('form-comentario');
        this.listaComentarios = document.getElementById('lista-comentarios');
        this.btnEnviar = document.getElementById('btn-enviar-comentario');
        this.contadorCaracteres = document.getElementById('contador-caracteres');
        this.maxCaracteres = 1000;

        this.inicializarEventos();
    }

    inicializarEventos() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.enviarComentario(e));
        }

        const textarea = document.getElementById('comentario');
        if (textarea && this.contadorCaracteres) {
            textarea.addEventListener('input', () => this.atualizarContador());
            this.atualizarContador(); // Inicializar contador
        }

        // Event delegation para respostas
        if (this.listaComentarios) {
            this.listaComentarios.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-responder')) {
                    this.responderComentario(e);
                }
            });
        }
    }

    atualizarContador() {
        const textarea = document.getElementById('comentario');
        const caracteres = textarea.value.length;
        const restantes = this.maxCaracteres - caracteres;

        this.contadorCaracteres.textContent = `${caracteres}/${this.maxCaracteres}`;

        if (restantes < 0) {
            this.contadorCaracteres.style.color = '#dc3545';
        } else if (restantes < 100) {
            this.contadorCaracteres.style.color = '#ffc107';
        } else {
            this.contadorCaracteres.style.color = '#28a745';
        }
    }

    async enviarComentario(e) {
        e.preventDefault();

        const formData = new FormData(this.form);
        const comentario = formData.get('comentario').trim();

        if (!comentario || comentario.length < 2) {
            this.mostrarAlerta('Por favor, digite um comentário com pelo menos 2 caracteres.', 'erro');
            return;
        }

        if (comentario.length > this.maxCaracteres) {
            this.mostrarAlerta(`O comentário não pode ter mais de ${this.maxCaracteres} caracteres.`, 'erro');
            return;
        }

        this.btnEnviar.disabled = true;
        this.btnEnviar.innerHTML = '⏳ Enviando...';

        try {
            const resposta = await fetch('../includes/comentar.php', {
                method: 'POST',
                body: formData
            });

            const dados = await resposta.json();

            if (dados.sucesso) {
                this.mostrarAlerta(dados.mensagem, 'sucesso');
                this.adicionarComentarioNaLista(dados.comentario);
                this.form.reset();
                this.atualizarContador();

                // Atualizar contador de comentários
                this.atualizarContadorComentarios();
            } else {
                this.mostrarAlerta(dados.erro, 'erro');
            }
        } catch (erro) {
            this.mostrarAlerta('Erro de conexão. Tente novamente.', 'erro');
        } finally {
            this.btnEnviar.disabled = false;
            this.btnEnviar.innerHTML = '💬 Publicar Comentário';
        }
    }

    adicionarComentarioNaLista(comentario) {
        if (!this.listaComentarios) return;

        const elementoComentario = this.criarElementoComentario(comentario);

        // Se é uma resposta, adicionar na thread do comentário pai
        if (comentario.parent_id) {
            const comentarioPai = document.getElementById(`comentario-${comentario.parent_id}`);
            if (comentarioPai) {
                const respostas = comentarioPai.querySelector('.respostas') || this.criarContainerRespostas(comentarioPai);
                respuestas.appendChild(elementoComentario);
            }
        } else {
            // É um comentário principal
            this.listaComentarios.insertBefore(elementoComentario, this.listaComentarios.firstChild);
        }

        // Mostrar o comentário com animação
        elementoComentario.style.opacity = '0';
        elementoComentario.style.transform = 'translateY(-20px)';

        setTimeout(() => {
            elementoComentario.style.transition = 'all 0.3s ease';
            elementoComentario.style.opacity = '1';
            elementoComentario.style.transform = 'translateY(0)';
        }, 100);
    }

    criarElementoComentario(comentario) {
        const div = document.createElement('div');
        div.className = `comentario ${comentario.parent_id ? 'resposta' : ''}`;
        div.id = `comentario-${comentario.id}`;

        const badgeAdmin = comentario.is_admin ? '<span class="badge-admin">👑</span>' : '';
        const statusModeracao = !comentario.aprovado ? '<span class="status-pendente">⏳ Aguardando moderação</span>' : '';

        div.innerHTML = `
            <div class="comentario-header">
                <div class="avatar-usuario">${comentario.avatar}</div>
                <div class="info-usuario">
                    <strong>${comentario.usuario_nome}</strong>
                    ${badgeAdmin}
                    <span class="data-comentario">${comentario.criado_em}</span>
                </div>
            </div>
            <div class="comentario-conteudo">
                <p>${comentario.comentario}</p>
                ${statusModeracao}
            </div>
            <div class="comentario-acoes">
                <button class="btn-responder" data-comentario-id="${comentario.id}">
                    💬 Responder
                </button>
            </div>
            <div class="respostas"></div>
        `;

        return div;
    }

    criarContainerRespostas(comentarioPai) {
        const div = document.createElement('div');
        div.className = 'respostas';
        comentarioPai.appendChild(div);
        return div;
    }

    responderComentario(e) {
        const comentarioId = e.target.getAttribute('data-comentario-id');

        // Criar formulário de resposta
        const formularioResposta = this.criarFormularioResposta(comentarioId);

        // Inserir após o comentário
        const comentario = e.target.closest('.comentario');
        comentario.appendChild(formularioResposta);

        // Focar no textarea
        const textarea = formularioResposta.querySelector('textarea');
        textarea.focus();

        // Remover botão de responder temporariamente
        e.target.style.display = 'none';
    }

    criarFormularioResposta(parentId) {
        const div = document.createElement('div');
        div.className = 'form-resposta';
        div.innerHTML = `
            <form class="form-comentario">
                <input type="hidden" name="parent_id" value="${parentId}">
                <div class="form-group">
                    <textarea name="comentario" placeholder="Escreva sua resposta..." rows="3" maxlength="1000"></textarea>
                    <div class="contador-caracteres">0/1000</div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancelar">Cancelar</button>
                    <button type="submit" class="btn-enviar">💬 Responder</button>
                </div>
            </form>
        `;

        // Adicionar eventos
        const form = div.querySelector('form');
        const btnCancelar = div.querySelector('.btn-cancelar');
        const textarea = div.querySelector('textarea');
        const contador = div.querySelector('.contador-caracteres');

        form.addEventListener('submit', (e) => this.enviarResposta(e, parentId));
        btnCancelar.addEventListener('click', () => div.remove());

        textarea.addEventListener('input', () => {
            const caracteres = textarea.value.length;
            contador.textContent = `${caracteres}/1000`;
        });

        return div;
    }

    async enviarResposta(e, parentId) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const noticiaId = document.querySelector('input[name="noticia_id"]').value;
        formData.append('noticia_id', noticiaId);

        const btnEnviar = form.querySelector('.btn-enviar');
        const comentarioOriginal = formData.get('comentario').trim();

        if (!comentarioOriginal || comentarioOriginal.length < 2) {
            this.mostrarAlerta('Por favor, digite uma resposta com pelo menos 2 caracteres.', 'erro');
            return;
        }

        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '⏳ Enviando...';

        try {
            const resposta = await fetch('../includes/comentar.php', {
                method: 'POST',
                body: formData
            });

            const dados = await resposta.json();

            if (dados.sucesso) {
                this.mostrarAlerta(dados.mensagem, 'sucesso');
                this.adicionarComentarioNaLista(dados.comentario);
                form.closest('.form-resposta').remove();

                // Restaurar botão de responder
                const btnResponderOriginal = document.querySelector(`[data-comentario-id="${parentId}"]`);
                if (btnResponderOriginal) {
                    btnResponderOriginal.style.display = 'inline-block';
                }
            } else {
                this.mostrarAlerta(dados.erro, 'erro');
            }
        } catch (erro) {
            this.mostrarAlerta('Erro de conexão. Tente novamente.', 'erro');
        } finally {
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = '💬 Responder';
        }
    }

    mostrarAlerta(mensagem, tipo) {
        // Remover alertas anteriores
        const alertasAnteriores = document.querySelectorAll('.alert-comentario');
        alertasAnteriores.forEach(alerta => alerta.remove());

        const alerta = document.createElement('div');
        alerta.className = `alert-comentario alert-${tipo}`;
        alerta.innerHTML = `
            <span>${mensagem}</span>
            <button class="fechar-alerta">&times;</button>
        `;

        // Adicionar antes do formulário
        this.form.parentNode.insertBefore(alerta, this.form);

        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.style.opacity = '0';
                setTimeout(() => alerta.remove(), 300);
            }
        }, 5000);

        // Botão fechar
        alerta.querySelector('.fechar-alerta').addEventListener('click', () => {
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 300);
        });
    }

    atualizarContadorComentarios() {
        const contador = document.querySelector('.total-comentarios');
        if (contador) {
            const atual = parseInt(contador.textContent) || 0;
            contador.textContent = atual + 1;
        }
    }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    new GerenciadorComentarios();
});