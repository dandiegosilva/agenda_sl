document.addEventListener("DOMContentLoaded", function () {

		/* =====================================================
   CONFIG GLOBAL
===================================================== */

		const INICIO_AGENDA=window.APP?.inicioAgenda || "2026-02-12";

		function toISO(dateObj) {
			const y=dateObj.getFullYear();
			const m=String(dateObj.getMonth() + 1).padStart(2, "0");
			const d=String(dateObj.getDate()).padStart(2, "0");
			return `${y}-${m}-${d}`;
		}

		/* =====================================================
   TOAST PHP (CONFIG SALVA / ERROS)
===================================================== */

		window.addEventListener("load", function () {
				if (typeof Swal !== "undefined" && window.APP?.toast) {
					Swal.fire({
						toast: true,
						position: window.innerWidth < 768 ? "top" : "top-end",
						icon: "success",
						title: window.APP.toast,
						showConfirmButton: false,
						timer: 3000
					});
			}
			if (typeof Swal !== "undefined" && window.APP?.toastErro) {
				Swal.fire({
					toast: true,
					position: window.innerWidth < 768 ? "top" : "top-end",
					icon: "error",
					title: window.APP.toastErro,
					showConfirmButton: false,
					timer: 4000
				});
			}
		});

	/* =====================================================
   FILTRO — BLOQUEAR VAZIO + VALIDAR LIMPAR
===================================================== */

	const formFiltro=document.querySelector("form.row.g-2.mb-3");

	if (formFiltro) {

		formFiltro.addEventListener("submit", function(e) {

				const cidade=formFiltro.querySelector("input[name='cidade']").value.trim();
				const data=formFiltro.querySelector("input[name='data']").value.trim();

				const botao=document.activeElement?.name;

				// FILTRAR SEM NADA
				if (botao==="filtrar" && !cidade && !data) {
					e.preventDefault();

					Swal.fire({
						toast: true,
						position: window.innerWidth < 768 ? "top" : "top-end",
						icon: "warning",
						title: "Preencha Cidade ou Data para filtrar",
						showConfirmButton: false,
						timer: 3000
					});

				return;
			}

			// LIMPAR SEM TER FILTRO
			if (botao==="limpar" && !cidade && !data) {
				e.preventDefault();

				Swal.fire({
					toast: true,
					position: window.innerWidth < 768 ? "top" : "top-end",
					icon: "info",
					title: "Nenhum filtro para limpar",
					showConfirmButton: false,
					timer: 2500
				});
		}
	});
}

const valorInput = document.getElementById('valor_por_dev');

function formatarValorInput(input) {
    let valor = input.value.replace(/\D/g, ''); // remove tudo que não é número

    if (!valor) valor = '0';

    // transforma em float (duas casas decimais)
    valor = (parseInt(valor) / 100).toFixed(2);

    // exibe no formato BR: 1.750,00
    input.value = valor.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

formatarValorInput(valorInput);
valorInput.addEventListener('input', () => formatarValorInput(valorInput));

/* =====================================================
   CONFIRMAÇÕES BONITAS (CONFIRMAR / CANCELAR / EXCLUIR)
===================================================== */

function confirmacaoForm(selector, titulo, texto, cor="#3085d6") {
	document.querySelectorAll(selector).forEach(form=> {
			form.addEventListener("submit", function(e) {
					e.preventDefault();

					Swal.fire({
						title: titulo,
						text: texto,
						icon: "warning",
						showCancelButton: true,
						confirmButtonColor: cor,
						cancelButtonColor: "#6c757d",
						confirmButtonText: "Sim",
						cancelButtonText: "Cancelar"

					}).then(result=> {
						if (result.isConfirmed) form.submit();
					});
			});
	});
}

confirmacaoForm(".form-confirmar", "Confirmar pagamento?", "Você pode desfazer essa ação", "#22c55e");
confirmacaoForm(".form-cancelar", "Cancelar visita?", "Tem certeza?");
confirmacaoForm(".form-excluir", "Excluir definitivamente?", "Isso apagará o registro!", "#ef4444");

/* =====================================================
   REMARCAR BONITO
===================================================== */

document.querySelectorAll(".btn-remarcar").forEach(btn=> {
		btn.addEventListener("click", function () {

				const id=this.dataset.id;

				Swal.fire({

					title: "Escolher nova data",
					input: "date",
					inputAttributes: {
						min: new Date().toISOString().split("T")[0]
					}

					,
					showCancelButton: true,
					confirmButtonText: "Remarcar",
					cancelButtonText: "Cancelar"

				}).then(result=> {

					if ( !result.isConfirmed || !result.value) return;

					const form=document.getElementById("form-remarcar-" + id);
					form.querySelector("input[name='nova_data']").value=result.value;
					form.submit();
				});
		});
});

/* =====================================================
   FLATPICKR ADMIN — VERDE / VERMELHO
===================================================== */

const inputDatas=document.getElementById("datas_agenda");

if (inputDatas) {

	const controller = new AbortController();
	const timeoutId = setTimeout(() => controller.abort(), 10000);

	fetch(window.BASE_URL + "datas_agenda.php", { signal: controller.signal })
		.then(res => { clearTimeout(timeoutId); return res.json(); })
		.then(data => {

			const datasAbertas=data.abertas || [];
			const datasOcupadas=data.ocupadas || [];

			flatpickr(inputDatas, {
				locale: "pt",
				dateFormat: "d/m/Y",
				mode: "multiple",
				minDate: "today",

				disable: [ function(date) {
					const iso=toISO(date);

					if (iso < INICIO_AGENDA) return true;
					if (datasOcupadas.includes(iso)) return true;
					if (datasAbertas.includes(iso)) return true;

					return false;
				}

				],

				onDayCreate: function(dObj, dStr, fp, dayElem) {

					const iso=toISO(dayElem.dateObj);

					// vermelho
					if (iso < INICIO_AGENDA || datasOcupadas.includes(iso) || datasAbertas.includes(iso)) {
						dayElem.style.background="#ff4d4f";
						dayElem.style.color="#fff";
						return;
					}

					// verde
					dayElem.style.background="#22c55e";
					dayElem.style.color="#fff";
				}
			});

	}).catch(err => {
		if (err.name !== 'AbortError') {
			Swal.fire({
				icon: "error",
				title: "Erro ao carregar agenda"
			});
		}
	});
}

});
