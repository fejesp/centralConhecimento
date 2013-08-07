<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Retorna o link completo para um post (pelo id)
if (!$_usuario || !$_usuario['admin'])
	retornarErro();

// Pega o id da pasta
$id = (int)$_GET['id'];
$post = Query::query(true, NULL, 'SELECT nome, pasta FROM posts WHERE id=? LIMIT 1', $id);

// Monta o caminho (de trás para frente)
$caminho = '';
$pasta = array('id' => $post['pasta']);
while ($pasta['id']) {
	$pasta = Query::query(true, NULL, 'SELECT nome, pai FROM pastas WHERE id=? LIMIT 1', $pasta['id']);
	$caminho = $pasta['nome'] . '/' . $caminho;
	$pasta['id'] = $pasta['pai'];
}
$caminho = '/' . substr($caminho, 0, -1);

// Retorna o caminho relativo a partir da raiz
retornar(html_entity_decode(getHref('post', $caminho, $post['nome']), ENT_QUOTES, 'UTF-8'));
