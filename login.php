<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Faz o login do usuário

// Inclui os arquivos básicos e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
Query::$conexao = new MySQLi($_config['mysql_host'], $_config['mysql_username'], $_config['mysql_password'], $_config['mysql_dbname']);
if (Query::$conexao->connect_error)
	die('Erro na conexão: ' . Query::$conexao->connect_error);
Query::$conexao->set_charset('utf8');

// Pega os parâmetros do login
$email = @$_POST['email'];
$senha = md5(@$_POST['senha']);

// Valida os parâmetros
$id = Query::getValor('SELECT id FROM usuarios WHERE email=? AND senha=? AND ativo=1 LIMIT 1', $email, $senha);
if ($id === NULL)
	redirecionar('index?erroLogin');

// Cria o cookie
$cookie = getRandomString(32);
setcookie('central_login', $cookie);
setcookie('central_id', $id);

// Salva o cookie no banco de dados
new Query('UPDATE usuarios SET cookie=? WHERE id=? LIMIT 1', $cookie, $id);

// Redireciona
if (empty($_POST['continuar']))
	redirecionar('pasta');
else
	redirecionar($_POST['continuar']);
