<?php

require __DIR__ . '/config/connection.php';
require __DIR__ . '/config/csrf.php';
require __DIR__ . '/config/validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

function retornarErro(string $mensagem, int $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $mensagem
    ]);
    exit;
}

function retornarSucesso(string $mensagem, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $mensagem,
        'data' => $data
    ]);
    exit;
}

/* ===========================
   CSRF
=========================== */

if (!CSRF::validarToken($_POST['csrf_token'] ?? null)) {
    retornarErro('Token inválido.', 403);
}

/* ===========================
   VALIDAÇÃO
=========================== */

$errors = [];

$nome = Validator::nome($_POST['nome_leiloeiro'] ?? '', 3, 100);
$telefone = Validator::telefone($_POST['telefone'] ?? '');
$cidade = Validator::cidade($_POST['cidade'] ?? '', 2, 100);
$qtdDev = Validator::inteiroPositivo($_POST['quantidade_desenvolvedores'] ?? 0, 1, 10);
$datasInput = $_POST['data_visita'] ?? '';

if (!$nome) $errors[] = 'Nome inválido.';
if (!$telefone) $errors[] = 'Telefone inválido.';
if (!$cidade) $errors[] = 'Cidade inválida.';
if (!$qtdDev) $errors[] = 'Quantidade inválida.';
if (empty($datasInput)) $errors[] = 'Selecione ao menos uma data.';

if (!empty($errors)) {
    retornarErro(implode(' ', $errors));
}

/* ===========================
   CONFIGURAÇÕES
=========================== */

try {
    $config = $pdo->query("SELECT * FROM configuracoes LIMIT 1")->fetch();

    if (!$config) {
        throw new Exception('Config não encontrada');
    }

    $valorPorDev = (float)$config['valor_padrao_desenvolvedor'];
    $limiteMax   = (int)$config['limite_max_desenvolvedores'];
    $horasExp    = (int)$config['horas_expiracao'];

    if ($qtdDev > $limiteMax) {
        retornarErro("Máximo permitido: {$limiteMax} desenvolvedores.");
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    retornarErro('Erro ao carregar configurações.', 500);
}

/* ===========================
   PROCESSAR DATAS
=========================== */

$datasArray = array_map('trim', explode(',', $datasInput));
$datasValidas = [];
$datasFormatadas = [];

foreach ($datasArray as $dataStr) {

    $dataObj = DateTime::createFromFormat('d/m/Y', $dataStr);

    if (!$dataObj) {
        retornarErro("Data inválida: {$dataStr}");
    }

    if ($dataObj < new DateTime('today')) {
        retornarErro("Data passada não permitida.");
    }

    $datasValidas[] = $dataObj;
    $datasFormatadas[] = $dataObj->format('d/m/Y');
}

if (empty($datasValidas)) {
    retornarErro('Nenhuma data válida informada.');
}

/* ===========================
   VERIFICAR DISPONIBILIDADE
=========================== */

try {

    foreach ($datasValidas as $dataObj) {

        $dataBanco = $dataObj->format('Y-m-d');

        // Está liberada?
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM agenda_aberta WHERE data = ?");
        $stmt->execute([$dataBanco]);
        if ($stmt->fetchColumn() == 0) {
            retornarErro("Data {$dataObj->format('d/m/Y')} não está disponível.");
        }

        // Está ocupada?
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM agenda_visitas 
            WHERE data_visita = ? 
            AND status IN ('aguardando','confirmado')
        ");
        $stmt->execute([$dataBanco]);

        if ($stmt->fetchColumn() > 0) {
            retornarErro("Data {$dataObj->format('d/m/Y')} já está reservada.");
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    retornarErro('Erro ao verificar disponibilidade.', 500);
}

/* ===========================
   INSERÇÃO (UM REGISTRO POR DIA)
=========================== */

$valorTotalGeral = $valorPorDev * $qtdDev * count($datasValidas);

$expiracao = (new DateTime())
    ->modify("+{$horasExp} hours")
    ->format('Y-m-d H:i:s');

try {

    $pdo->beginTransaction();

    $sql = "INSERT INTO agenda_visitas (
                nome_leiloeiro,
                telefone,
                cidade,
                data_visita,
                quantidade_desenvolvedores,
                valor_por_desenvolvedor,
                valor_total,
                status,
                expiracao_em,
                criado_em
            ) VALUES (
                :nome,
                :telefone,
                :cidade,
                :data,
                :qtd,
                :valorDev,
                :total,
                'aguardando',
                :expiracao,
                NOW()
            )";

    $stmt = $pdo->prepare($sql);

    foreach ($datasValidas as $dataObj) {

        $dataBanco = $dataObj->format('Y-m-d');

        $stmt->execute([
            'nome'      => $nome,
            'telefone'  => $telefone,
            'cidade'    => $cidade,
            'data'      => $dataBanco,
            'qtd'       => $qtdDev,
            'valorDev'  => $valorPorDev,
            'total'     => $valorPorDev * $qtdDev,
            'expiracao' => $expiracao
        ]);
    }

    $pdo->commit();

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    retornarErro('Erro ao criar reserva.', 500);
}

/* ===========================
   WHATSAPP
=========================== */

$telefoneWhats = getenv('WHATSAPP_PHONE') ?: '553899507998';

$mensagemWhats = sprintf(
    "Olá! Acabei de fazer uma reserva.\n\n" .
    "📋 Detalhes:\n" .
    "• Nome: %s\n" .
    "• Datas: %s\n" .
    "• Desenvolvedores: %d\n" .
    "• Valor Total: R$ %.2f\n\n" .
    "Por favor, envie a chave PIX.",
    $nome,
    implode(', ', $datasFormatadas),
    $qtdDev,
    $valorTotalGeral
);

$linkWhats = "https://wa.me/{$telefoneWhats}?text=" . urlencode($mensagemWhats);

CSRF::rotacionarToken();

retornarSucesso('Reserva criada com sucesso!', [
    'nome' => $nome,
    'datas' => $datasFormatadas,
    'quantidade' => $qtdDev,
    'valor_total' => number_format($valorTotalGeral, 2, ',', '.'),
    'expiracao' => $expiracao,
    'whatsapp_link' => $linkWhats
]);
