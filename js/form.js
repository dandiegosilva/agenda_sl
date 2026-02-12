(function () {
    'use strict';

    document.addEventListener("DOMContentLoaded", function () {

        const form = document.getElementById('formAgendamento');
        const btnSubmit = document.getElementById('btnSubmit');

        if (!form) return;

        let isSubmitting = false;

        // ================================
        // MÁSCARA TELEFONE
        // ================================
        if (typeof $ !== 'undefined' && $.fn.mask) {
            $('.telefone').mask('(00) 00000-0000');
        }

        // ================================
        // VALIDAÇÃO CAMPOS
        // ================================

        function validarCampo(campo) {
            const valor = campo.value.trim();
            let valido = true;
            let mensagem = '';

            campo.classList.remove('is-invalid', 'is-valid');

            switch (campo.id) {
                case 'nome_leiloeiro':
                    valido = valor.length >= 3 && valor.length <= 100;
                    mensagem = 'Nome deve ter entre 3 e 100 caracteres';
                    break;

                case 'telefone':
                    const telefoneNumeros = valor.replace(/\D/g, '');
                    valido = telefoneNumeros.length === 10 || telefoneNumeros.length === 11;
                    mensagem = 'Telefone inválido. Use: (00) 00000-0000';
                    break;

                case 'cidade':
                    valido = valor.length >= 2 && valor.length <= 100;
                    mensagem = 'Cidade deve ter entre 2 e 100 caracteres';
                    break;

                case 'data_visita':
                    valido = valor !== '';
                    mensagem = 'Selecione uma data disponível';
                    break;
            }

            if (campo.value.length > 0) {
                if (valido) {
                    campo.classList.add('is-valid');
                } else {
                    campo.classList.add('is-invalid');
                    const feedback = campo.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = mensagem;
                    }
                }
            }

            return valido;
        }

        const camposValidacao = ['nome_leiloeiro', 'telefone', 'cidade', 'data_visita'];

        camposValidacao.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (!campo) return;

            campo.addEventListener('blur', () => validarCampo(campo));

            let timeout;
            campo.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => validarCampo(campo), 400);
            });
        });

        // ================================
        // SUBMIT
        // ================================

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (isSubmitting) return;

            let formularioValido = true;

            camposValidacao.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo && !validarCampo(campo)) {
                    formularioValido = false;
                }
            });

            if (!formularioValido) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Por favor, corrija os campos destacados antes de continuar.',
                    confirmButtonColor: '#1e92ff'
                });
                return;
            }

            if (!form.checkValidity()) return;

            isSubmitting = true;

            btnSubmit.disabled = true;
            const btnText = btnSubmit.querySelector('.btn-text');
            const spinner = btnSubmit.querySelector('.spinner-border');

            btnText.textContent = 'Processando...';
            spinner.classList.remove('d-none');

            try {
                const formData = new FormData(form);

                const response = await fetch('agendar.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '<strong>Reserva Criada com Sucesso!</strong>',
                    html: `
                        <div class="text-start mt-3">
                            <p class="mb-3">Sua reserva foi confirmada com os seguintes dados:</p>
                            <div class="alert alert-light border">
                                <p class="mb-2"><strong>📋 Nome:</strong> ${data.data.nome}</p>
                                ${data.data.datas.length === 1 
                                    ? `<p><strong>📅 Data:</strong> ${data.data.datas[0]}</p>`
                                    : `<p><strong>📅 Datas:</strong> ${data.data.datas.join(', ')}</p>`
                                }
                                <p class="mb-2"><strong>👨‍💻 Desenvolvedores:</strong> ${data.data.quantidade}</p>
                                <p class="mb-0"><strong>💰 Valor:</strong> R$ ${data.data.valor_total}</p>
                            </div>
                            <p class="text-muted small mt-3">
                                <i>⚠️ Esta reserva expira em 24 horas. Efetue o pagamento para confirmar.</i>
                            </p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-whatsapp"></i> Enviar Mensagem no WhatsApp',
                    confirmButtonColor: '#25D366',
                    cancelButtonText: 'Fechar',
                    cancelButtonColor: '#6c757d',
                    customClass: {
                        popup: 'swal-wide',
                        confirmButton: 'btn-whatsapp',
                        title: 'swal-title-custom'
                    },
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed && data.data.whatsapp_link) {
                        window.open(data.data.whatsapp_link, '_blank');
                        
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000);
                    } else {
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 1500);
                    }
                });
                
            } else {
                    throw new Error(data.message || 'Erro ao criar reserva.');
                }

            } catch (error) {

                await Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });

                isSubmitting = false;
                btnSubmit.disabled = false;
                btnText.textContent = 'Reservar Data';
                spinner.classList.add('d-none');
            }
        });

        // animação
        document.querySelector('.premium-card')
            ?.classList.add('animate__animated', 'animate__fadeIn');

    });

})();
