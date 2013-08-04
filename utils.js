/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Reúne várias funções úteis em JavaScript

// Apelido para document.getElementById
function get(id) {
	return document.getElementById(id)
}

// Define o ouvinte de click de um botão com o dado id
// Se a página não carregou ainda, espera o DOM ficar pronto
// Se o botão não existir, não faz nada (nem lança exceção)
var setBotao = (function () {
	var pronto = false, fila = []
	window.addEventListener("load", function () {
		var i
		for (i=0; i<fila.length; i++)
			if (get(fila[i][0]))
				get(fila[i][0]).onclick = fila[i][1]
		pronto = true
		fila = null
	})
	return function (id, onclick) {
		if (!pronto)
			fila.push([id, onclick])
		else if (get(id))
			get(id).onclick = onclick
	}
	
})()

// Gerencia o funcionamento dos menus
// Menu.abrir(evento, botoes) recebe o evento do mouse (a partir do qual o menu será montado) e
// botoes, que é uma array onde cada elemento é uma array na forma [html, onclick]
// Menu.fechar() fecha imediatamente o menu aberto
var Menu = (function () {
	var divMenu = null
	window.addEventListener("click", function () {
		Menu.fechar()
	})
	
	return {abrir: function (evento, botoes) {
		var i, subdiv, fechar
		
		// Monta a div
		Menu.fechar()
		divMenu = document.createElement("div")
		divMenu.className = "menu"
		document.body.appendChild(divMenu)
		
		// Insere os botões
		for (i=0; i<botoes.length; i++) {
			subdiv = document.createElement("div")
			subdiv.innerHTML = botoes[i][0]
			subdiv.onclick = botoes[i][1]
			divMenu.appendChild(subdiv)
		}
		
		// Posiciona
		if (evento.pageX+divMenu.offsetWidth > document.documentElement.clientWidth)
			divMenu.style.left = (evento.pageX-divMenu.offsetWidth)+"px"
		else
			divMenu.style.left = evento.pageX+"px"
		if (evento.pageY+divMenu.offsetHeight > document.documentElement.clientHeight)
			divMenu.style.top = (evento.pageY-divMenu.offsetHeight)+"px"
		else
			divMenu.style.top = evento.pageY+"px"
		
		evento.stopPropagation()
		evento.preventDefault()
	}, fechar: function () {
		if (divMenu) {
			document.body.removeChild(divMenu)
			divMenu = null
		}
	}}
})()

// Redireciona para uma outra página
// Exemplo: redirecionar("pasta", "/um/dois", "tres", "criar") => "/pasta/um/dois/tres?criar"
// redirecionar("index") => "/index"
// redirecionar("script.php", "/um", "dois", "criar") => "/script.php?caminho=/um/dois&criar"
function redirecionar(tipo, caminho, nome, query) {
	caminho = caminho || "/"
	nome = nome || ""
	caminho = (caminho=="/" ? "" : caminho)+"/"+nome
	if (tipo.substr(-4) == ".php")
		window.location = "/"+tipo+"?caminho="+encodeURIComponent(caminho)+(query ? "&"+query : "")
	else
		window.location = "/"+tipo+escaparUrl(caminho)+(query ? "?"+query : "")
}

// Mostra ou esconde a janela
var mostrarJanela = (function () {
	var aberta = false
	window.addEventListener("load", function () {
		get("fundoJanela").onclick = function () {
			mostrarJanela(false)
		}
	})
	return function (estado) {
		aberta = Boolean(estado)
		get("janela").style.display = get("fundoJanela").style.display = aberta ? "" : "none"
	}
})()

// Mostra a janela com uma mensagem de "Carregando"
// Retorna um div dentro da janela onde o conteúdo será colocado depois de carregado
function abrirJanelaCarregando() {
	var idDiv = "div"+String(Math.random())
	mostrarJanela(true)
	get("janela").innerHTML = "<div id='"+idDiv+"'><em>Carregando...</em></div>"+
	"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span></p>"
	return get(idDiv)
}

// Cria um novo elemento com a tag, conteúdo e atributos desejados
// tag é uma string com o nome da tag. Opcionalmente, pode conter o nome da classe, ex: "span.botao"
// conteudo é uma string, um nó ou uma array de string/nós
// atributos é um objeto com os atributos do elemento, ex: {onclick: "alert('oi')"}
function criarTag(tag, conteudo, atributos) {
	var el, pos, i
	
	// Cria o elemento
	pos = tag.indexOf(".")
	if (pos == -1)
		el = document.createElement(tag)
	else {
		el = document.createElement(tag.substr(0, pos))
		el.className = tag.substr(pos+1)
	}
	
	// Coloca o conteúdo
	if (Array.isArray(conteudo))
		for (i=0; i<conteudo.length; i++) {
			if (typeof conteudo[i] == "object")
				el.appendChild(conteudo[i])
			else
				el.appendChild(document.createTextNode(conteudo[i]))
		}
	else if (typeof conteudo == "object")
		el.appendChild(conteudo)
	else if (conteudo !== "" && conteudo !== undefined)
		el.appendChild(document.createTextNode(conteudo))
	
	// Coloca os parâmetros
	if (atributos)
		for (i in atributos)
			el.setAttribute(i, atributos[i])
	
	return el
}

// Transforma em HTML seguro
function assegurarHTML(str) {
	return str.replace(/</g, "&lt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;")
}

// Se comporta como o round do PHP
function round(num, casas) {
	var passo
	casas = casas || 0
	passo = Math.pow(10, casas)
	return Math.round(num*passo)/passo
}

// Transforma de número de kiB (int) para string
function kiB2str(num) {
	if (num < 1000)
		return round(num)+" kiB"
	if (num < 10240)
		return round(num/1024, 2)+" MiB"
	if (num < 102400)
		return round(num/1024, 1)+" MiB"
	if (num < 1024000)
		return round(num/1024)+" MiB"
	return round(num/(1024*1024), 2)+" GiB"
}

// Trata uma url, escapando os caracteres necessários
function escaparUrl(url) {
	url = encodeURIComponent(url)
	return url.replace(/%2F/g, "/").replace(/%/g, "%25")
}
