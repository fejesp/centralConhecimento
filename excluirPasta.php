<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/06/2013
*/

// Carrega as configurações e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();
validarLogin();

// Interpreta o caminho da pasta
$dados = NULL;
$caminho = @$_GET['caminho'];
$sucesso = interpretarCaminho($caminho, $dados);
if (!$sucesso || !$dados['id'])
	morrerComErro('Pasta não encontrada');
	
// Valida as permissões do usuário
if (!$_usuario || (!$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	morrerComErro('O usuário atual não tem permissão para isso');

// Faz um levantamento recursivo do que será deletado
// Para deletar algum item, tem de ser administrador ou seu criador
$posts = array();
$forms = array();
$pastas = array();
function levantar($pasta, &$posts, &$forms, &$pastas) {
	global $_usuario;
	$pastas[] = $pasta;
	
	// Carrega os posts
	$posts2 = Query::query(false, NULL, 'SELECT id, criador FROM posts WHERE pasta=?', $pasta);
	for ($i=0; $i<count($posts2); $i++) {
		if (!$_usuario['admin'] && $posts2[$i]['criador'] != $_usuario['id'])
			morrerComErro('O usuário atual não tem permissão para excluir todos os itens dentro dessa pasta');
		$posts[] = $posts2[$i]['id'];
	}
	
	// Carrega os forms
	$forms2 = Query::query(false, NULL, 'SELECT id, criador FROM forms WHERE pasta=?', $pasta);
	for ($i=0; $i<count($forms2); $i++) {
		if (!$_usuario['admin'] && $forms2[$i]['criador'] != $_usuario['id'])
			morrerComErro('O usuário atual não tem permissão para excluir todos os itens dentro dessa pasta');
		$forms[] = $forms2[$i]['id'];
	}
	
	// Carrega as pastas
	$pastas2 = Query::query(false, NULL, 'SELECT id, criador FROM pastas WHERE pai=?', $pasta);
	for ($i=0; $i<count($pastas2); $i++) {
		if (!$_usuario['admin'] && $pastas2[$i]['criador'] != $_usuario['id'])
			morrerComErro('O usuário atual não tem permissão para excluir todos os itens dentro dessa pasta');
		levantar($pastas2[$i]['id'], $posts, $forms, $pastas);
	}
}
levantar($dados['id'], $posts, $forms, $pastas);
$anexos = count($posts) ? Query::query(false, 0, 'SELECT id FROM anexos WHERE post IN ?', $posts) : array();

// Exclui itens relacionados
if (count($pastas))
	new Query('DELETE FROM visibilidades WHERE tipoItem="pasta" AND item IN ?', $pastas);
if (count($posts))
	new Query('DELETE FROM visibilidades WHERE tipoItem="post" AND item IN ?', $posts);
if (count($anexos))
	new Query('DELETE FROM visibilidades WHERE tipoItem="anexo" AND item IN ?', $anexos);
if (count($posts))
	new Query('DELETE FROM tagsEmPosts WHERE post IN ?', $posts);
if (count($anexos))
	new Query('DELETE FROM downloads WHERE anexo IN ?', $anexos);

// Exclui todos os itens
if (count($anexos)) {
	new Query('DELETE FROM anexos WHERE id IN ?', $anexos);
	foreach ($anexos as $cada)
		unlinkAnexo($cada);
}
if (count($forms))
	new Query('DELETE FROM forms WHERE id IN ?', $forms);
if (count($posts)) {
	new Query('DELETE FROM comentarios WHERE post IN ?', $posts);
	new Query('DELETE FROM posts WHERE id IN ?', $posts);
}

// Delete a pasta (o banco de dados cuida da recursão)
new Query('DELETE FROM pastas WHERE id=?', $dados['id']);

redirecionar('pasta', getCaminhoAcima($caminho));
