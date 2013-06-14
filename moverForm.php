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

// Interpreta o caminho do form
$dados = NULL;
$dados2 = NULL;
$caminho = $_POST['caminho'];
$novoCaminho = $_POST['novoCaminho'];
$sucesso = interpretarCaminho($caminho, $dados, 'form');
$sucesso2 = interpretarCaminho($novoCaminho, $dados2);
if (!$sucesso || !$sucesso2)
	morrerComErro('Item não encontrado');
	
// Valida as permissões do usuário
if (!$_usuario || (!$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	morrerComErro('O usuário atual não tem permissão para isso');

// Salva as alterações
new Query('UPDATE forms SET pasta=? WHERE id=? LIMIT 1', $dados2['id'], $dados['id']);

redirecionar('pasta', getCaminhoAcima($caminho));
