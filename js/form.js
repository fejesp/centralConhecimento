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
