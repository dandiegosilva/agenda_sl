<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function verificarAutenticacao() {
    if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
        header('Location: index.php');
        exit;
    }

    if (isset($_SESSION['ultimo_acesso'])) {
        if (time() - $_SESSION['ultimo_acesso'] > 1800) {
            session_destroy();
            header('Location: index.php?timeout=1');
            exit;
        }
    }
    $_SESSION['ultimo_acesso'] = time();
}
