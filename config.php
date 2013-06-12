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

// Limite máximo de espaço (em kiB)
$_config['espacoTotal'] = 2*1024*1024; // 2 GiB

// Limite de tentativas de login falhas em 1 hora
$_config['maxLogins'] = 10;

// Limite de tempo de uma conexão (em horas)
$_config['tempoSessao'] = 17;

// URL base da central (deve terminar em '/')
$_config['urlBase'] = 'http://localhost:10082/';
