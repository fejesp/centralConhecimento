/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

setBotao("layout-login", function () {
	redirecionar("index", "", "", "continuar="+encodeURIComponent(window.location))
})

setBotao("layout-logout", function () {
	redirecionar("logout.php")
})

setBotao("layout-buscar", function () {
	redirecionar("busca")
})

setBotao("layout-editarUsuario", function () {
	redirecionar("editarUsuario")
})

setBotao("layout-admin", function () {
	redirecionar("admin")
})
