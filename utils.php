<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Reúne várias funções úteis

// Redireciona o usuário para outra página
function redirecionar($pagina) {
	header("Location: /$pagina");
	exit;
}
