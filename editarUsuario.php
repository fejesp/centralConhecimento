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

if (!$_usuario)
	morrerComErro('Usuário não encontrado');

// Recebe os dados
$nome = @$_POST['nome'];
$email = @$_POST['email'];
$novaSenha = @$_POST['novaSenha'];
$novaSenha2 = @$_POST['novaSenha2'];
$senha = @$_POST['senha'];

// Valida os dados
if (md5($senha) != $_usuario['senha'])
	morrerComErro('Senha incorreta');
if ($novaSenha && $novaSenha != $novaSenha2)
	morrerComErro('A mesma senha deve ser digitada duas vezes');

// Salva os novos dados
$dados = array();
if ($nome != $_usuario['nome'])
	$dados['nome'] = $nome;
if ($email != $_usuario['email'])
	$dados['email'] = $email;
if ($novaSenha)
	$dados['senha'] = md5($novaSenha);
if (count($dados))
	new Query('UPDATE usuarios SET ? WHERE id=? LIMIT 1', $dados, $_usuario['id']);
redirecionar('index');
