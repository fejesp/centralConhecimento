<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Retorna as estatísticas de todos os downloads
if (!$_usuario || !$_usuario['admin'])
	retornarErro();
$query = 'SELECT
	a.id AS id, p.id AS idPost, p.nome AS post, a.nome AS anexo, COUNT(*) AS downloads
	FROM posts AS p
	JOIN anexos AS a ON a.post=p.id
	JOIN downloads AS d ON d.anexo=a.id
	WHERE d.usuario IS NULL OR d.usuario NOT IN (SELECT id FROM usuarios WHERE admin=1)
	GROUP BY a.id
	ORDER BY COUNT(*) DESC';
retornar(Query::query(false, NULL, $query));
