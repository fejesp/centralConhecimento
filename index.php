<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Trata as requisições às páginas
// Recebe o nome do modelo da página em $_GET['p']
// Recebe o restante da URL em $_GET['q']
// Exemplo: /pasta/caminho/para/pasta?a=b => array('p' => 'pasta', 'q' => '/caminha/para/pasta', 'a' => 'b')
// Cuidado: um parâmetro p ou q na URL irá sobreescrever o criado pelo redirecionamento

// Essa página verifica o login e delega para as páginas em layouts/ a validação de $_GET['q'] e a montagem do HTML

// Valida a página requisitada
if (empty($_GET['p']))
	$_GET['p'] = 'index';
if (!preg_match('@^[a-zA-Z]+$@', $_GET['p']) || !file_exists('layouts/' . $_GET['p'] . '.php'))
	die('Página inválida: ' . $_GET['p']);

// Carrega as configurações e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();
validarLogin();

// Chama layout.php para iniciar a geração do layout
require_once 'layout.php';
