// Cria o visual do caminho
window.addEventListener("load", montarCaminho)

// Vai para um dado recurso
// É chamado pela <div class="item"> com o nome do recurso em seu primeiro filho <span>
// el é o elemento div citado
// tipo é 'pasta', 'post' ou 'form'
function ir(el, tipo) {
	var nome
	nome = el.querySelector("span").textContent
	window.location = "/"+tipo+(caminho=="/" ? "" : caminho)+"/"+nome
}

// Abre o menu de editar e remover
function menu(tipo, criador, evento) {
	var nome = evento.currentTarget.querySelector("span").textContent
	if (admin || usuario == criador)
		Menu.abrir(evento, [["<img src='/imgs/editar.png'> Editar item", function () {
			if (tipo == "pasta")
				window.location = "/editarPasta"+(caminho=="/" ? "" : caminho)+"/"+nome
		}], ["<img src='/imgs/remover.png'> Remover item", function () {
			alert('b')
		}], ["<img src='/imgs/mover.png'> Mover item", function () {
			alert('b')
		}]])
}

// Cria uma nova pasta
setBotao("criarPasta", function () {
	window.location = "/editarPasta"+(caminho=="/" ? "" : caminho)+"/?criar"
})
