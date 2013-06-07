<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Faz o logout do usuário

// Inclui os arquivos básicos
require_once 'utils.php';

// Remove o cookie e redireciona
setcookie('central_login', '');
setcookie('central_id', '');
redirecionar('index');
