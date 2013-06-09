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

if (!$_usuario || !$_usuario['admin'])
	morrerComErro('Usuário sem permissão para isso');

// Recebe os dados
$criar = isset($_GET['criar']);
$id = (int)@$_POST['id'];
$nome = @$_POST['nome'];
$email = @$_POST['email'];
$usoMax = (int)@$_POST['usoMax'];

// Salva no banco de dados
try {
	if ($criar) {
		$senha = gerarSenha();
		new Query('INSERT INTO usuarios VALUES (NULL, ?, ?, ?, 0, 1, ?, "")', $nome, $email, md5($senha), $usoMax);
		redirecionar('admin/?novoUsuario=' . $senha);
	} else {
		new Query('UPDATE usuarios SET nome=?, email=?, usoMax=? WHERE id=? LIMIT 1', $nome, $email, $usoMax, $id);
		redirecionar('admin');
	}
} catch (Exception $e) {
	morrerComErro('Falha ao gravar os dados: ' . $e);
}