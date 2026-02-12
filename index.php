<?php
   require __DIR__ . '/config/connection.php';
   require __DIR__ . '/config/csrf.php';
   
   $config = $pdo->query("SELECT * FROM configuracoes LIMIT 1")->fetch();
   $valorPorDev = (float)$config['valor_padrao_desenvolvedor'];
   $limiteMax   = (int)$config['limite_max_desenvolvedores'];
   ?>
<!DOCTYPE html>
<html lang="pt-BR">
   <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta name="description" content="Agendamento de Visita Técnica - SL">
      <meta name="theme-color" content="#1e92ff">
      <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
      <title>Agendamento de Visita Técnica | SL</title>
      <link rel="preconnect" href="https://cdn.jsdelivr.net">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <!-- Stylesheets -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
      <link rel="stylesheet" href="css/style2.css">
      <!-- Fonts -->
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
   </head>
   <body class="bg-light">
      <div class="container py-5">
         <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
               <div class="text-center mb-4" style="margin-top: -23px;">
                  <img src="img/logoSl.png" alt="SL Desenvolvedores" class="logo-principal" loading="lazy">
               </div>
               <div class="card premium-card shadow-lg border-0 rounded-4">
                  <div class="card-body p-4">
                     <h1 class="h3 text-center mb-2 fw-bold text-primary">
                        📅 Agendamento de Visita Técnica
                     </h1>
                     <p class="text-center text-muted mb-4 small">
                        Preencha os dados abaixo para reservar sua data
                     </p>
                     <div role="alert" aria-live="polite" id="form-erros" class="d-none"></div>
                     <form method="POST" action="agendar.php" id="formAgendamento" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= CSRF::gerarToken(); ?>">
                        <div class="mb-3">
                           <label for="nome_leiloeiro" class="form-label fw-semibold">
                           Nome do Leiloeiro <span class="text-danger" aria-hidden="true">*</span>
                           </label>
                           <input 
                              type="text" 
                              id="nome_leiloeiro"
                              name="nome_leiloeiro" 
                              class="form-control form-control-lg" 
                              placeholder="Digite o seu nome"
                              required
                              minlength="3"
                              maxlength="100"
                              autocomplete="name"
                              aria-required="true"
                              >
                           <div class="invalid-feedback" role="alert">
                              Por favor, informe o seu nome.
                           </div>
                        </div>
                        <!-- Telefone -->
                        <div class="mb-3">
                           <label for="telefone" class="form-label fw-semibold">
                           Telefone <span class="text-danger" aria-hidden="true">*</span>
                           </label>
                           <input 
                              type="tel" 
                              id="telefone"
                              name="telefone" 
                              class="form-control form-control-lg telefone" 
                              placeholder="(00) 00000-0000"
                              required
                              autocomplete="tel"
                              aria-required="true"
                              >
                           <div class="invalid-feedback" role="alert">
                              Por favor, informe um telefone válido.
                           </div>
                        </div>
                        <!-- Cidade -->
                        <div class="mb-3">
                           <label for="cidade" class="form-label fw-semibold">
                           Cidade <span class="text-danger" aria-hidden="true">*</span>
                           </label>
                           <input 
                              type="text" 
                              id="cidade"
                              name="cidade" 
                              class="form-control form-control-lg" 
                              placeholder="Digite a cidade"
                              required
                              minlength="2"
                              maxlength="100"
                              autocomplete="address-level2"
                              aria-required="true"
                              >
                           <div class="invalid-feedback" role="alert">
                              Por favor, informe a cidade.
                           </div>
                        </div>
                        <!-- Data da Visita -->
                        <div class="mb-3">
                           <label for="data_visita" class="form-label fw-semibold">
                           Data da Visita <span class="text-danger" aria-hidden="true">*</span>
                           </label>
                           <input 
                              type="text" 
                              id="data_visita" 
                              name="data_visita" 
                              class="form-control form-control-lg" 
                              placeholder="Clique para selecionar"
                              required
                              readonly
                              aria-required="true"
                              >
                           <div class="invalid-feedback" role="alert">
                              Por favor, selecione uma data disponível.
                           </div>
                           <div class="form-text">
                              <i class="bi bi-info-circle"></i>
                              <small>⚠️ A reserva é válida por 24 horas após o envio</small>
                           </div>
                        </div>
                        <div class="mb-4">
                           <label for="quantidade_desenvolvedores" class="form-label fw-semibold">
                           Quantidade de Desenvolvedores <span class="text-danger" aria-hidden="true">*</span>
                           </label>
                           <select 
                              id="quantidade_desenvolvedores"
                              name="quantidade_desenvolvedores" 
                              class="form-select form-select-lg" 
                              data-valor="<?= $valorPorDev ?>"
                              required
                              aria-required="true"
                              >
                              <?php for ($i = 1; $i <= $limiteMax; $i++): ?>
                              <option value="<?= $i ?>">
                                 <?= $i ?> desenvolvedor<?= $i > 1 ? 'es' : '' ?>
                                 <?php if ($i === 1): ?>
                                 (Tiago Felipe)
                                 <?php endif; ?>
                              </option>
                              <?php endfor; ?>
                           </select>
                        </div>
                        <div class="alert alert-info border-0 shadow-sm mb-4" role="status" aria-live="polite">
                           <div class="d-flex justify-content-between align-items-center">
                              <span class="fw-semibold">💰 Valor Total:</span>
                              <span class="fs-4 fw-bold text-primary">
                              R$ <span id="valor_total">0,00</span>
                              </span>
                           </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 btn-submit" id="btnSubmit">
                        <span class="btn-text">Reservar Data</span>
                        <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                        </button>
                     </form>
                     <div class="text-center mt-4">
                        <small class="text-muted">
                        <i class="bi bi-shield-check"></i>
                        Seus dados estão protegidos e seguros
                        </small>
                     </div>
                  </div>
               </div>
               <div class="text-center mt-4">
                  <p class="text-muted small mb-0">
                     © <?= date('Y') ?> SL Desenvolvedores. Todos os direitos reservados.
                  </p>
               </div>
            </div>
         </div>
      </div>
      <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
      <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <script src="js/form.js"></script>
      <script src="js/agenda.js"></script>
   </body>
</html>
