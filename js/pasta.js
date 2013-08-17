// Faz o botão de busca iniciar uma busca na pasta atual
window.addEventListener("load", function () {
	get("layout-buscar").href = "/busca?pasta="+encodeURIComponent(_caminho)
})

// Vai para um dado recurso
// É chamado pela <div class="item"> com o nome do recurso em seu primeiro filho <span>
// el é o elemento div citado
// tipo é 'pasta', 'post' ou 'form'
function ir(el, tipo) {
	var nome
	nome = el.querySelector(".item-nome").textContent
	redirecionar(tipo, _caminho, nome)
}

// Abre o menu de editar e remover
function menu(tipo, criador, evento) {
	var el, nome, botoes
	el = evento.currentTarget
	nome = el.querySelector("span").textContent
	if (_admin || _usuario == criador) {
		botoes = [["editar", "Editar item", function () {
			if (tipo == "pasta")
				redirecionar("editarPasta", _caminho, nome)
			else if (tipo == "post")
				redirecionar("editarPost", _caminho, nome)
			else if (tipo == "form")
				redirecionar("editarForm", _caminho, nome)
		}], ["remover", "Remover item", function () {
			removerItem(tipo, nome, el)
		}], ["mover", "Mover item", function () {
			abrirJanelaMover(el, tipo, nome)
		}], ["renomear", "Renomear item", function () {
			renomearItem(tipo, nome, el)
		}]]
		
		// Adiciona os botões de ativar/desativar formulário
		if (tipo == "form") {
			if (el.classList.contains("inativo"))
				botoes.unshift(["ativar", "Reativar formulário", function () {
					el.classList.remove("inativo")
					executarAjax("ativarForm", {caminho: _caminho+"/"+nome}, function () {
						el.classList.add("inativo")
						alert("Erro ao ativar formulário")
					})
				}])
			else
				botoes.unshift(["desativar", "Desativar formulário", function () {
					el.classList.add("inativo")
					executarAjax("desativarForm", {caminho: _caminho+"/"+nome}, function () {
						el.classList.remove("inativo")
						alert("Erro ao desativar formulário")
					})
				}])
		}
		Menu.abrir(evento, botoes)
	} else
		evento.preventDefault()
}

// Abre a janela para renomear um item
function renomearItem(tipo, nome, el) {
	var janela
	
	// Monta e mostra a janela
	mostrarJanela(true)
	janela = get("janela")
	janela.innerHTML = "<h2>Renomear "+assegurarHTML(tipo)+" "+assegurarHTML(nome)+"</h2>"+
		"<form id='formRenomear'>"+
		"Novo nome: <input size='40' maxlength='200' id='novoNome' required pattern='[^/]+' value='"+assegurarHTML(nome)+"'>"+
		"<input type='submit' style='display:none' id='submitRenomear'>"+
		"</form>"+
		"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Cancelar</span> "+
		"<span class='botao' onclick='get(\"submitRenomear\").click()'><img src='/imgs/enviar.png'> Renomear</span></p>"
	
	get("formRenomear").onsubmit = function (evento) {
		var novoNome, hrefAntes
		
		// Executa a ação
		novoNome = get("novoNome").value
		mostrarJanela(false)
		el.querySelector("span").textContent = novoNome
		hrefAntes = el.href
		el.href = getHref(tipo, _caminho, novoNome)
		executarAjax("renomearItem", {caminho: _caminho+"/"+nome, tipo: tipo, novoNome: novoNome}, function () {
			el.querySelector("span").textContent = nome
			el.href = hrefAntes
			alert("Erro ao renomear "+tipo)
		})
		evento.preventDefault()
	}
	get("novoNome").select()
}

// Remove um item ao clicar no botão remover do menu
function removerItem(tipo, nome, el) {
	if (tipo == "pasta" && confirm("Você tem certeza que deseja excluir a pasta "+nome+"?\nTodo o seu conteúdo será excluído permanentemente!"))
		redirecionar("excluirPasta.php", _caminho, nome)
	else if (tipo == "post" && confirm("Você tem certeza que deseja excluir o post "+nome+"?\nTodo o seu conteúdo será excluído permanentemente!"))
		redirecionar("excluirPost.php", _caminho, nome)
	else if (tipo == "form" && confirm("Você tem certeza que deseja excluir o form "+nome+"?\nTalvez seja melhor desativa-lo")) {
		el.style.display = "none"
		executarAjax("excluirForm", {caminho: _caminho+"/"+nome}, function () {
			el.style.display = ""
			alert("Erro ao excluir formulário")
		})
	}
}

setBotao("criarPasta", function () {
	redirecionar("editarPasta", _caminho, "", "criar")
})

setBotao("criarPost", function () {
	redirecionar("editarPost", _caminho, "", "criar")
})

setBotao("criarForm", function () {
	redirecionar("editarForm", _caminho, "", "criar")
})

