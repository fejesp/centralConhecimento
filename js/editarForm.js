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
	acoes.innerHTML = "<span class='botao' onclick='moverCampoAcima(this)'><img src='/imgs/praCima.png'> Mover para cima</span> "+
	"<span class='botao' onclick='moverCampoAbaixo(this)'><img src='/imgs/praBaixo.png'> Mover para baixo</span> "+
	"<span class='botao' onclick='removerCampo(this)'><img src='/imgs/remover.png'> Remover</span>"
	
	// Monta os elementos de cada tipo de campo
	titulo = criarTag("strong")
	div.appendChild(criarTag("p", titulo))
	div.appendChild(p = criarTag("p"))
	id = String(Math.random())
	p.innerHTML = "Título da questão: <input size='30' name='nomes["+id+"]' required> "+
	"<input type='checkbox' id='campo"+id+"' name='obrigatorio["+id+"]'> <label for='campo"+id+"'>Preenchimento obrigatório</label>"+
	"<input type='hidden' name='campos[]' value='"+id+":"+tipo+"'>"
	div.appendChild(p = criarTag("p"))
	p.innerHTML = "Texto de ajuda: <input size='40' name='ajudas["+id+"]'>"
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

// Remove um campo da lista
function removerCampo(el) {
	var divCampo = getDivCampo(el)
	divCampo.parentNode.removeChild(divCampo)
}

// Move um campo para cima na lista
function moverCampoAcima(el) {
	var divCampo, pos, antes
	divCampo = getDivCampo(el)
	antes = divCampo.previousSibling
	if (antes)
		divCampo.parentNode.insertBefore(divCampo, antes)
}

// Move um campo para baixo na lista
function moverCampoAbaixo(el) {
	var divCampo, pos, depois
	divCampo = getDivCampo(el)
	depois = divCampo.nextSibling
	if (depois)
		divCampo.parentNode.insertBefore(divCampo, depois.nextSibling)
}

// Retorna a div com a classe "campo" que está acima do elemento dado
function getDivCampo(el) {
	while (el != null)
		if (el.className == "campo")
			return el
		else
			el = el.parentNode
	return null
}

// Carrega a previsão do resultado do conteúdo em HTML
function visualizar() {
	var str = get("descricao").value
	Ajax({url: "/ajax.php?op=preverHTML", dados: {str: str}, funcao: function (html) {
		var div = get("divPrevisao")
		if (div)
			div.innerHTML = html
	}, retorno: "json", metodo: "post"})
	mostrarJanela(true)
	get("janela").innerHTML = "<div id='divPrevisao' class='subConteudo'><em>Carregando...</em></div>"+
	"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span></p>"
}
