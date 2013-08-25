<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 25/08/2013
*/

// Edita/cria um comentário
$post = $_POST['post']; // Caminho do post
$id = (int)$_POST['id']; // Id do comentário (0 indica novo comentário)
$conteudo = $_POST['conteudo']; // Conteúdo do comentário

// Busca o post
$dados = NULL;
$sucesso = interpretarCaminho($post, $dados, 'post');
if (!$sucesso || !$_usuario)
	retornarErro();

if ($id) {
	// Edita um comentário
	$criador = Query::getValor('SELECT criador FROM comentarios WHERE post=? AND id=? LIMIT 1', $dados['id'], $id);
	if (!$criador || (!$_usuario['admin'] && $criador != $_usuario['id']))
		retornarErro();
	new Query('UPDATE comentarios SET conteudo=?, modificacao=NOW() WHERE id=? LIMIT 1', $conteudo, $id);
} else {
	// Cria um comentário
	new Query('INSERT INTO comentarios VALUES (NULL, ?, ?, NOW(), NOW(), ?)', $dados['id'], $conteudo, $_usuario['id']);
	$id = Query::$conexao->insert_id;
}

retornar(array('id' => $id, 'conteudo' => gerarHTML($conteudo)));

