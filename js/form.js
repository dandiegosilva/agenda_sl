(function() {
    'use strict';
    
    const form = document.getElementById('formAgendamento');
    const qtdSelect = document.getElementById('quantidade_desenvolvedores');
    const valorTotalSpan = document.getElementById('valor_total');
    const btnSubmit = document.getElementById('btnSubmit');
    const valorPorDev = parseFloat(qtdSelect.dataset.valor);
    
    let isSubmitting = false;
    
    function atualizarValorTotal() {
        const quantidade = parseInt(qtdSelect.value) || 1;
        const valorTotal = quantidade * valorPorDev;
        
        valorTotalSpan.classList.add('value-changing');
        
        setTimeout(() => {
            valorTotalSpan.textContent = valorTotal.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            valorTotalSpan.classList.remove('value-changing');
        }, 150);
    }
    
    if (typeof $ !== 'undefined' && $.fn.mask) {
        $('.telefone').mask('(00) 00000-0000');
    }
    
    function validarCampo(campo) {
        const valor = campo.value.trim();
        let valido = true;
        let mensagem = '';

        campo.classList.remove('is-invalid', 'is-valid');
        
        switch(campo.id) {
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
        if (campo) {
            campo.addEventListener('blur', () => validarCampo(campo));
            
            let timeout;
            campo.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => validarCampo(campo), 500);
            });
        }
    });
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (isSubmitting) {
            return;
        }
        
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
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#1e92ff'
            });
            return;
        }
        
        form.classList.add('was-validated');
        
        if (!form.checkValidity()) {
            return;
        }

        isSubmitting = true;
        
        btnSubmit.disabled = true;
        const btnText = btnSubmit.querySelector('.btn-text');
        const spinner = btnSubmit.querySelector('.spinner-border');
        btnText.textContent = 'Processando...';
        spinner.classList.remove('d-none');
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000);

        try {
            const formData = new FormData(form);
            
            const response = await fetch('agendar.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });

            clearTimeout(timeoutId);
            
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
                                <p class="mb-2"><strong>📅 Data:</strong> ${data.data.data}</p>
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
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro ao Criar Reserva',
                    text: data.message || 'Ocorreu um erro ao processar sua reserva. Por favor, tente novamente.',
                    confirmButtonText: 'Tentar Novamente',
                    confirmButtonColor: '#dc3545'
                });
                
                isSubmitting = false;
                btnSubmit.disabled = false;
                btnText.textContent = 'Reservar Data';
                spinner.classList.add('d-none');
            }
            
        } catch (error) {
            clearTimeout(timeoutId);
            console.error('Erro:', error);

            const mensagem = error.name === 'AbortError'
                ? 'O servidor demorou para responder. Verifique sua conexão e tente novamente.'
                : 'Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.';
            
            await Swal.fire({
                icon: 'error',
                title: 'Erro de Conexão',
                text: mensagem,
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        
            isSubmitting = false;
            btnSubmit.disabled = false;
            btnText.textContent = 'Reservar Data';
            spinner.classList.add('d-none');
        }
    });
    
    qtdSelect.addEventListener('change', atualizarValorTotal);
    atualizarValorTotal();
    
    document.querySelector('.premium-card').classList.add('animate__animated', 'animate__fadeIn');
    
})();
