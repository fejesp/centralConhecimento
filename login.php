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
conectar();

// Pega os parâmetros do login
$email = @$_POST['email'];
$senha = md5(@$_POST['senha']);

// Pega os dados do usuário
try {
	$dados = Query::query(true, NULL, 'SELECT id, senha, ativo FROM usuarios WHERE email=? LIMIT 1', $email);
} catch (Exception $e) {
	redirecionar('index?erroLogin=1');
}

// Valida os parâmetros
if (!$dados['ativo'])
	redirecionar('index?erroLogin=2');

// Protege contra ataques de força bruta
$num = Query::getValor('SELECT COUNT(*) FROM logins WHERE usuario=? AND sucesso=0 AND HOUR(data)=HOUR(NOW())', $dados['id']);
if ($num >= $_config['maxLogins'])
	redirecionar('index?erroLogin=3');

if ($senha != $dados['senha']) {
	new Query('INSERT INTO logins VALUES (?, 0, NOW())', $dados['id']);
	redirecionar('index?erroLogin=4');
}

// Cria o cookie
$cookie = getRandomString(22) . date('YdmH');
setcookie('central_login', $cookie);
setcookie('central_id', $dados['id']);

// Salva o cookie no banco de dados
new Query('UPDATE usuarios SET cookie=? WHERE id=? LIMIT 1', $cookie, $dados['id']);

// Guarda a estatística
new Query('INSERT INTO logins VALUES (?, 1, NOW())', $dados['id']);

// Redireciona
if (empty($_POST['continuar']))
	redirecionar('pasta');
else
	redirecionar($_POST['continuar']);
