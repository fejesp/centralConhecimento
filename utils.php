<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Reúne várias funções úteis

// Redireciona o usuário para outra página
function redirecionar($pagina) {
	if (substr($pagina, 0, 7) == 'http://')
		header("Location: $pagina");
	else
		header("Location: /$pagina");
	exit;
}

// Retorna uma string aleatório com o tamanho desejado
function getRandomString($tamanho) {
	$base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
	$str = '';
	for ($i=0; $i<$tamanho; $i++)
		$str .= $base[mt_rand(0, 63)];
	return $str;
}

// Verifica a visibilidade de um item, dado seus parâmetros
// Não leva em conta as permissões dos itens acima desse
// $tipo é 'pasta', 'post', 'anexo' ou 'form'
// $criador para o caso de anexo é o criador do post
// $visibilidade para o caso do form é se ele está ativo (0 ou 1)
function verificarVisibilidade($tipo, $id, $visibilidade, $criador) {
	global $_usuario;
	if ($_usuario && $_usuario['admin'])
		// Para um administrador, tudo é visível
		return true;
	if ($tipo == 'pasta' && !$id)
		// Pasta raiz sempre é visível
		return true;
	if ($_usuario && $criador == $_usuario['id'])
		// É visível para seu criador
		return true;
	if ($tipo == 'form')
		return $visibilidade;
	if ($visibilidade == 'publico')
		// Visibilidade pública
		return true;
	if (!$_usuario)
		// Não é público e o usuário não está logado
		return false;
	if ($visibilidade == 'geral')
		// Todos os usuários podem ver
		return true;
	if ($visibilidade == 'seleto' && Query::existe('SELECT 1 FROM visibilidades WHERE tipoItem=? AND item=? AND usuario=? LIMIT 1', $tipo, $id, $_usuario['id']))
		// Somente os usuários selecionados podem ver
		return true;
	return false;
}

// Verifica se uma dada pasta é visível para o usuário atual
// $pasta é o id da pasta a ser verificada (ela deve existir)
// TODO: não utilizada, remover!
function pastaEVisivel($pasta) {
	global $_usuario;
	if ($_usuario && $_usuario['admin'])
		// Para um administrador, tudo é visível
		return true;
	if (!$pasta)
		// Pasta raiz sempre é visível
		return true;
	$dados = Query::query(true, NULL, 'SELECT visibilidade, criador, pai FROM pastas WHERE id=? LIMIT 1', $pasta);
	if ($_usuario && $dados['criador'] == $_usuario['id'])
		// A pasta é visível para seu criador
		return pastaEVisivel($dados['pai']);
	if ($dados['visibilidade'] == 'publico')
		// Visibilidade pública
		return pastaEVisivel($dados['pai']);
	if (!$_usuario)
		// Não é público e o usuário não está logado
		return false;
	if ($dados['visibilidade'] == 'geral')
		// Todos os usuários podem ver
		return pastaEVisivel($dados['pai']);
	if ($dados['visibilidade'] == 'seleto' && Query::existe('SELECT 1 FROM visibilidades WHERE tipoItem="pasta" AND item=? AND usuario=? LIMIT 1', $pasta, $_usuario['id']))
		// Somente os usuários selecionados podem ver
		return pastaEVisivel($dados['pai']);
	return false;
}

// Transforma em HTML seguro
function assegurarHTML($str) {
	return htmlentities($str, ENT_COMPAT, 'UTF-8');
}

// Imprime uma string na forma HTML de forma segura, dentro de uma tag opcional
// $tags é uma sequência de tags separada por espaço, cada tag pode ter uma classe associada após um ponto
// Exemplo: echoTag('oi', 'div.painel strong') => '<div class="painel"><strong>oi</strong></p>'
function imprimir($str, $tags='p') {
	$tags = explode(' ', strtolower($tags));
	$str = assegurarHTML($str);
	for ($i=count($tags)-1; $i>=0; $i--) {
		$tag = $tags[$i];
		if (($pos=strpos($tag, '.')) !== false) {
			$class = substr($tag, $pos+1);
			$tag = substr($tag, 0, $pos);
			$str = "<$tag class=\"$class\">$str</$tag>";
		} else
			$str = "<$tag>$str</$tag>";
	}
	echo $str . "\n";
}

