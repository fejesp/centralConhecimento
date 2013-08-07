<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Ativa uma conta de usuário
if (!$_usuario || !$_usuario['admin'])
	retornarErro();
$id = (int)@$_POST['id'];
if ($id != $_usuario['id'])
	new Query('UPDATE usuarios SET ativo=1 WHERE id=? LIMIT 1', $id);
retornar(true);
