<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Exclui um formulário
$caminho = $_GET['caminho'];
$dados = NULL;
$sucesso = interpretarCaminho($caminho, $dados, 'form');
if ($sucesso && $_usuario && ($_usuario['admin'] || $dados['criador']==$_usuario['id']))
	new Query('DELETE FROM forms WHERE id=? LIMIT 1', $dados['id']);
else
	retornarErro();
