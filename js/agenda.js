(function () {
    'use strict';

    document.addEventListener("DOMContentLoaded", function () {

        const inputDataVisita = document.getElementById('data_visita');
        const selectDevs = document.getElementById("quantidade_desenvolvedores");
        const valorSpan = document.getElementById("valor_total");

        if (!inputDataVisita || !selectDevs || !valorSpan) {
            console.error("Elementos necessários não encontrados.");
            return;
        }

        const VALOR_POR_DESENVOLVEDOR = parseFloat(selectDevs.dataset.valor || 0);

        // ========================================
        // BUSCAR DATAS DO SERVIDOR
        // ========================================

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);

        fetch('datas_agenda.php', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
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

                const datasAbertas = data.abertas || [];
                const datasOcupadas = data.ocupadas || [];

                // ========================================
                // DESABILITAR DATAS NÃO DISPONÍVEIS
                // ========================================

                function desabilitarDataNaoDisponivel(date) {

                    const y = date.getFullYear();
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    const d = String(date.getDate()).padStart(2, '0');
                    const iso = `${y}-${m}-${d}`;

                    return !datasAbertas.includes(iso) || datasOcupadas.includes(iso);
                }

                // ========================================
                // INICIALIZAR FLATPICKR
                // ========================================

                const fp = flatpickr(inputDataVisita, {
                    locale: 'pt',
                    dateFormat: 'd/m/Y',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    mode: "multiple",
                    conjunction: ", ",
                    minDate: "today",
                    disable: [desabilitarDataNaoDisponivel],

                    onDayCreate: function (dObj, dStr, fp, dayElem) {

                        const y = dayElem.dateObj.getFullYear();
                        const m = String(dayElem.dateObj.getMonth() + 1).padStart(2, '0');
                        const d = String(dayElem.dateObj.getDate()).padStart(2, '0');
                        const iso = `${y}-${m}-${d}`;

                        if (datasOcupadas.includes(iso)) {
                            dayElem.classList.add('dia-bloqueado');
                            dayElem.title = 'Data ocupada';
                        } else if (datasAbertas.includes(iso)) {
                            dayElem.classList.add('dia-disponivel');
                            dayElem.title = 'Data disponível';
                        }
                    },

                    onChange: function (selectedDates, dateStr, instance) {

                        if (selectedDates.length === 0) {
                            valorSpan.textContent = "0,00";
                            inputDataVisita.classList.remove('is-valid');
                            return;
                        }

                        // valida se alguma data ficou inválida
                        for (let dataSelecionada of selectedDates) {

                            const y = dataSelecionada.getFullYear();
                            const m = String(dataSelecionada.getMonth() + 1).padStart(2, '0');
                            const d = String(dataSelecionada.getDate()).padStart(2, '0');
                            const iso = `${y}-${m}-${d}`;

                            if (datasOcupadas.includes(iso) || !datasAbertas.includes(iso)) {

                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Data inválida',
                                    text: 'Uma das datas selecionadas não está disponível.',
                                    confirmButtonColor: '#1e92ff'
                                });

                                instance.clear();
                                valorSpan.textContent = "0,00";
                                return;
                            }
                        }

                        inputDataVisita.classList.remove('is-invalid');
                        inputDataVisita.classList.add('is-valid');

                        calcularTotal();
                    }
                });

                // ========================================
                // FUNÇÃO DE CÁLCULO OFICIAL
                // ========================================

                function calcularTotal() {

                    const qtdDevs = parseInt(selectDevs.value || 0);
                    const quantidadeDias = fp.selectedDates.length;

                    if (!qtdDevs || quantidadeDias === 0) {
                        valorSpan.textContent = "0,00";
                        return;
                    }

                    const total = VALOR_POR_DESENVOLVEDOR * qtdDevs * quantidadeDias;

                    valorSpan.textContent = total.toLocaleString("pt-BR", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                // Atualiza quando muda desenvolvedor
                selectDevs.addEventListener("change", calcularTotal);

            })
            .catch(error => {

                clearTimeout(timeoutId);
                console.error('Erro ao carregar datas:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao Carregar Calendário',
                    text: 'Não foi possível carregar as datas disponíveis.',
                    confirmButtonText: 'Recarregar',
                    confirmButtonColor: '#1e92ff'
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
            });

    });

})();
