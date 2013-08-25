setBotao("editar", function () {
	redirecionar("editarPost", _caminho)
})

setBotao("remover", function () {
	if (confirm("Você tem certeza que deseja excluir o post "+_nome+"?\nTodo o seu conteúdo será excluído permanentemente!"))
		redirecionar("excluirPost.php", _caminho)
})

/*

Comentários

*/

var comentando = {
	ativo: false, // Indica se existe alguma edição em andamento
	comentario: null // Indica qual a div do comentário sendo editado (null para novo comentário)
}

// Monta a interface para criar um novo comentário
function criarComentario() {
	var form = get("editarComentario")
	get("conteudo").value = ""
	get("comentarios").appendChild(form)
	form.style.display = ""
	get("conteudo").focus()
	comentando.ativo = true
	comentando.comentario = null
}

// Inicia a edição de um comentário
function editarComentario(id) {
	var div, form
	div = getDivComentario(id)
	form = get("editarComentario")
	get("conteudo").value = div.dataset.conteudo
	get("comentarios").insertBefore(form, div)
	form.style.display = ""
	div.style.display = "none"
	get("conteudo").focus()
	comentando.ativo = true
	comentando.comentario = div
}

// Exclui um comentário
function excluirComentario(id) {
	var div
	if (confirm("Você tem certeza que deseja excluir esse comentário?")) {
		div = getDivComentario(id)
		div.style.display = "none"
		executarAjax("excluirComentario", {post: _caminho, id: id}, function () {
			alert("Erro ao excluir comentário")
			div.style.display = ""
		})
	}
}

// Retorna a div do comentário pelo id
function getDivComentario(id) {
	var els = get("comentarios").childNodes, i
	for (i=0; i<els.length; i++)
		if (els.item(i).dataset.id == id)
			return els.item(i)
	return null
}

// Volta a interface para o normal, antes da edição
function cancelarEdicaoComentario() {
	var form
	if (comentando.ativo) {
		form = get("editarComentario")
		form.style.display = "none"
		comentando.ativo = false
		if (comentando.comentario) {
			comentando.comentario.style.display = ""
			comentando.comentario = null
		}
	}
}

function confirmarEdicaoComentario() {
	var dados, div, conteudo, acoes, novo, conteudoAntes
	
	// Monta e envia a requisição
	dados = {}
	dados.post = _caminho
	dados.id = comentando.comentario ? Number(comentando.comentario.dataset.id) : 0
	dados.conteudo = get("conteudo").value
	executarAjax("editarComentario", dados, function () {
		if (novo) {
			get("comentarios").removeChild(div)
			alert("Erro na criação do comentário")
		} else {
			conteudo.innerHTML = conteudoAntes
			alert("Erro na edição do comentário")
		}
	}, function (dados) {
		conteudo.innerHTML = dados.conteudo
		if (novo) {
			div.dataset.id = dados.id
			acoes.innerHTML = "<span class='botao' onclick='excluirComentario("+dados.id+")'><img src='/imgs/excluirComentario.png'> Excluir</span>"+
			"<span class='botao' onclick='editarComentario("+dados.id+")'><img src='/imgs/editarComentario.png'> Editar</span>"
		}
	})
	
	// Atualiza a interface
	novo = !comentando.comentario
	if (novo) {
		div = document.createElement("div")
		div.className = "comentario"
		div.dataset.conteudo = get("conteudo").value
		div.appendChild(criarTag("p.detalhe", _nomeUsuario+" disse"))
		acoes = criarTag("div.acoes")
		div.appendChild(acoes)
		conteudo = criarTag("div.subConteudo")
		conteudo.innerHTML = "<em>Carregando...</em>"
		div.appendChild(conteudo)
		get("comentarios").appendChild(div)
	} else {
		div = comentando.comentario
		conteudo = div.querySelector(".subConteudo")
		conteudoAntes = conteudo.innerHTML
		conteudo.innerHTML = "<em>Carregando...</em>"
	}
	div.dataset.conteudo = get("conteudo").value
	cancelarEdicaoComentario()
}

// Define as ações dos botões
setBotao("comentar", criarComentario)
setBotao("cancelar", cancelarEdicaoComentario)
setBotao("salvar", confirmarEdicaoComentario)

// Carrega a previsão do resultado do conteúdo em HTML
function visualizar() {
	var str, janela
	str = get("conteudo").value
	janela = abrirJanelaCarregando()
	Ajax({url: "/ajax.php?op=preverHTML", dados: {str: str}, funcao: function (html) {
		janela.innerHTML = html
	}, retorno: "json", metodo: "post"})
	janela.className = "subConteudo"
}
