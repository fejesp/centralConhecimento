// Guarda o número de novos anexos
var _numNovos = 0

// Guarda o tamanho total dos novos anexos
var _tamanhoNovos = 0

// Cria o visual do caminho
window.addEventListener("load", montarCaminho)

setBotao("editar", function () {
	redirecionar("editarForm", _caminho)
})

setBotao("remover", function () {
	if (confirm("Você tem certeza que deseja excluir esse formulário?\nTalvez seja melhor desativa-lo"))
		Ajax({url: "/ajax.php?op=excluirForm", dados: {caminho: _caminho}, funcao: function () {
			redirecionar("pasta", _pasta)
		}})
})

setBotao("ativar", function () {
	Ajax({url: "/ajax.php?op=ativarForm", dados: {caminho: _caminho}, funcao: function () {
		window.location.reload()
	}})
})

setBotao("desativar", function () {
	Ajax({url: "/ajax.php?op=desativarForm", dados: {caminho: _caminho}, funcao: function () {
		window.location.reload()
	}})
})

setBotao("enviar", function () {
	get("submit").click()
})

setBotao("voltar", function () {
	redirecionar("pasta", _pasta)
})

setBotao("adicionarAnexo", function () {
	// Limita o número de anexos por envio
	if (_numNovos >= _maxNum)
		alert("O sistema só permite o upload de no máximo "+_maxNum+" anexos")
	
	// Monta a janela de opções de anexo
	mostrarJanela(true)
	
	get("janela").innerHTML = "<h2>Novo anexo</h2>"+
	"<p>Arquivo: <input type='file' id='anexo-file' name='arquivos[]'> (máx "+KiB2str(_maxCada)+")</p>"+
	"<span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span> "+
	"<span class='botao' onclick='adicionarAnexo()'><img src='/imgs/enviar.png'> Salvar</span>"
	
	get("anexo-file").click()
})

// Coloca o novo anexo na lista
function adicionarAnexo() {
	var file, div, tamanho
	
	// Valida as restrições de número e tamanho
	file = get("anexo-file")
	if (!file.files.length)
		return alert("Nenhum arquivo selecionado")
	tamanho = file.files[0].size/1024
	if (tamanho > _maxCada)
		return alert("O arquivo selecionado é muito grande\nO tamanho máximo permitido por arquivo é de "+KiB2str(_maxCada)+"\nSelecione outro ou contate o administrador")
	if (_tamanhoNovos+tamanho > _maxTotal-1024)
		return alert("O sistema só permite enviar "+KiB2str(_maxTotal)+" de anexos")
	
	// Coloca o item visualmente na lista
	div = criarTag("div", "", {class: "item item-anexo", oncontextmenu: "menu(event)", "data-tamanho": tamanho})
	div.innerHTML = "<span class='item-nome'>"+file.files[0].name+"</span>"+
	"<span class='item-descricao'>"+KiB2str(tamanho)+"</span>"
	get("anexos").appendChild(div)
	
	// Coloca os dados no formulário
	file.style.display = "none"
	file.id = ""
	div.appendChild(file)
	mostrarJanela(false)
	get("janela").innerHTML = ""
	_numNovos++
	_tamanhoNovos += tamanho
}

// Mostra o menu de opções para os anexos
function menu(evento) {
	var el = evento.currentTarget
	Menu.abrir(evento, [["<img src='/imgs/remover.png'> Remover anexo", function () {
		removerAnexo(el)
	}]])
}

// Remove um anexo
function removerAnexo(el) {
	_numNovos--
	_tamanhoNovos -= Number(el.dataset.tamanho)
	el.parentNode.removeChild(el)
}
