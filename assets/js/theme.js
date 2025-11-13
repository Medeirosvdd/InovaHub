document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const btn = document.getElementById('btn-theme');

    if (!btn) return;

    function setCookie(nome, valor, dias) {
        const data = new Date();
        data.setTime(data.getTime() + (dias * 24 * 60 * 60 * 1000));
        document.cookie = nome + "=" + valor + ";expires=" + data.toUTCString() + ";path=/";
    }

    function getCookie(nome) {
        const nomeEQ = nome + "=";
        const cookies = document.cookie.split(';');
        for (let c of cookies) {
            c = c.trim();
            if (c.indexOf(nomeEQ) === 0) return c.substring(nomeEQ.length);
        }
        return null;
    }

    const temaSalvo = getCookie('tema_inovahub');
    if (temaSalvo === 'light') {
        body.classList.add('light');
    }

    btn.addEventListener('click', function () {
        body.classList.toggle('light');
        const modoClaro = body.classList.contains('light');

        setCookie('tema_inovahub', modoClaro ? 'light' : 'dark', 365);
    });
});
