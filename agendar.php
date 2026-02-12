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

if (!CSRF::validarToken($_POST['csrf_token'] ?? null)) {
    retornarErro('Token de segurança inválido. Por favor, recarregue a página e tente novamente.', 403);
}

$errors = [];

$nome = Validator::nome($_POST['nome_leiloeiro'] ?? '', 3, 100);
if (!$nome) {
    $errors[] = 'Nome do leiloeiro inválido. Deve ter entre 3 e 100 caracteres.';
}

$telefone = Validator::telefone($_POST['telefone'] ?? '');
if (!$telefone) {
    $errors[] = 'Telefone inválido. Use o formato: (00) 00000-0000';
}

$cidade = Validator::cidade($_POST['cidade'] ?? '', 2, 100);
if (!$cidade) {
    $errors[] = 'Cidade inválida. Deve ter entre 2 e 100 caracteres.';
}

$dataObj = Validator::data($_POST['data_visita'] ?? '');
if (!$dataObj) {
    $errors[] = 'Data inválida. Selecione uma data futura válida.';
}

$qtdDev = Validator::inteiroPositivo($_POST['quantidade_desenvolvedores'] ?? 0, 1, 10);
if (!$qtdDev) {
    $errors[] = 'Quantidade de desenvolvedores inválida.';
}

if (!empty($errors)) {
    retornarErro(implode(' ', $errors), 400);
}

try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch();

    if (!$config) {
        throw new Exception('Configurações não encontradas');
    }

    $valorPorDev = (float)$config['valor_padrao_desenvolvedor'];
    $limiteMax   = (int)$config['limite_max_desenvolvedores'];
    $horasExp    = (int)$config['horas_expiracao'];

    if ($qtdDev > $limiteMax) {
        retornarErro("Quantidade máxima permitida: {$limiteMax} desenvolvedores.", 400);
    }

} catch (Exception $e) {
    error_log('Config Error: ' . $e->getMessage());
    retornarErro('Erro ao processar configurações. Tente novamente.', 500);
}

$dataBanco = $dataObj->format('Y-m-d');

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM agenda_aberta
        WHERE data = :data
    ");
    $stmt->execute(['data' => $dataBanco]);
    $dataLiberada = $stmt->fetch();

    $dataInicioPredefinida = new DateTime('2026-02-12');
    $dataFimPredefinida = new DateTime('2026-03-13');

    $isDataPredefinida = ($dataObj >= $dataInicioPredefinida && $dataObj <= $dataFimPredefinida);

    if ($dataLiberada['total'] == 0 && !$isDataPredefinida) {
        retornarErro('Esta data não está disponível para agendamento.', 400);
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM agenda_visitas
        WHERE data_visita = :data
        AND status IN ('aguardando', 'confirmado')
    ");
    $stmt->execute(['data' => $dataBanco]);
    $ocupada = $stmt->fetch();

    if ($ocupada['total'] > 0) {
        retornarErro('Esta data já está reservada. Por favor, escolha outra data.', 400);
    }

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    retornarErro('Erro ao verificar disponibilidade. Tente novamente.', 500);
}

$valorTotal = $valorPorDev * $qtdDev;

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
    $stmt->execute([
        'nome'      => $nome,
        'telefone'  => $telefone,
        'cidade'    => $cidade,
        'data'      => $dataBanco,
        'qtd'       => $qtdDev,
        'valorDev'  => $valorPorDev,
        'total'     => $valorTotal,
        'expiracao' => $expiracao
    ]);

    $reservaId = $pdo->lastInsertId();

    $pdo->commit();

    error_log(sprintf(
        'Reserva criada com sucesso - ID: %d | Nome: %s | Data: %s | Valor: R$ %.2f',
        $reservaId,
        $nome,
        $dataBanco,
        $valorTotal
    ));

    if (isset($rateLimit)) {
        $rateLimit->resetar('booking');
    }

    CSRF::rotacionarToken();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Insert Error: ' . $e->getMessage());
    retornarErro('Erro ao criar reserva. Por favor, tente novamente.', 500);
}

$telefoneWhats = getenv('WHATSAPP_PHONE') ?: '553899507998';

$mensagemWhats = sprintf(
    "Olá, Tiago Felipe! Acabei de fazer uma reserva na sua agenda.\n\n" .
    "📋 *Detalhes da Reserva:*\n" .
    "• Nome: %s\n" .
    "• Data: %s\n" .
    "• Desenvolvedores: %d\n" .
    "• Valor Total: R$ %.2f\n\n" .
    "Por favor, me envie a chave PIX para efetuar o pagamento.",
    $nome,
    $dataObj->format('d/m/Y'),
    $qtdDev,
    $valorTotal
);

$linkWhats = sprintf(
    'https://wa.me/%s?text=%s',
    $telefoneWhats,
    urlencode($mensagemWhats)
);

retornarSucesso('Reserva criada com sucesso!', [
    'reserva_id' => $reservaId,
    'nome' => $nome,
    'data' => $dataObj->format('d/m/Y'),
    'quantidade' => $qtdDev,
    'valor_total' => number_format($valorTotal, 2, ',', '.'),
    'expiracao' => $expiracao,
    'whatsapp_link' => $linkWhats
]);
