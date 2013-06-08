// Cria o visual do caminho
window.addEventListener("load", montarCaminho)

// Vai para um dado recurso
// É chamado pela <div class="item"> com o nome do recurso em seu primeiro filho <span>
// el é o elemento div citado
// tipo é 'pasta', 'post' ou 'form'
function ir(el, tipo) {
	var nome
	nome = el.querySelector("span").textContent
	redirecionar(tipo, caminho, nome)
}

// Abre o menu de editar e remover
function menu(tipo, criador, evento) {
	var nome = evento.currentTarget.querySelector("span").textContent
	if (admin || usuario == criador)
		Menu.abrir(evento, [["<img src='/imgs/editar.png'> Editar item", function () {
			if (tipo == "pasta")
				redirecionar("editarPasta", caminho, nome)
		}], ["<img src='/imgs/remover.png'> Remover item", function () {
			if (tipo == "pasta" && confirm("Você tem certeza que deseja excluir a pasta "+nome+"\nTodo o seu conteúdo será excluído permanentemente!"))
				redirecionar("excluirPasta.php", caminho, nome)
		}], ["<img src='/imgs/mover.png'> Mover item", function () {
			abrirJanelaMover(caminho, nome)
		}]])
}

// Cria uma nova pasta
setBotao("criarPasta", function () {
	redirecionar("editarPasta", caminho, "?criar")
})

// Abre uma janela para escolher o novo local de um item
// caminho é o local atual do item
function abrirJanelaMover(caminho, nome) {
	mostrarJanela(true)
	get("janela").innerHTML = "<p>Carregando</p>"
	
	// Vai gerando as divs recursivamente
	var gerarSubArvore = function (caminhoBase, elBase, arvore) {
		var div, i, input, n = 0, label
		for (i in arvore) {
			n++
			div = document.createElement("div")
			label = document.createElement("label")
			label.textContent = i+" "
			div.appendChild(label)
			input = document.createElement("input")
			input.type = "radio"
			input.name = "novoCaminho"
			input.id = label.htmlFor = "input"+Math.random()
			input.value = div.dataset.caminho = (caminhoBase=="/" ? "" : caminhoBase)+"/"+i
			if (input.value == caminho)
				input.checked = true
			div.appendChild(input)
			if (arvore[i]) {
				div.className = "arvore-pastaAberta"
				div.dataset.carregado = "1"
				gerarSubArvore(i ? caminhoBase+"/"+i : "", div, arvore[i])
			} else {
				div.className = "arvore-pastaFechada"
				div.dataset.carregado = "0"
			}
			
			// Coloca o ouvinte de abrir ou fechar a pasta
			div.onclick = function (evento) {
				var dX, el
				el = evento.currentTarget
				dX = evento.clientX-el.getBoundingClientRect().left
				if (dX < 20) {
					if (el.className == "arvore-pastaAberta")
						fecharSubPastas(el)
					else if (el.className == "arvore-pastaFechada")
						abrirSubPastas(el)
				}
			}
			elBase.appendChild(div)
		}
		
		return n
	}
	
	// Esconde as subpastas
	var fecharSubPastas = function (el) {
		var i, els
		els = el.childNodes
		for (i=0; i<els.length; i++)
			if (els.item(i).nodeName == "DIV")
				els.item(i).style.display = "none"
		el.className = "arvore-pastaFechada"
	}
	
	// Mostra as subpastas encondidas ou carrega do servidor
	var abrirSubPastas = function (el) {
		var i, els
		if (el.dataset.carregado == "1") {
			els = el.childNodes
			for (i=0; i<els.length; i++)
				if (els.item(i).nodeName == "DIV")
					els.item(i).style.display = ""
			el.className = "arvore-pastaAberta"
		} else {
			// Carrega do servidor
			Ajax({url: "/ajax.php?op=getSubPastas", dados: {caminho: el.dataset.caminho}, funcao: function (arvore) {
				if (gerarSubArvore(el.dataset.caminho, el, arvore))
					el.className = "arvore-pastaAberta"
				else
					el.className = "arvore-pastaFolha"
				el.dataset.carregado = "1"
			}, retorno: "json"})
		}
	}
	
	// Envia a requisição da árvore básica
	Ajax({url: "/ajax.php?op=getArvoreInicial", dados: {caminho: caminho}, funcao: function (arvore) {
		var janela = get("janela"), p, form, input
		janela.innerHTML = ""
		form = document.createElement("form")
		form.id = "form"
		form.action = "/moverPasta.php"
		form.method = "post"
		form.appendChild(criarTag("h2", "Mover "+nome))
		form.appendChild(criarTag("p", "Selecione a nova localidade:"))
		input = document.createElement("input")
		input.type = "hidden"
		input.name = "caminho"
		input.value = (caminho=="/" ? "" : caminho)+"/"+nome
		form.appendChild(input)
		gerarSubArvore("", form, arvore)
		p = criarTag("p")
		p.innerHTML = "<input type='submit' id='submit' style='display:none'>"+
			"<span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Cancelar</span> "+
			"<span class='botao' onclick='get(\"submit\").click()'><img src='/imgs/enviar.png'> Salvar</span>"
		form.appendChild(p)
		janela.appendChild(form)
	}, retorno: "json"})
}
