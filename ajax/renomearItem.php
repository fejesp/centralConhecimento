<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Renomeia um post, form ou pasta

// Recebe e valida os parâmetros
$caminho = $_POST['caminho'];
$tipo = $_POST['tipo'];
$novoNome = $_POST['novoNome'];
if ($tipo != 'post' && $tipo != 'form' && $tipo != 'pasta')
	retornar(false);
if (!preg_match('@^[^/]+$@', $novoNome))
	retornar(false);

// Encontra o item
$dados = NULL;
$sucesso = interpretarCaminho($caminho, $dados, $tipo);

if ($sucesso && $_usuario && ($_usuario['admin'] || $dados['criador']==$_usuario['id'])) {
	// Tenta renomear
	$campo = $tipo=='pasta' ? 'pai' : 'pasta';
	$tipo = $tipo . 's';
	if (Query::existe("SELECT 1 FROM $tipo WHERE nome=? AND $campo=? AND id!=? LIMIT 1", $novoNome, $dados[$campo], $dados['id']))
		retornar(false);
	new Query("UPDATE $tipo SET nome=? WHERE id=? LIMIT 1", $novoNome, $dados['id']);
} else
	retornarErro();
retornar(true);