// Abre uma janela para escolher o novo local de um item
// el é a div clicada
// tipo é uma string ("form", "post" ou "pasta")
// caminho é o local atual do item
// nome é a string com o nome do item
function abrirJanelaMover(el, tipo, nome) {
	mostrarJanela(true)
	get("janela").innerHTML = "<p>Carregando</p>"
	
	// Vai gerando as divs recursivamente
	var gerarSubArvore = function (caminhoBase, elBase, arvore) {
		var div, i, input, n = 0, label, id
		for (i in arvore) {
			n++
			div = criarTag("div")
			id = "input"+Math.random()
			div.appendChild(criarTag("label", i ? i+" " : "Diretório raiz", {"for": id}))
			input = criarTag("input", "", {type: "radio", name: "novoCaminho", id: id})
			input.value = div.dataset.caminho = (caminhoBase=="/" ? "" : caminhoBase)+"/"+i
			if (input.value == _caminho)
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
	Ajax({url: "/ajax.php?op=getArvoreInicial", dados: {caminho: _caminho}, funcao: function (arvore) {
		var janela = get("janela"), action
		action = tipo=="pasta" ? "moverPasta" : (tipo=="post" ? "moverPost" : "moverForm")
		janela.innerHTML = "<h2>Mover "+assegurarHTML(nome)+"</h2>"+
		"<p>Selecione a nova localidade:</p>"+
		"<div id='arvore'></div>"+
		"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Cancelar</span> "+
		"<span class='botao' id='btMover'><img src='/imgs/enviar.png'> Mover</span></p>"
		gerarSubArvore("", get("arvore"), arvore)
		get("btMover").onclick = function () {
			var op, dados, radios, i
			
			// Pega os valores
			op = tipo=="pasta" ? "moverPasta" : (tipo=="post" ? "moverPost" : "moverForm")
			dados = {}
			dados.caminho = (_caminho=="/" ? "" : _caminho)+"/"+nome
			radios = janela.querySelectorAll("input[type=radio]")
			for (i=0; i<radios.length; i++)
				if (radios.item(i).checked) {
					dados.novoCaminho = radios.item(i).value
					break
				}
			
			// Executa a ação no cliente e no servidor
			el.style.display = "none"
			executarAjax(op, dados, function () {
				el.style.display = ""
				alert("Erro ao mover item")
			})
			mostrarJanela(false)
		}
	}, retorno: "json"})
}

/*

Arrastar e soltar pastas

*/

window.addEventListener("load", function () {
	var els, i
	
	// Define os ouvintes de arrasto
	els = document.querySelectorAll(".item")
	for (i=0; i<els.length; i++)
		els.item(i).onmousedown = function (evento) {
			var passoArrastar, terminarArrastar, el, rotulo, arrastando, alvo
			
			arrastando = false
			el = evento.currentTarget
			alvo = null
			
			// Executa cada passo do arrasto
			passoArrastar = function (evento) {
				var i, bordas, alvoAntigo, elsPastas
				
				if (!arrastando) {
					// Cria o rótulo de arrasto
					rotulo = document.createElement("div")
					rotulo.className = "rotuloArrasto"
					rotulo.textContent = "Mover "+el.querySelector(".item-nome").textContent
					document.body.appendChild(rotulo)
					arrastando = true
				}
				rotulo.style.left = (evento.clientX-rotulo.clientWidth/2)+"px"
				rotulo.style.top = (evento.clientY-rotulo.clientHeight/2)+"px"
				
				// Verifica sobre qual pasta está
				alvoAntigo = alvo
				alvo = null
				elsPastas = document.querySelectorAll(".item-pasta")
				for (i=0; i<elsPastas.length; i++) {
					bordas = elsPastas.item(i).getBoundingClientRect()
					if (evento.clientX > bordas.left
						&& evento.clientX < bordas.right
						&& evento.clientY > bordas.top
						&& evento.clientY < bordas.bottom
						&& elsPastas.item(i) != el) {
						alvo = elsPastas.item(i)
						alvo.classList.add("alvoArrasto")
						break
					}
				}
				if (alvoAntigo && alvoAntigo != alvo)
					alvoAntigo.classList.remove("alvoArrasto")
			}
			
			// Termina o arrasto
			terminarArrastar = function () {
				var op, dados, nome, novoNome
				
				window.removeEventListener("mousemove", passoArrastar)
				window.removeEventListener("mouseup", terminarArrastar)
				
				if (arrastando)
					document.body.removeChild(rotulo)
				if (alvo) {
					// Executa a ação de mover
					
					// Pega os valores
					op = el.classList.contains("item-pasta") ? "moverPasta" : (el.classList.contains("item-post") ? "moverPost" : "moverForm")
					nome = el.querySelector(".item-nome").textContent
					novoNome = alvo.querySelector(".item-nome").textContent
					dados = {}
					dados.caminho = (_caminho=="/" ? "" : _caminho)+"/"+nome
					dados.novoCaminho = (_caminho=="/" ? "" : _caminho)+"/"+novoNome
					
					// Executa a ação no cliente e no servidor
					el.style.display = "none"
					executarAjax(op, dados, function () {
						el.style.display = ""
						alert("Erro ao mover item")
					})
					
					alvo.classList.remove("alvoArrasto")
				}
			}
			
			window.addEventListener("mousemove", passoArrastar)
			window.addEventListener("mouseup", terminarArrastar)
			evento.preventDefault()
		}
})
