<?php

function buscarUsuarioPorEmail(PDO $pdo, string $email) {
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function buscarUsuarioPorId(PDO $pdo, int $id) {
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function usuarioLogado(PDO $pdo) {
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }
    return buscarUsuarioPorId($pdo, (int)$_SESSION['usuario_id']);
}

function resumoTexto(string $texto, int $limite = 200) {
    $textoLimpo = strip_tags($texto);
    if (mb_strlen($textoLimpo) <= $limite) {
        return $textoLimpo;
    }
    return mb_substr($textoLimpo, 0, $limite) . "...";
}

function ehAdmin(?array $usuario): bool {
    if (!$usuario) {
        return false;
    }
    return isset($usuario['is_admin']) && (int)$usuario['is_admin'] === 1;
}
