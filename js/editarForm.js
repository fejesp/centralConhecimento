setBotao("voltar", function () {
	redirecionar("pasta", _caminho)
})

setBotao("salvar", function () {
	get("submit").click()
})

setBotao("adicionarCampo", function (evento) {
	Menu.abrir(evento, [["Texto", function () {
		adicionarCampo("input")
	}], ["Texto longo", function () {
		adicionarCampo("textarea")
	}], ["Múltipla escolha", function () {
		adicionarCampo("radio")
	}], ["Checkboxes", function () {
		adicionarCampo("checkbox")
	}]])
})

// Adiciona um novo campo na lista
function adicionarCampo(tipo) {
	var div, acoes, p, id, titulo
	
	// Monta os elementos básicos
	div = criarTag("div.campo")
	div.appendChild(acoes = criarTag("div.campo-acoes"))
	acoes.innerHTML = "<span class='botao'><img src='/imgs/praCima.png' onclick='moverAcima()'> Mover para cima</span> "+
	"<span class='botao'><img src='/imgs/praBaixo.png' onclick='moverAbaixo()'> Mover para baixo</span> "+
	"<span class='botao'><img src='/imgs/remover.png' onclick='remover()'> Remover</span>"
	
	// Monta os elementos de cada tipo de campo
	titulo = criarTag("strong")
	div.appendChild(criarTag("p", titulo))
	div.appendChild(p = criarTag("p"))
	id = String(Math.random())
	p.innerHTML = "Título da questão: <input size='40' name='nomes["+id+"]' required> "+
	"<input type='checkbox' id='campo"+id+"' name='obrigatorio["+id+"]'> <label for='campo"+id+"'>Preenchimento obrigatório</label>"+
	"<input type='hidden' name='campos[]' value='"+id+":"+tipo+"'>"
	if (tipo == "input")
		titulo.textContent = "Texto"
	else if (tipo == "textarea")
		titulo.textContent = "Texto longo"
	else if (tipo == "radio") {
		titulo.textContent = "Múltipla escolha"
		div.appendChild(p = criarTag("p"))
		p.innerHTML = "Digite as opções, uma por linha:<br><textarea required name='valores["+id+"]'></textarea>"
	} else if (tipo == "checkbox") {
		titulo.textContent = "Checkboxes"
		div.appendChild(p = criarTag("p"))
		p.innerHTML = "Digite as opções, uma por linha:<br><textarea required name='valores["+id+"]'></textarea>"
	}
	
	get("campos").appendChild(div)
}
