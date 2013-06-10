// Guarda o elemento da lista sendo editado
var _editando = null

// Guarda o número de novos anexos
var _numNovos = 0

// Guarda o tamanho total dos novos anexos
var _tamanhoNovos = 0

// Envia o formulário
setBotao("salvar", function () {
	get("submit").click()
})

// Controla a exibição da lista de usuários
function atualizarLista() {
	get("lista").style.display = get("seleto").checked ? "" : "none"
}
function atualizarLista2() {
	get("anexo-lista").style.display = get("anexo-seleto").checked ? "" : "none"
}
window.addEventListener("load", function () {
	get("publico").onchange = atualizarLista
	get("geral").onchange = atualizarLista
	get("seleto").onchange = atualizarLista
	atualizarLista()
})

// Volta para a pasta
setBotao("voltar", function () {
	window.location = "/pasta"+_caminho
})

setBotao("adicionarAnexo", function () {
	// Limita o número de anexos por envio
	if (_numNovos >= _maxNum)
		alert("O sistema só permite o upload de no máximo "+_maxNum+" anexos por vez\nSalve e edite o post para enviar o restante")
	else
		montarJanelaAnexo()
})

// Monta a janela de opções de anexo
// Se visibilidade não for enviado, cria a janela de novo anexo
function montarJanelaAnexo(visibilidade, selecionados) {
	var janela = get("janela"), checks = "", i
	mostrarJanela(true)
	
	// Monta as checkbox com os nomes do usuários
	for (i=0; i<_usuarios.length; i++)
		checks += "<input type='checkbox' id='anexo-usuario"+_usuarios[i].id+"'"+(_usuarios[i].id==_criador ? " checked disabled" : "")+" data-id='"+_usuarios[i].id+"'> "+
		"<label for='anexo-usuario"+_usuarios[i].id+"'>"+assegurarHTML(_usuarios[i].nome)+"</label><br>"
	
	janela.innerHTML = (visibilidade ? "<h2>Editando anexo</h2>" : "<h2>Novo anexo</h2>")+
	(visibilidade ? "" : "<p>Arquivo: <input type='file' id='anexo-file'> (máx "+KiB2str(_maxCada)+")</p>")+
	"<p class='rotuloEsquerdo'>Visibilidade: </p>"+
	"<p class='opcoesDireita'>"+
	"<input type='radio' name='anexo-visibilidade' id='anexo-publico' onchange='atualizarLista2()' checked> <label for='anexo-publico'>para qualquer um</label><br>"+
	"<input type='radio' name='anexo-visibilidade' id='anexo-geral' onchange='atualizarLista2()'> <label for='anexo-geral'>para qualquer usuário logado</label><br>"+
	"<input type='radio' name='anexo-visibilidade' id='anexo-seleto' onchange='atualizarLista2()'> <label for='anexo-seleto'>para um grupo definido de usuários</label><br>"+
	"</p>"+
	"<div class='opcoesDireita' id='anexo-lista' style='display:none'>"+
	"<p>Usuários permitidos:</p>"+
	checks+
	"</div>"+
	"<div class='clear'></div>"+
	"<span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span> "+
	"<span class='botao' onclick='"+(visibilidade ? "salvarEdicaoAnexo" : "adicionarAnexo")+"()'><img src='/imgs/enviar.png'> Salvar</span>"
	
	if (visibilidade) {
		get("anexo-"+visibilidade).checked = true
		atualizarLista2()
		if (selecionados)
			for (i=0; i<selecionados.length; i++)
				get("anexo-usuario"+selecionados[i]).checked = true
	} else
		get("anexo-file").click()
}

// Mostra o menu de opções para os anexos
function menu(evento) {
	var el = evento.currentTarget
	var botoes = [["<img src='/imgs/editar.png'> Editar anexo", function () {
		editarAnexo(el)
	}], ["<img src='/imgs/remover.png'> Remover anexo", function () {
		removerAnexo(el)
	}]]
	if (el.dataset.novo == "0")
		botoes.push(["<img src='/imgs/download.png'> Baixar anexo", function () {
			baixarAnexo(el)
		}])
	Menu.abrir(evento, botoes)
}

// Permite baixa o anexo
// Recebe a <div> do anexo clicado
function baixarAnexo(el) {
	var src = "/anexo"+_caminhoPost+"/"+el.querySelector("span.item-nome").textContent
	document.body.appendChild(criarTag("iframe", "", {src: src, style: "display:none"}))
}

// Remove um anexo
function removerAnexo(el) {
	if (el.dataset.novo == "0") {
		// Marca que foi removido
		get("form").appendChild(criarTag("input", "", {type: "hidden", name: "removidos["+el.dataset.id+"]", value: "1"}))
		if (_quotaLivre !== null)
			_quotaLivre += Number(el.dataset.tamanho)
	} else {
		_numNovos--
		_tamanhoNovos -= Number(el.dataset.tamanho)
	}
	
	// Remove da lista
	el.parentNode.removeChild(el)
}

