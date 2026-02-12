<?php
require __DIR__ . '/../config/connection.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   DATAS ABERTAS
============================= */
$datasAbertas = $pdo->query("
    SELECT DATE_FORMAT(data, '%Y-%m-%d')
    FROM agenda_aberta
    WHERE data >= CURDATE()
")->fetchAll(PDO::FETCH_COLUMN);


/* =============================
   TODAS OCUPADAS (CONFIRMADO + AGUARDANDO)
============================= */
$datasOcupadas = $pdo->query("
    SELECT DATE_FORMAT(data_visita, '%Y-%m-%d')
    FROM agenda_visitas
    WHERE status IN ('confirmado','aguardando')
")->fetchAll(PDO::FETCH_COLUMN);


echo json_encode([
    'abertas' => $datasAbertas,
    'ocupadas' => $datasOcupadas
]);
