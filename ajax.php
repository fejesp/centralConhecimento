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

// Vai para a rotina correta
$op = @$_GET['op'];
if ($op == 'getArvoreInicial') {
	// Retorna a árvore inicial para um dado caminho
	$caminho = @$_GET['caminho'];
	$pastas = preg_split('@/@', $caminho, -1, PREG_SPLIT_NO_EMPTY);
	$arvore = array('' => NULL);
	$nivelAtual = &$arvore[''];
	$pai = 0;
	
	for ($i=0; $i<count($pastas); $i++) {
		// Carrega cada nível da árvore, a partir da raiz
		$nivelAtual = array();
		$subpastas = Query::query(false, NULL, 'SELECT id, nome, visibilidade, criador FROM pastas WHERE pai=? AND id!=0 ORDER BY nome', $pai);
		$achou = false;
		foreach ($subpastas as $dados)
			if (verificarVisibilidade('pasta', $dados['id'], $dados['visibilidade'], $dados['criador'])) {
				$nivelAtual[$dados['nome']] = NULL;
				if ($dados['nome'] == $pastas[$i]) {
					$achou = true;
					$pai = $dados['id'];
				}
			}
		
		// Verifica se a pasta está no caminho
		if (!$achou)
			retornarErro();
		$nivelAtual = &$nivelAtual[$pastas[$i]];
	}
	
	retornar($arvore);
} else if ($op == 'getSubPastas') {
	// Interpreta o caminho da pasta
	$dados = NULL;
	$caminho = @$_GET['caminho'];
	$sucesso = interpretarCaminho($caminho, $dados);
	
	if (!$sucesso)
		retornarErro();
	
	// Carrega cada nível da árvore, a partir da raiz
	$subpastas = Query::query(false, NULL, 'SELECT id, nome, visibilidade, criador FROM pastas WHERE pai=? AND id!=0 ORDER BY nome', $dados['id']);
	$visiveis = array();
	foreach ($subpastas as $dados)
		if (verificarVisibilidade('pasta', $dados['id'], $dados['visibilidade'], $dados['criador']))
			$visiveis[$dados['nome']] = NULL;
	
	retornar($visiveis);
}

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