// Imprime uma tag <script> de forma a enviar ao JS uma variável do PHP
// $nome é uma string com o nome que a variável JS terá
// $valor é qualquer valor PHP
function gerarJSVar($nome, $valor) {
	echo "<script>var $nome = " . json_encode($valor) . "</script>\n";
}

// Conecta ao banco de dados
function conectar() {
	global $_config;
	Query::$conexao = new MySQLi($_config['mysql_host'], $_config['mysql_username'], $_config['mysql_password'], $_config['mysql_dbname']);
	if (Query::$conexao->connect_error)
		die('Erro na conexão: ' . Query::$conexao->connect_error);
	Query::$conexao->set_charset('utf8');
}

// Valida o login
// Se o login for inválido, $_usuario será NULL
// Se o login for válido, $_usuario terá os índices do seu registro no banco de dados
function validarLogin() {
	global $_usuario;
	$_usuario = NULL;
	if (isset($_COOKIE['central_login']) && isset($_COOKIE['central_id'])) {
		$id = (int)$_COOKIE['central_id'];
		$cookie = $_COOKIE['central_login'];
		if (substr($cookie, -4) == date('dm'))
			if (Query::existe('SELECT 1 FROM usuarios WHERE id=? AND cookie=? LIMIT 1', $id, $cookie))
				$_usuario = Query::query(true, NULL, 'SELECT * FROM usuarios WHERE id=?', $id);
	}
}

// Retorna o caminho ("a/b/c") até uma dada pasta
// $pasta é o id de uma pasta que existe
// TODO: não utilizada, remover
function getCaminho($pasta) {
	if (!$pasta)
		return '';
	$dados = Query::query(true, NULL, 'SELECT nome, pai FROM pastas WHERE id=? LIMIT 1', $pasta);
	if ($dados['pai'])
		return getCaminho($dados['pai']) . '/' . $dados['nome'];
	else
		return $dados['nome'];
}

// Retorna o caminho da pasta pai
// Exemplo: '/a/b/c' => '/a/b' => '/a' => '/' => '/'
function getCaminhoAcima($caminho) {
	if ($caminho == '/')
		return '/';
	$partes = explode('/', substr($caminho, 1));
	array_pop($partes);
	return '/' . implode('/', $partes);
}

// Interpreta um caminho, levando em conta a visibilidade das pastas
// Se houver algum erro, a função irá retornar false (retorna true em caso de sucesso)
// Em caso de sucesso, $dados irá conter id, visibilidade, criador, descricao e nome da última pasta
// Altera $caminho, deixando-o normalizado na forma "/a/b"
function interpretarCaminho(&$caminho, &$dados) {
	$pastas = preg_split('@/@', $caminho, -1, PREG_SPLIT_NO_EMPTY);
	$caminho = '/' . implode('/', $pastas);
	
	$dados = array('id' => 0, 'visibilidade' => 'publico', 'criador' => 0, 'descricao' => '', 'nome' => 'Diretório raiz');
	if (!count($pastas)) {
		// Diretório raiz
		return true;
	}
	
	// Percorre o caminho, verificando a permissão de acesso
	for ($i=0; $i<count($pastas); $i++) {
		// Carrega o id da próxima pasta
		$pasta = $pastas[$i];
		if ($pasta == '')
			continue;
		try {
			$dados = Query::query(true, NULL, 'SELECT id, visibilidade, criador, descricao, nome FROM pastas WHERE nome=? AND pai=? LIMIT 1', $pasta, $dados['id']);
		} catch (Exception $e) {
			// Pasta não encontrada
			return false;
		}
		
		// Verifica se a pasta é visível para esse usuário
		if (!verificarVisibilidade('pasta', $dados['id'], $dados['visibilidade'], $dados['criador']))
			return false;
	}
	
	return true;
}
