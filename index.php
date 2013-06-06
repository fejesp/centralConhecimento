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
if (!preg_match('@^[a-z]+$@', $_GET['p']) || !file_exists('layouts/' . $_GET['p'] . '.php'))
	die('Página inválida: ' . $_GET['p']);

// Carrega as configurações e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
Query::$conexao = new MySQLi($_config['mysql_host'], $_config['mysql_username'], $_config['mysql_password'], $_config['mysql_dbname']);
if (Query::$conexao->connect_error)
	die('Erro na conexão: ' . Query::$conexao->connect_error);
Query::$conexao->set_charset('utf8');

// Valida o login
// Se o login for inválido, $_usuario será NULL
// Se o login for válido, $_usuario terá os índices do seu registro no banco de dados
$_usuario = NULL;
if (isset($_COOKIE['central_login']) && isset($_COOKIE['central_id'])) {
	$id = (int)$_COOKIE['central_id'];
	$cookie = $_COOKIE['central_login'];
	if (Query::existe('SELECT 1 FROM usuarios WHERE id=? AND cookie=? LIMIT 1', $id, $cookie))
		$_usuario = Query::query(true, NULL, 'SELECT * FROM usuarios WHERE id=?', $id);
}

// Chama layout.php para iniciar a geração do layout
require_once 'layout.php';
