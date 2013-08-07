setBotao("criarUsuario", function () {
	editarUsuario(null)
})

// Exibe a senha do usuário criado
window.addEventListener("load", function () {
	if (window._novoUsuario) {
		mostrarJanela(true)
		get("janela").innerHTML = "<h2>Novo usuário</h2>"+
		"<p>Passe o link abaixo ao novo usuário. Ao acessa-lo ele irá receber um e-mail com sua senha aleatória:<br>"+assegurarHTML(_novoUsuario)+"</p>"+
		"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span></p>"
	}
})

// Coloca os menus em cada linha de usuários
window.addEventListener("load", function () {
	var linhas, i
	linhas = get("tabelaUsuarios").rows
	for (i=1; i<linhas.length; i++)
		linhas.item(i).oncontextmenu = function (evento) {
			var botoes = [], ativo, id, linha
			linha = evento.currentTarget
			ativo = !linha.classList.contains("inativo")
			id = Number(linha.dataset.id)
			if (id != _meuId)
				botoes.push([ativo ? "<img src='/imgs/desativar.png'> Desativar" : "<img src='/imgs/ativar.png'> Ativar", function () {
					linha.classList[ativo ? "add" : "remove"]("inativo")
					executarAjax(ativo ? "desativarUsuario" : "ativarUsuario", {id: id}, function () {
						linha.classList[!ativo ? "add" : "remove"]("inativo")
					})
				}])
			botoes.push(["<img src='/imgs/link.png'> Link de gerar senha", function () {
				var janela = abrirJanelaCarregando()
				executarAjax("gerarLink", {id: id}, mostrarJanela, function (link) {
					janela.innerHTML = "<h2>Link de alteração de senha</h2>"+
						"<p>Ao acessar o link abaixo, o usuário irá receber um e-mail com uma nova senha aleatória:<br>"+assegurarHTML(link)+"</p>"
				})
			}])
			botoes.push(["<img src='/imgs/editar.png'> Editar", function () {
				editarUsuario(linha)
			}])
			Menu.abrir(evento, botoes)
		}
})

// Abre a janela de edição de um usuário
// Recebe o elemento TR do usuário na tabela
function editarUsuario(linha) {
	var id, nome, email, usoMax, janela
	
	// Carrega os dados
	if (linha) {
		id = linha.dataset.id
		nome = linha.dataset.nome
		email = linha.dataset.email
		usoMax = linha.dataset.usoMax/1024
	} else {
		id = nome = email = ""
		usoMax = 10
	}
	
	// Monta a janela
	mostrarJanela(true)
	janela = get("janela")
	janela.innerHTML = "<h2>Editar usuário</h2>"+
		"<form method='post' action='/editarUsuarioAdmin.php"+(linha ? "" : "?criar")+"'>"+
		"<div class='rotuloEsquerdo'>Nome: <br>Email: <br>Quota de uso de espaço: </div>"+
		"<div class='opcoesDireita'><input size='30' name='nome' value='"+assegurarHTML(nome)+"' required><br>"+
		"<input size='30' name='email' type='email' value='"+assegurarHTML(email)+"' required><br>"+
		"<input name='usoMax' size='10' value='"+usoMax+"' required type='number' min='0' step='1'> MiB (0 = sem limites)</div>"+
		"<div class='clear'></div>"+
		"<input name='id' type='hidden' value='"+id+"'><input type='submit' style='display:none' id='submit'>"+
		"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span> "+
		"<span class='botao' onclick='get(\"submit\").click()'><img src='/imgs/enviar.png'> Salvar</span></p>"+
		"</form>"
}

/*

Tabela de anexos mais baixados

*/

// Coloca o menu em cada linha de downloads
window.addEventListener("load", function () {
	var linhas, i
	linhas = get("tabelaDownloads").rows
	for (i=1; i<linhas.length; i++)
		linhas.item(i).oncontextmenu = onContextMenuAnexos
})

// Mostra o menu de uma linha
function onContextMenuAnexos(evento) {
	var id, nome, idPost
	id = evento.currentTarget.dataset.id
	idPost = evento.currentTarget.dataset.idPost
	nome = evento.currentTarget.dataset.nome
	Menu.abrir(evento, [["<img src='/imgs/buscar.png'> Ver quem baixou", function () {
		mostrarQuemBaixou(id, nome)
	}], ["<img src='/imgs/enviar.png'> Ver post", function () {
		Ajax({url: "/ajax.php", dados: {op: "getLinkPost", id: idPost}, funcao: function (link) {
			window.open(link, "_blank")
		}, retorno: "json"})
	}]])
}

