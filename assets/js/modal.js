// assets/js/modal.js
document.addEventListener('DOMContentLoaded', function () {
    const btnAbrirModal = document.getElementById('btnAbrirModal');
    const modal = document.getElementById('modalLogin');
    const closeBtn = document.querySelector('.close');

    function abrirModal() {
        if (modal) {
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }
    }

    function fecharModal() {
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', abrirModal);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', fecharModal);
    }

    // Fechar modal ao clicar fora
    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            fecharModal();
        }
    });

    // Fechar com ESC
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            fecharModal();
        }
    });
});