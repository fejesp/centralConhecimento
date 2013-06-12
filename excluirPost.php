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

// Interpreta o caminho do post
$dados = NULL;
$caminho = @$_GET['caminho'];
$sucesso = interpretarCaminho($caminho, $dados, 'post');
if (!$sucesso)
	morrerComErro('Post não encontrado');
	
// Valida as permissões do usuário
if (!$_usuario || (!$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	morrerComErro('O usuário atual não tem permissão para isso');

// Separa o que deve ser excluído
$anexos = Query::query(false, 0, 'SELECT id FROM anexos WHERE post=?', $dados['id']);

// Exclui itens relacionados
new Query('DELETE FROM visibilidades WHERE tipoItem="post" AND item=?', $dados['id']);
if (count($anexos))
	new Query('DELETE FROM visibilidades WHERE tipoItem="anexo" AND item IN ?', $anexos);
new Query('DELETE FROM tagsemposts WHERE post=?', $dados['id']);
if (count($anexos))
	new Query('DELETE FROM downloads WHERE anexo IN ?', $anexos);

// Exclui todos os itens
if (count($anexos)) {
	foreach ($anexos as $cada)
		unlinkAnexo($cada);
	new Query('DELETE FROM anexos WHERE id IN ?', $anexos);
}
new Query('DELETE FROM posts WHERE id=?', $dados['id']);

redirecionar('pasta' . getCaminhoAcima($caminho));
