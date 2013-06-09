<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Carrega as configurações e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();
validarLogin();

// Interpreta o caminho do item
$dados = NULL;
$caminho = @$_POST['caminho'];
$criar = isset($_GET['criar']);
$sucesso = interpretarCaminho($caminho, $dados, $criar ? 'pasta' : 'post');
if (!$sucesso)
	morrerComErro('Post não encontrado');
	
// Valida as permissões do usuário
if (!$_usuario || (!$criar && !$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	morrerComErro('O usuário atual não tem permissão para isso');

// Carrega os novos dados
$nome = $_POST['nome'];
$conteudo = $_POST['conteudo'];
$visibilidade = $_POST['visibilidade'];
$selecionados = isset($_POST['selecionados']) ? $_POST['selecionados'] : array();

// Valida o nome
if (!preg_match('@^[^/]+$@', $nome))
	morrerComErro('Nome inválido');

// Salva os novos dados
try {
	Query::$conexao->autocommit(false);
	if ($criar) {
		new Query('INSERT INTO posts VALUES (NULL, ?, ?, ?, NOW(), ?, ?)', $dados['id'], $nome, $conteudo, $visibilidade, $_usuario['id']);
		$dados['id'] = Query::$conexao->insert_id;
		$dados['criador'] = $_usuario['id'];
	} else {
		new Query('UPDATE posts SET nome=?, conteudo=?, data=NOW(), visibilidade=? WHERE id=? LIMIT 1', $nome, $conteudo, $visibilidade, $dados['id']);
		new Query('DELETE FROM visibilidades WHERE tipoItem="post" AND item=?', $dados['id']);
	}
	for ($i=0; $i<count($selecionados); $i++)
		if ((int)$selecionados[$i] != $dados['criador'])
			new Query('INSERT INTO visibilidades VALUES ("post", ?, ?)', $dados['id'], (int)$selecionados[$i]);
	Query::$conexao->commit();
	
	// Tudo ok, volta para a página anterior
	redirecionar('post' . ($criar ? $caminho . '/' . $nome : $caminho));
} catch (Exception $e) {
	morrerComErro('Falha ao gravar os dados, provavelmente já existe um post com esse nome');
}
