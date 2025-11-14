<?php

/**
 * Verifica se usuário é administrador
 */

require 'verifica_login.php';

if ($usuario['tipo'] !== 'admin') {
    $_SESSION['erro'] = "Acesso negado. Área restrita para administradores.";
    header('Location: ../index.php');
    exit();
}
