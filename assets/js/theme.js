document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const btn = document.getElementById('btn-theme');

    if (!btn) return;

    const temaSalvo = localStorage.getItem("tema_inovahub");

    if (temaSalvo === "dark") {
        body.classList.add("dark");
        btn.textContent = "🌙";
    } else {
        btn.textContent = "☀️";
    }

    btn.addEventListener('click', function () {
        body.classList.toggle("dark");

        const modoEscuro = body.classList.contains("dark");

        localStorage.setItem("tema_inovahub", modoEscuro ? "dark" : "light");

        btn.textContent = modoEscuro ? "🌙" : "☀️";
    });
});
