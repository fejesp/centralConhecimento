// Envia o formulário
setBotao("salvar", function () {
	get("submit").click()
})

// Controla a exibição da lista de usuários
function atualizarLista() {
	get("lista").style.display = get("seleto").checked ? "" : "none"
}
window.addEventListener("load", function () {
	get("publico").onchange = atualizarLista
	get("geral").onchange = atualizarLista
	get("seleto").onchange = atualizarLista
	atualizarLista()
})

// Volta para a pasta
setBotao("voltar", function () {
	window.location = "/pasta"+caminho
})