// Monta a tabela de quem baixou um dado anexo
function mostrarQuemBaixou(id, nome) {
	var div
	div = abrirJanelaCarregando()
	Ajax({url: "/ajax.php", dados: {op: "getDownloads", id: id}, funcao: function (dados) {
		var html, i, quem
		html = "<p>Downloads de "+assegurarHTML(nome)+"</p>"
		html += "<table><tr><th>Quem</th><th>Quando</th></tr>"
		for (i=0; i<dados.length; i++) {
			quem = dados[i].usuario ? dados[i].usuario : dados[i].email+" ("+dados[i].empresa+")"
			html += "<tr><td>"+assegurarHTML(quem)+"</td><td>"+dados[i].data+"</td></tr>"
		}
		html += "</table>"
		div.innerHTML = html
	}, retorno: "json"})
}

// Botão para mostrar todos os downloads feitos
setBotao("btMaisDownloads", function () {
	var div
	
	// Mostra feedback
	div = get("btMaisDownloads").parentNode
	div.innerHTML = "Carregando..."
	
	Ajax({url: "/ajax.php", dados: {op: "getTodosDownloads"}, funcao: function (dados) {
		var tabela, tr, i
		
		// Remove todas as linhas atuais
		tabela = get("tabelaDownloads")
		while (tabela.rows.length > 1)
			tabela.deleteRow(1)
		
		// Coloca as novas linhas
		for (i=0; i<dados.length; i++) {
			tr = document.createElement("tr")
			tr.appendChild(criarTag("td.nomePost", dados[i].post))
			tr.appendChild(criarTag("td", dados[i].anexo))
			tr.appendChild(criarTag("td", dados[i].downloads))
			tr.dataset.id = dados[i].id
			tr.dataset.nome = dados[i].anexo
			tr.dataset.idPost = dados[i].idPost
			tabela.appendChild(tr)
			tr.oncontextmenu = onContextMenuAnexos
		}
		
		// Remove o botão
		div.parentNode.removeChild(div)
	}, retorno: "json"})
})

/*

Tabela de downloads externos

*/

// Coloca o menu em cada linha de downloads
window.addEventListener("load", function () {
	var linhas, i
	linhas = get("tabelaDownloadsExternos").rows
	for (i=1; i<linhas.length; i++)
		linhas.item(i).oncontextmenu = onContextMenuDownloaders
})

// Mostra a menu de uma linha
function onContextMenuDownloaders(evento) {
	var email, empresa
	email = evento.currentTarget.dataset.email
	empresa = evento.currentTarget.dataset.empresa
	Menu.abrir(evento, [["<img src='/imgs/buscar.png'> Ver o que foi baixado", function () {
		var div = abrirJanelaCarregando()
		div.innerHTML = "<p>Downloads de "+assegurarHTML(email)+" ("+assegurarHTML(empresa)+")</p><table>"+
			"<tr><th>Post</th><th>Anexo</th><th>Data</td></tr></table>"
		Ajax({url: "/ajax.php", dados: {op: "getAnexos", email: email, empresa: empresa}, funcao: function (dados) {
			var tabela, i, tr
			tabela = div.childNodes[1]
			for (i=0; i<dados.length; i++) {
				tr = document.createElement("tr")
				tr.appendChild(criarTag("td.nomePost", dados[i].post))
				tr.appendChild(criarTag("td", dados[i].anexo))
				tr.appendChild(criarTag("td", dados[i].data))
				tr.dataset.id = dados[i].id
				tr.dataset.nome = dados[i].anexo
				tr.dataset.idPost = dados[i].idPost
				tabela.appendChild(tr)
				tr.oncontextmenu = onContextMenuAnexos
			}
		}, retorno: "json"})
	}]])
}

// Botão para mostrar todos os downloaders externos
setBotao("btMaisDownloadsExternos", function () {
	var div
	
	// Mostra feedback
	div = get("btMaisDownloadsExternos").parentNode
	div.innerHTML = "Carregando..."
	
	Ajax({url: "/ajax.php", dados: {op: "getTodosDownloadsExternos"}, funcao: function (dados) {
		var tabela, tr, i
		
		// Remove todas as linhas atuais
		tabela = get("tabelaDownloadsExternos")
		while (tabela.rows.length > 1)
			tabela.deleteRow(1)
		
		// Coloca as novas linhas
		for (i=0; i<dados.length; i++) {
			tr = document.createElement("tr")
			tr.appendChild(criarTag("td", dados[i].email))
			tr.appendChild(criarTag("td", dados[i].empresa))
			tr.appendChild(criarTag("td", dados[i].downloads))
			tr.dataset.email = dados[i].email
			tr.dataset.empresa = dados[i].empresa
			tabela.appendChild(tr)
			tr.oncontextmenu = onContextMenuDownloaders
		}
		
		// Remove o botão
		div.parentNode.removeChild(div)
	}, retorno: "json"})
})
