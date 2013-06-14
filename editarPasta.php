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

// Interpreta o caminho da pasta
$dados = NULL;
$caminho = @$_POST['caminho'];
$criar = isset($_GET['criar']);
$sucesso = interpretarCaminho($caminho, $dados);
if (!$sucesso || (!$criar && !$dados['id']))
	morrerComErro('Pasta não encontrada');
	
// Valida as permissões do usuário
if (!$_usuario || (!$criar && !$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	morrerComErro('O usuário atual não tem permissão para isso');

// Carrega os novos dados
$nome = $_POST['nome'];
$descricao = $_POST['descricao'];
$visibilidade = $_POST['visibilidade'];
$selecionados = isset($_POST['selecionados']) ? $_POST['selecionados'] : array();

// Valida o nome
if (!preg_match('@^[^/]+$@', $nome))
	morrerComErro('Nome inválido');

// Salva os novos dados
try {
	Query::$conexao->autocommit(false);
	if ($criar) {
		new Query('INSERT INTO pastas VALUES (NULL, ?, ?, ?, ?, ?)', $nome, $descricao, $dados['id'], $visibilidade, $_usuario['id']);
		$dados['id'] = Query::$conexao->insert_id;
		$dados['criador'] = $_usuario['id'];
	} else {
		new Query('UPDATE pastas SET nome=?, descricao=?, visibilidade=? WHERE id=? LIMIT 1', $nome, $descricao, $visibilidade, $dados['id']);
		new Query('DELETE FROM visibilidades WHERE tipoItem="pasta" AND item=?', $dados['id']);
	}
	for ($i=0; $i<count($selecionados); $i++)
		if ((int)$selecionados[$i] != $dados['criador'])
			new Query('INSERT INTO visibilidades VALUES ("pasta", ?, ?)', $dados['id'], (int)$selecionados[$i]);
	Query::$conexao->commit();
	
	// Tudo ok, volta para a página anterior
	redirecionar('pasta', $criar ? $caminho : getCaminhoAcima($caminho));
} catch (Exception $e) {
	morrerComErro('Falha ao gravar os dados, provavelmente já existe uma pasta com esse nome');
}
