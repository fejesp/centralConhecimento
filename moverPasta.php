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
$dados2 = NULL;
$caminho = $_POST['caminho'];
$novoCaminho = $_POST['novoCaminho'];
$sucesso = interpretarCaminho($caminho, $dados);
$sucesso2 = interpretarCaminho($novoCaminho, $dados2);
if (!$sucesso || !$dados['id'] || !$sucesso2)
	morrerComErro('Pasta não encontrada');
	
// Valida as permissões do usuário
if (!$_usuario || (!$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	morrerComErro('O usuário atual não tem permissão para isso');

// Não deixa fechar um ciclo
if (substr($novoCaminho, 0, strlen($caminho)) == $caminho)
	morrerComErro('Não é possível colocar uma pasta dentro dela mesmo');

// Salva as alterações
new Query('UPDATE pastas SET pai=? WHERE id=? LIMIT 1', $dados2['id'], $dados['id']);

redirecionar('pasta', getCaminhoAcima($caminho));
