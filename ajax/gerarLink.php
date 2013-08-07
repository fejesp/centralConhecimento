<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Retorna o link para gera uma nova senha para uma conta de usuário
if (!$_usuario || !$_usuario['admin'])
	retornarErro();
$id = (int)@$_POST['id'];
$chave = Query::getValor('SELECT senha FROM usuarios WHERE id=? LIMIT 1', $id);
if ($chave === NULL)
	retornar(false);
$chave = md5($chave);
$link = $_config['urlBase'] . "gerarSenha.php?id=$id&chave=$chave";
retornar($link);
