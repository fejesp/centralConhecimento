// Executa o login
setBotao("comLogin", function () {
	get("submit").click()
})

// Entra na pasta raiz diretamente
setBotao("semLogin", function () {
	window.location.href = "/pasta"
})
