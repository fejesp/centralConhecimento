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

// Constroi a representação visual de um caminho
// O caminho deve estar na forma "a/b/c" num elemento com classe "caminho"
function montarCaminho() {
	var els, el, partes, i, j, botao, divisao, caminho
	
	var gerarOnclick = function (caminho) {
		return function () {
			window.location.href = "/pasta"+caminho
		}
	}
	
	divisao = document.createElement("span")
	divisao.className = "caminho-divisao"
	els = document.getElementsByClassName("caminho")
	for (i=0; i<els.length; i++) {
		el = els.item(i)
		if (!el.textContent)
			continue
		partes = el.textContent.split("/")
		el.innerHTML = ""
		caminho = ""
		botao = document.createElement("span")
		botao.className = "botao"
		botao.textContent = "Raiz"
		botao.onclick = gerarOnclick("")
		el.appendChild(botao)
		for (j=0; j<partes.length; j++) {
			el.appendChild(divisao.cloneNode(false))
			botao = document.createElement("span")
			botao.className = "botao"
			botao.textContent = partes[j]
			caminho += "/"+partes[j]
			botao.onclick = gerarOnclick(caminho)
			el.appendChild(botao)
		}
	}
}
