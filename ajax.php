<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/06/2013
*/

// Responde às chamadas em AJAX

// Carrega as configurações e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();
validarLogin();

// Gera um erro com o status 400
function retornarErro() {
	header('HTTP/1.1 400 Bad Request');
	exit;
}

// Retorna o valor com status de sucesso
function retornar($valor) {
	echo json_encode($valor);
	exit;
}

// Vai para a rotina correta
$op = @$_GET['op'];
if (!preg_match('@^[a-zA-Z0-9_-]+$@', $op))
	retornarErro();
if (!file_exists("ajax/$op.php"))
	retornarErro();
require_once "ajax/$op.php";
