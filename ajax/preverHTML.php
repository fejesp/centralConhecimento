<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/08/2013
*/

// Converte texto simples em HTML com base numa sintaxe parecida com markdown
require_once 'gerarHTML.php';
retornar(gerarHTML($_POST['str']));
