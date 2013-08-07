<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Retorna os nomes de quem baixou um anexo (pelo id) na forma de uma array
// Cada elemento dessa array tem os índices "usuario", "email", "empresa" e "data"
if (!$_usuario || !$_usuario['admin'])
	retornarErro();
$id = (int)$_GET['id'];
retornar(Query::query(false, NULL, 'SELECT
	"" AS usuario, email, empresa, data
	FROM downloads
	WHERE anexo=? AND usuario IS NULL
	UNION ALL SELECT
	u.nome, NULL, NULL, d.data
	FROM usuarios AS u
	JOIN downloads AS d ON d.usuario=u.id
	WHERE anexo=? AND usuario IS NOT NULL
	ORDER BY data DESC', $id, $id));
