<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 25/08/2013
*/

// Exclui um comentário
$post = $_POST['post']; // Caminho do post
$id = (int)$_POST['id']; // Id do comentário

// Busca o post
$dados = NULL;
$sucesso = interpretarCaminho($post, $dados, 'post');
if (!$sucesso || !$_usuario)
	retornarErro();

// Busca o comentário
$criador = Query::getValor('SELECT criador FROM comentarios WHERE post=? AND id=? LIMIT 1', $dados['id'], $id);
if (!$criador || (!$_usuario['admin'] && $criador != $_usuario['id']))
	retornarErro();

// Exclui o comentário
new Query('DELETE FROM comentarios WHERE id=? LIMIT 1', $id);
retornar(true);
