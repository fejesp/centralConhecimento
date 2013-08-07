<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Retorna as estatísticas de todos os downloaders externos
if (!$_usuario || !$_usuario['admin'])
	retornarErro();
$query = 'SELECT
	email, empresa, COUNT(*) AS downloads
	FROM downloads
	WHERE usuario IS NULL
	GROUP BY email, empresa
	ORDER BY COUNT(*) DESC';
retornar(Query::query(false, NULL, $query));
