/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

setBotao("layout-login", function () {
	window.location.href = "/index?continuar="+encodeURIComponent(window.location.href)
})

setBotao("layout-logout", function () {
	window.location.href = "/logout.php"
})

setBotao("layout-buscar", function () {
	window.location.href = "/busca"
})
