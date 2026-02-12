<?php
if (php_sapi_name() !== 'cli') {
    $allowedIPs = ['127.0.0.1', '::1'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, $allowedIPs, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}
require __DIR__ . '/config/connection.php';

$sql = "UPDATE agenda_visitas
        SET status = 'expirado'
        WHERE status = 'aguardando'
        AND expiracao_em < NOW()";

$affected = $pdo->exec($sql);

if (php_sapi_name() === 'cli') {
    echo date('[Y-m-d H:i:s]') . " Expirados: {$affected} registros\n";
}
