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
$caminho = $_GET['caminho'];
$sucesso = interpretarCaminho($caminho, $dados);
if (!$sucesso || !$dados['id'])
	die('Erro: pasta não encontrada');
	
// Valida as permissões do usuário
if (!$_usuario || (!$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	die('Erro: o usuário atual não tem permissão para isso');

// Vai eliminando recursivamente os itens
// TODO: excluir fisicamente os anexos
// TODO: excluir estatísticas
function excluir($pasta) {
	// Exclui os anexos dos posts
	new Query('DELETE anexos FROM anexos JOIN posts ON anexos.post=posts.id WHERE posts.pasta=?', $pasta);
	
	// Exclui os posts e forms
	new Query('DELETE FROM posts WHERE pasta=?', $pasta);
	new Query('DELETE FROM forms WHERE pasta=?', $pasta);
	
	// Exclui as sub-pastas
	$pastas = Query::query(false, 0, 'SELECT id FROM pastas WHERE pai=?', $pasta);
	foreach ($pastas as $cada)
		excluir($cada);
	
	// Exclui essa pasta
	new Query('DELETE FROM pastas WHERE id=? LIMIT 1', $pasta);
}
excluir($dados['id']);

redirecionar('pasta' . getCaminhoAcima($caminho));
