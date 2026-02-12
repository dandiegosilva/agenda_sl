<?php
   require __DIR__ . '/../config/connection.php';
   require_once __DIR__ . '/auth.php';
   require __DIR__ . '/../config/csrf.php';
   
   verificarAutenticacao();
   
   $where = [];
   $params = [];
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtrar'])) {
       if (!empty($_POST['cidade'])) {
           $where[] = "cidade LIKE :cidade";
           $params['cidade'] = "%" . $_POST['cidade'] . "%";
       }
       if (!empty($_POST['data'])) {
           $where[] = "data_visita = :data";
           $params['data'] = $_POST['data'];
       }
   }
   
   $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
   $config = $stmt->fetch(PDO::FETCH_ASSOC);
   
   if (!$config) {
       $config = [
           'valor_padrao_desenvolvedor' => 0,
           'limite_max_desenvolvedores' => 1
       ];
   }
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limpar'])) {
       header("Location: admin_agenda.php");
       exit;
   }
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'], $_POST['id'])) {

       if (!CSRF::validarToken($_POST['csrf_token'] ?? '')) {
           $_SESSION['toast_erro'] = 'Token de segurança inválido. Recarregue a página.';
           header("Location: admin_agenda.php");
           exit;
       }

       $id = (int) $_POST['id'];
       $acao = $_POST['acao'];
   
       try {
           if ($acao === 'confirmar') {
               $sql = "UPDATE agenda_visitas SET status = 'confirmado', confirmado_em = NOW() WHERE id = :id AND status = 'aguardando'";
               $stmt = $pdo->prepare($sql);
               $stmt->execute(['id' => $id]);
           }
   
           if ($acao === 'cancelar') {
               $sql = "UPDATE agenda_visitas SET status = 'cancelado' WHERE id = :id AND status = 'aguardando'";
               $stmt = $pdo->prepare($sql);
               $stmt->execute(['id' => $id]);
           }
   
           if ($acao === 'excluir') {
               $sql = "DELETE FROM agenda_visitas WHERE id = :id AND (status = 'confirmado' OR status = 'cancelado')";
               $stmt = $pdo->prepare($sql);
               $stmt->execute(['id' => $id]);
           }
   
           if ($acao === 'remarcar' && !empty($_POST['nova_data'])) {
               $novaData = $_POST['nova_data'];
               if ($novaData >= date('Y-m-d')) {
                   $sql = "UPDATE agenda_visitas SET data_visita = :novaData WHERE id = :id AND status = 'confirmado'";
                   $stmt = $pdo->prepare($sql);
                   $stmt->execute(['novaData' => $novaData, 'id' => $id]);
               }
           }
       } catch (PDOException $e) {
           error_log('Admin action error: ' . $e->getMessage());
           $_SESSION['toast_erro'] = 'Erro ao processar ação. Tente novamente.';
           header("Location: admin_agenda.php");
           exit;
       }
   
       header("Location: admin_agenda.php");
       exit;
   }
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_config'])) {
   
       if (!CSRF::validarToken($_POST['csrf_token'] ?? '')) {
           $_SESSION['toast_erro'] = 'Token inválido';
           header("Location: admin_agenda.php");
           exit;
       }
   
       $valorDev = str_replace(['.', ','], ['', '.'], $_POST['valor_por_dev']);
       $valorDev = (float) $valorDev;
       $limite   = (int) $_POST['limite_devs'];
   
       if ($valorDev <= 0 || $limite < 1) {
           $_SESSION['toast_erro'] = 'Valores inválidos';
           header("Location: admin_agenda.php");
           exit;
       }
   
       $stmt = $pdo->prepare("
           UPDATE configuracoes 
           SET valor_padrao_desenvolvedor = :valor,
               limite_max_desenvolvedores = :limite
           WHERE id = 1
       ");
   
       $stmt->execute([
           'valor'  => $valorDev,
           'limite' => $limite
       ]);
   
       $_SESSION['toast'] = "Configurações salvas com sucesso";
       header("Location: admin_agenda.php");
       exit;
   }
   
   $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";
   
   $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
   $porPagina = 20;
   $offset = ($pagina - 1) * $porPagina;
   
   $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM agenda_visitas $whereSql");
   $stmtTotal->execute($params);
   $totalRegistros = $stmtTotal->fetchColumn();
   $totalPaginas = ceil($totalRegistros / $porPagina);
   
   $sql = "SELECT * FROM agenda_visitas $whereSql ORDER BY data_visita ASC LIMIT :limit OFFSET :offset";
   $stmt = $pdo->prepare($sql);
   foreach ($params as $k => $v) {
       $stmt->bindValue($k, $v);
   }
   $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
   $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
   $stmt->execute();
   $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
   $totalVisitas = $totalRegistros;
   $confirmadas = count(array_filter($reservas, fn($r) => $r['status'] === 'confirmado'));
   $faturamento = array_sum(array_map(fn($r) => $r['status'] === 'confirmado' ? $r['valor_total'] : 0, $reservas));
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['datas'])) {

    $datasInseridas = 0;

    foreach ($_POST['datas'] as $dataStr) {

        if (!$dataStr) continue;

        $datasSeparadas = explode(",", $dataStr);

        foreach ($datasSeparadas as $data) {

            $data = trim($data);
            if (!$data) continue;

            // tenta formato BR
            $dateObj = DateTime::createFromFormat('d/m/Y', $data);

            // tenta formato SQL
            if (!$dateObj) {
                $dateObj = DateTime::createFromFormat('Y-m-d', $data);
            }

            // se inválida ignora
            if (!$dateObj) continue;

            $dataSQL = $dateObj->format('Y-m-d');

            $stmt = $pdo->prepare("
                INSERT IGNORE INTO agenda_aberta (data)
                VALUES (:data)
            ");

            $stmt->execute(['data' => $dataSQL]);

            // conta apenas se realmente inseriu
            if ($stmt->rowCount() > 0) {
                $datasInseridas++;
            }
        }
    }

    // TOAST
    if ($datasInseridas > 0) {
        $_SESSION['toast'] = "$datasInseridas data(s) inserida(s) no sistema com sucesso";
    } else {
        $_SESSION['toast'] = "Nenhuma nova data foi inserida (podem já existir ou ser inválidas)";
    }

    header("Location: admin_agenda.php");
    exit;
}

   ?>
<!doctype html>
<html lang="pt-BR">
   <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Painel Administrativo - SL</title>
      <link rel="stylesheet" href="assets/css/admin.css" />
      <link rel="icon" href="../img/favicon.svg" type="image/svg+xml">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
      <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
      <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
      <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   </head>
   <body>
      <section class="header-blog">
         <div class="container">
            <img src="../img/logoSl.png" alt="Logo" class="logo-admin" />
         </div>
      </section>
      <div class="admin-container">
         <div class="row mb-4">
            <div class="col-12 col-md-4 mb-3">
               <div class="card text-center shadow-sm">
                  <div class="card-body">
                     <h6>Total de Visitas</h6>
                     <h3><?= $totalVisitas ?></h3>
                  </div>
               </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
               <div class="card text-center shadow-sm">
                  <div class="card-body">
                     <h6>Confirmadas</h6>
                     <h3><?= $confirmadas ?></h3>
                  </div>
               </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
               <div class="card text-center shadow-sm">
                  <div class="card-body">
                     <h6>Faturamento</h6>
                     <h3>R$ <?= number_format($faturamento,2,',','.') ?></h3>
                  </div>
               </div>
            </div>
         </div>
         <form method="POST" class="row g-2 mb-3">
            <div class="col-12 col-md-4">
               <input type="text" name="cidade" class="form-control" placeholder="Cidade" value="<?= htmlspecialchars($_POST['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="col-12 col-md-4">
               <input type="date" name="data" class="form-control" placeholder="Data" value="<?= htmlspecialchars($_POST['data'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
               <button type="submit" name="filtrar" class="btn btn-success flex-fill">Filtrar</button>
               <button type="submit" class="btn btn-secondary flex-fill" name="limpar">Limpar</button>
            </div>
         </form>
         <div class="mt-4">
            <div class="card shadow-sm border rounded-4 mb-4">
               <div class="card-body">
                  <h5 class="fw-bold mb-3">Configurações do Sistema</h5>
                  <form method="POST" class="row g-3 align-items-end">
                     <input type="hidden" name="csrf_token" value="<?= CSRF::gerarToken(); ?>">
                     <div class="col-12 col-md-4">
                        <label class="form-label">Valor por Desenvolvedor (R$)</label>
                        <input
                        type="text"
                        name="valor_por_dev"
                        id="valor_por_dev"
                        class="form-control"
                        value="<?= number_format((float)$config['valor_padrao_desenvolvedor'], 2, ',', '.') ?>"
                        required
                     >
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label">Limite de Desenvolvedores</label>
                        <input
                           type="number"
                           name="limite_devs"
                           class="form-control"
                           value="<?= (int)$config['limite_max_desenvolvedores'] ?>"
                           required
                           >
                     </div>
                     <div class="col-12 col-md-4">
                        <button
                           type="submit"
                           name="salvar_config"
                           class="btn btn-success w-100"
                           >
                        Salvar Configurações
                        </button>
                     </div>
                  </form>
                  <form method="POST" id="form-datas" class="row g-2 align-items-end mb-4">
                     <div class="col-12 col-md-6">
                        <label class="form-label">Liberar datas na agenda</label>
                        <input type="text" id="datas_agenda" name="datas[]" class="form-control" placeholder="Clique para selecionar datas" required>
                     </div>
                     <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-success w-100">Salvar Datas</button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
         <div class="table-responsive">
            <table class="table table-hover align-middle">
               <thead class="table-primary">
                  <tr>
                     <th>Leiloeiro</th>
                     <th>Telefone</th>
                     <th>Cidade</th>
                     <th>Data</th>
                     <th>Devs</th>
                     <th>Total</th>
                     <th>Status</th>
                     <th>Ações</th>
                  </tr>
               </thead>
               <tbody>
                  <?php if (!$reservas): ?>
                  <tr>
                     <td colspan="8" class="text-center">Nenhum registro encontrado</td>
                  </tr>
                  <?php endif; ?>
                  <?php foreach ($reservas as $r): ?>
                  <tr>
                     <td><?= htmlspecialchars($r['nome_leiloeiro'], ENT_QUOTES, 'UTF-8') ?></td>
                     <!-- TELEFONE FORMATADO -->
                     <td>
                        <?php
                           $tel = preg_replace('/\D/', '', $r['telefone']);
                           
                           if (strlen($tel) === 11) {
                               $tel = preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $tel);
                           }
                           
                           echo htmlspecialchars($tel, ENT_QUOTES, 'UTF-8');
                           ?>
                     </td>
                     <td><?= htmlspecialchars($r['cidade'], ENT_QUOTES, 'UTF-8') ?></td>
                     <td><?= date('d/m/Y', strtotime($r['data_visita'])) ?></td>
                     <td><?= (int)$r['quantidade_desenvolvedores'] ?></td>
                     <td>R$ <?= number_format($r['valor_total'],2,',','.') ?></td>
                     <td>
                        <span class="status <?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= ucfirst(htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8')) ?>
                        </span>
                     </td>
                     <td>
                        <?php if ($r['status'] === 'aguardando'): ?>
                        <!-- CONFIRMAR -->
                        <form method="POST" class="form-confirmar d-inline mb-1">
                           <input type="hidden" name="csrf_token" value="<?= CSRF::gerarToken(); ?>">
                           <input type="hidden" name="acao" value="confirmar" />
                           <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                           <button type="submit" class="btn btn-sm btn-success">
                           Confirmar
                           </button>
                        </form>
                        <!-- CANCELAR -->
                        <form method="POST" class="form-cancelar d-inline mb-1">
                           <input type="hidden" name="csrf_token" value="<?= CSRF::gerarToken(); ?>">
                           <input type="hidden" name="acao" value="cancelar" />
                           <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                           <button type="submit" class="btn btn-sm btn-danger">
                           Cancelar
                           </button>
                        </form>
                        <?php endif; ?>
                        <?php if ($r['status'] === 'confirmado'): ?>
                        <!-- REMARCAR -->
                        <button type="button"
                           class="btn btn-sm btn-warning btn-remarcar"
                           data-id="<?= (int)$r['id'] ?>">
                        Remarcar
                        </button>
                        <form method="POST"
                           class="form-remarcar d-none"
                           id="form-remarcar-<?= (int)$r['id'] ?>">
                           <input type="hidden" name="csrf_token" value="<?= CSRF::gerarToken(); ?>">
                           <input type="hidden" name="acao" value="remarcar" />
                           <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                           <input type="date" name="nova_data" required />
                        </form>
                        <?php endif; ?>
                        <?php if ($r['status'] === 'confirmado' || $r['status'] === 'cancelado'): ?>
                        <!-- EXCLUIR -->
                        <form method="POST" class="form-excluir d-inline mb-1">
                           <input type="hidden" name="csrf_token" value="<?= CSRF::gerarToken(); ?>">
                           <input type="hidden" name="acao" value="excluir" />
                           <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                           <button type="submit" class="btn btn-sm btn-danger">
                           Excluir
                           </button>
                        </form>
                        <?php endif; ?>
                     </td>
                  </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         </div>
         <!-- PAGINAÇÃO -->
         <nav aria-label="Paginação">
            <ul class="pagination justify-content-center mt-3">
               <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                  <form method="POST">
                     <input type="hidden" name="pagina" value="<?= $pagina - 1 ?>">
                     <button class="page-link">&laquo;</button>
                  </form>
               </li>
               <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
               <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                  <form method="POST">
                     <input type="hidden" name="pagina" value="<?= $i ?>">
                     <button class="page-link"><?= $i ?></button>
                  </form>
               </li>
               <?php endfor; ?>
               <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                  <form method="POST">
                     <input type="hidden" name="pagina" value="<?= $pagina + 1 ?>">
                     <button class="page-link">&raquo;</button>
                  </form>
               </li>
            </ul>
         </nav>
      </div>
      <script>
         window.APP = {
             toast: <?php echo json_encode($_SESSION['toast'] ?? null); ?>,
             toastErro: <?php echo json_encode($_SESSION['toast_erro'] ?? null); ?>
         };
      </script>
      <?php unset($_SESSION['toast'], $_SESSION['toast_erro']); ?>
      <script src="assets/js/admin.js"></script>
      <script>
         window.BASE_URL = "<?= dirname($_SERVER['SCRIPT_NAME']) ?>/";
      </script>
   </body>
</html>
