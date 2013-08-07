<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

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