// Coloca o novo anexo na lista
function adicionarAnexo() {
	var file, visibilidade, selecionados, els, i, anexos, id, info, div, tamanho
	
	// Valida as restrições de número e tamanho
	file = get("anexo-file")
	if (!file.files.length)
		return alert("Nenhum arquivo selecionado")
	tamanho = file.files[0].size/1024
	if (tamanho > _maxCada)
		return alert("O arquivo selecionado é muito grande\nO tamanho máximo permitido por arquivo é de "+KiB2str(_maxCada)+"\nSelecione outro ou contate o administrador")
	if (_tamanhoNovos+tamanho > _maxTotal-1024)
		return alert("O sistema só permite enviar "+KiB2str(_maxTotal)+" de anexos por vez\nSalve e edite o post para enviar o restante")
	if (_quotaLivre !== null && _tamanhoNovos+tamanho > _quotaLivre)
		return alert("Você passou da sua cota de uso de espaço de "+KiB2str(_quota)+"\nContate o administrador para poder usar mais espaço")
	
	// Pega os dados da janela
	visibilidade = get("anexo-publico").checked ? "publico" : (get("anexo-geral").checked ? "geral" : "seleto")
	if (visibilidade == "seleto") {
		selecionados = []
		els = get("anexo-lista").querySelectorAll("input")
		for (i=0; i<els.length; i++)
			if (els.item(i).checked && !els.item(i).disabled)
				selecionados.push(Number(els.item(i).dataset.id))
		info = "seleto"+JSON.stringify(selecionados)
	} else
		info = visibilidade
	
	// Coloca o item visualmente na lista
	anexos = get("anexos")
	div = criarTag("div", "", {class: "item item-anexo", oncontextmenu: "menu(event)", "data-novo": "1", "data-tamanho": tamanho})
	div.innerHTML = "<span class='item-nome'>"+file.files[0].name+"</span>"+
	"<span class='item-descricao'>"+KiB2str(tamanho)+
	"</span><span class='item-descricao'>"+visibilidade2str(visibilidade, selecionados)+"</span>"
	anexos.appendChild(div)
	
	// Coloca os dados no formulário
	id = String(Math.random())
	file.style.display = "none"
	file.name = "arquivos["+id+"]"
	file.id = ""
	div.appendChild(file)
	div.appendChild(criarTag("input", "", {type: "hidden", name: "infos["+id+"]", value: info}))
	mostrarJanela(false)
	get("janela").innerHTML = ""
	_numNovos++
	_tamanhoNovos += tamanho
}

// Retorna uma representação em string da visibilidade de um anexo
function visibilidade2str(visibilidade, selecionados) {
	var nomes, i
	if (visibilidade == "publico")
		return "Visível publicamente"
	if (visibilidade == "geral")
		return "Visível para todos os usuários logados"
	if (!selecionados.length)
		return "Visível somente para o criador"
	nomes = []
	for (i=0; i<_usuarios.length; i++)
		if (selecionados.indexOf(_usuarios[i].id) != -1)
			nomes.push(_usuarios[i].nome)
	return "Visível para somente para "+nomes.join(", ")+" e o criador"
}

// Abre a janela para editar um anexo
// Recebe o elemento <div> dele na lista
function editarAnexo(el) {
	var visibilidade, selecionados
	
	// Carrega os dados atuais
	visibilidade = el.dataset.novo == "1" ? el.querySelector("input[type=hidden]").value : el.dataset.visibilidade
	if (visibilidade.substr(0, 6) == "seleto") {
		selecionados = JSON.parse(visibilidade.substr(6))
		visibilidade = "seleto"
	}
	
	// Abre a janela
	montarJanelaAnexo(visibilidade, selecionados)
	_editando = el
}

// Atualiza o anexo da lista
function salvarEdicaoAnexo() {
	var visibilidade, selecionados, els, i, info, input
	
	// Pega os dados da janela
	visibilidade = get("anexo-publico").checked ? "publico" : (get("anexo-geral").checked ? "geral" : "seleto")
	if (visibilidade == "seleto") {
		selecionados = []
		els = get("anexo-lista").querySelectorAll("input")
		for (i=0; i<els.length; i++)
			if (els.item(i).checked && !els.item(i).disabled)
				selecionados.push(Number(els.item(i).dataset.id))
		info = "seleto"+JSON.stringify(selecionados)
	} else
		info = visibilidade
	
	// Atualiza o item visualmente na lista
	_editando.querySelectorAll(".item-descricao")[1].textContent = visibilidade2str(visibilidade, selecionados)
	
	// Coloca os dados no formulário
	input = _editando.querySelector("input[type=hidden]")
	if (_editando.dataset.novo == "1")
		input.value = info
	else {
		_editando.dataset.visibilidade = info
		if (input)
			input.value = info
		else
			_editando.appendChild(criarTag("input", "", {type: "hidden", name: "mudancas["+_editando.dataset.id+"]", value: info}))
	}
	
	mostrarJanela(false)
	get("janela").innerHTML = ""
	_editando = null
}