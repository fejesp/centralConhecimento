<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Retorna as pastas abaixo de uma outra

// Interpreta o caminho da pasta
$dados = NULL;
$caminho = @$_GET['caminho'];
$sucesso = interpretarCaminho($caminho, $dados);

if (!$sucesso)
	retornarErro();

// Carrega o próximo nível de pastas
$subpastas = Query::query(false, NULL, 'SELECT id, nome, visibilidade, criador FROM pastas WHERE pai=? AND id!=0 ORDER BY nome', $dados['id']);
$visiveis = array();
foreach ($subpastas as $dados)
	if (verificarVisibilidade('pasta', $dados['id'], $dados['visibilidade'], $dados['criador']))
		$visiveis[$dados['nome']] = NULL;

retornar($visiveis);
