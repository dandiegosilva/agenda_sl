<?php
require __DIR__ . '/config/connection.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   DATAS ABERTAS DO BANCO
============================= */
$datasAbertas = $pdo->query("
    SELECT DATE_FORMAT(data, '%Y-%m-%d')
    FROM agenda_aberta
    WHERE data >= CURDATE()
")->fetchAll(PDO::FETCH_COLUMN);


/* =============================
   PERÍODO INICIAL AUTOMÁTICO
   (12/02/2026 → 13/03/2026)
============================= */
$inicio = new DateTime('2026-02-12');
$fim = new DateTime('2026-03-13');

while ($inicio <= $fim) {
    $datasAbertas[] = $inicio->format('Y-m-d');
    $inicio->modify('+1 day');
}


$datasAbertas = array_unique($datasAbertas);


/* =============================
   DATAS OCUPADAS
============================= */
$datasOcupadas = $pdo->query("
    SELECT DATE_FORMAT(data_visita, '%Y-%m-%d')
    FROM agenda_visitas
    WHERE status IN ('confirmado','aguardando')
")->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'abertas' => array_values($datasAbertas),
    'ocupadas' => $datasOcupadas
]);
