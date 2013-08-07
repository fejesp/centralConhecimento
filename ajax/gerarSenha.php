<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Gera uma nova senha para uma conta de usuário
if (!$_usuario || !$_usuario['admin'])
	retornarErro();
$id = (int)@$_GET['id'];
$senha = gerarSenha();
if ($id != $_usuario['id'])
	new Query('UPDATE usuarios SET senha=? WHERE id=? LIMIT 1', md5($senha), $id);
retornar($senha);
