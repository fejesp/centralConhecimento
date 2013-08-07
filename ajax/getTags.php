<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Retorna todas as tags para o cliente (JS) sugerir as melhores ao usuário
retornar(Query::query(false, 0, 'SELECT nome FROM tags'));
