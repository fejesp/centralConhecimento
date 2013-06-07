<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Armazena as configurações da central
$_config = array();

// Parâmetros de conexão do banco de dados
$_config['mysql_host'] = 'localhost';
$_config['mysql_username'] = 'root';
$_config['mysql_password'] = '';
$_config['mysql_dbname'] = 'central';

// Limite máximo de espaço (em KiB)
$_config['espacoTotal'] = 2*1024*1024;