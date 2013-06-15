// Cria o visual do caminho
window.addEventListener("load", montarCaminho)

setBotao("editar", function () {
	redirecionar("editarPost", _caminho)
})

setBotao("remover", function () {
	if (confirm("Você tem certeza que deseja excluir o post "+_nome+"?\nTodo o seu conteúdo será excluído permanentemente!"))
		redirecionar("excluirPost.php", _caminho)
})
