(function() {
    'use strict';
    
    const inputDataVisita = document.getElementById('data_visita');
    
    if (!inputDataVisita) {
        console.error('Campo data_visita não encontrado');
        return;
    }
    
    // ========================================
    // BUSCAR DATAS DO SERVIDOR
    // ========================================
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);

    fetch('datas_agenda.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error('Erro ao carregar datas');
        }
        return response.json();
    })
    .then(data => {
        let datasAbertas = data.abertas || [];
        let datasOcupadas = data.ocupadas || [];
        
        // ========================================
        // FUNÇÃO PARA DESABILITAR DATAS NÃO DISPONÍVEIS
        // ========================================
        const desabilitarDataNaoDisponivel = function(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            const iso = `${y}-${m}-${d}`;
            
            // Desabilitar se não está na lista de abertas OU está ocupada
            return !datasAbertas.includes(iso) || datasOcupadas.includes(iso);
        };
        
        // ========================================
        // INICIALIZAR FLATPICKR
        // ========================================
        const fp = flatpickr(inputDataVisita, {
            locale: 'pt',
            dateFormat: 'd/m/Y',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: false,
            clickOpens: true,
            
            // Limites de data
            minDate: new Date(2026, 0, 1),  // 01/01/2026
            maxDate: new Date(2026, 11, 31), // 31/12/2026
            
            // Desabilitar datas não disponíveis
            disable: [desabilitarDataNaoDisponivel],
            
            // Estilizar dias no calendário
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const y = dayElem.dateObj.getFullYear();
                const m = String(dayElem.dateObj.getMonth() + 1).padStart(2, '0');
                const d = String(dayElem.dateObj.getDate()).padStart(2, '0');
                const iso = `${y}-${m}-${d}`;
                
                // Adicionar classes de estilo
                if (datasOcupadas.includes(iso)) {
                    dayElem.classList.add('dia-bloqueado');
                    dayElem.title = 'Data ocupada';
                } else if (datasAbertas.includes(iso)) {
                    dayElem.classList.add('dia-disponivel');
                    dayElem.title = 'Data disponível';
                }
            },
            
            // Validação ao selecionar data
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 0) return;
                
                const dataSelecionada = selectedDates[0];
                const y = dataSelecionada.getFullYear();
                const m = String(dataSelecionada.getMonth() + 1).padStart(2, '0');
                const d = String(dataSelecionada.getDate()).padStart(2, '0');
                const iso = `${y}-${m}-${d}`;
                
                // Verificar se data está ocupada
                if (datasOcupadas.includes(iso)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Data Indisponível',
                        text: 'Esta data já está reservada. Por favor, escolha outra data.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1e92ff'
                    });
                    instance.clear();
                    return;
                }
                
                // Verificar se data está disponível
                if (!datasAbertas.includes(iso)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Data Não Liberada',
                        text: 'Esta data não está disponível para agendamento. Selecione uma data destacada em verde.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1e92ff'
                    });
                    instance.clear();
                    return;
                }
                
                // Data válida - adicionar feedback visual
                inputDataVisita.classList.remove('is-invalid');
                inputDataVisita.classList.add('is-valid');
            },
            
            // Callback quando abre o calendário
            onOpen: function() {
                // Adicionar animação suave
                const calendar = document.querySelector('.flatpickr-calendar');
                if (calendar) {
                    calendar.style.animation = 'fadeIn 0.2s ease-in-out';
                }
            },
            
            // Configurações de acessibilidade
            ariaDateFormat: 'd/m/Y',
            
            // Desabilitar animações se usuário preferir movimento reduzido
            animate: !window.matchMedia('(prefers-reduced-motion: reduce)').matches
        });
        
        // ========================================
        // CONFIGURAR TOOLTIPS
        // ========================================
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        }
        
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Erro ao carregar datas:', error);

        const mensagem = error.name === 'AbortError'
            ? 'O servidor demorou para responder. Recarregue a página.'
            : 'Não foi possível carregar as datas disponíveis. Por favor, recarregue a página.';
        
        // Mostrar erro ao usuário
        Swal.fire({
            icon: 'error',
            title: 'Erro ao Carregar Calendário',
            text: mensagem,
            confirmButtonText: 'Recarregar',
            confirmButtonColor: '#1e92ff'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    });
    
})();
