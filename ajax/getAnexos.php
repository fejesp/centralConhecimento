<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Retorna os posts baixados por uma pessoa externa (pelo email+empresa)
if (!$_usuario || !$_usuario['admin'])
	retornarErro();
$email = $_GET['email'];
$empresa = $_GET['empresa'];
retornar(Query::query(false, NULL, 'SELECT
	p.nome AS post, a.nome AS anexo, d.data AS data, a.id AS id, p.id AS idPost
	FROM posts AS p
	JOIN anexos AS a ON a.post=p.id
	JOIN downloads AS d ON d.anexo=a.id
	WHERE d.email=? AND d.empresa=?
	ORDER BY data DESC', $email, $empresa));
