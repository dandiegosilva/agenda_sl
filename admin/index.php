<?php
session_start();
require __DIR__ . '/../config/connection.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if ($usuario && $senha) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE usuario = :usuario LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($senha, $admin['senha_hash'])) {
            $_SESSION['admin_logado'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            session_regenerate_id(true);
            header('Location: admin_agenda.php');
            exit;
        } else {
            $erro = 'Usuário ou senha incorretos';
        }
    } else {
        $erro = 'Preencha todos os campos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — SL Desenvolvedores</title>
    <link rel="icon" href="../img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css" />
</head>
<body>

    <!-- Animated background grid -->
    <div class="bg-scene">
        <div class="grid-lines"></div>
        <div class="noise-overlay"></div>
    </div>

    <div class="page-wrapper">

        <main class="form-panel">
            <div class="card-wrap">

                <!-- Logo acima do card -->

            <div class="form-inner">
            <div class="top-logo">
                <img src="../img/logoSl.png" alt="SL Desenvolvedores">
                </div>
                <div class="form-header">
                    <div class="header-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="form-title">Bem-vindo</h2>
                        <p class="form-subtitle">Faça login para continuar</p>
                    </div>
                </div>

                <?php if ($erro): ?>
                    <div class="alert-error" role="alert">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form" novalidate>
                    <div class="field-group">
                        <label class="field-label" for="usuario">Usuário</label>
                        <div class="field-wrap">
                            <span class="field-icon">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <input
                                type="text"
                                id="usuario"
                                name="usuario"
                                class="field-input"
                                placeholder="seu.usuario"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="senha">Senha</label>
                        <div class="field-wrap">
                            <span class="field-icon">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                class="field-input"
                                placeholder="••••••••••"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-pw" aria-label="Mostrar senha" onclick="togglePassword()">
                                <svg id="eye-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span class="btn-text">Entrar no sistema</span>
                        <span class="btn-arrow">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </span>
                    </button>
                </form>
            </div><!-- /form-inner -->
            </div><!-- /card-wrap -->
        </main>

    </div>
    <script src="assets/js/logadm.js"></script>
</body>
</html>
