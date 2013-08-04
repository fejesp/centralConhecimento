<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

require_once 'gerarHTML.php';

// Reúne várias funções úteis

// Redireciona o usuário para outra página
// $tipo é o nome de um arquivo php (terminando em '.php') ou um formato de layout (ex: 'index')
function redirecionar($tipo, $caminho='/', $nome='', $query='') {
	$caminho = ($caminho=='/' ? '' : $caminho) . '/' . $nome;
	if (substr($tipo, -4) == '.php')
		$location = '/' . $tipo . '?caminho=' . urlencode($caminho) . ($query ? '&' . $query : '');
	else {
		$caminho = strtr($caminho, array('?' => '%3F', '\\' => '%5C', '#' => '%23', '&' => '%26', '+' => '%2B'));
		$caminho = str_replace('%', '%25', $caminho);
		$location = '/' . $tipo . $caminho . ($query ? '?' . $query : '');
	}
	header("Location: $location");
	exit;
}

// Retorna uma chave de login, usada para identificar e validar os logins
function gerarChaveLogin() {
	$base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
	$str = '';
	for ($i=0; $i<22; $i++)
		$str .= $base[mt_rand(0, 63)];
	return $str . date('YdmH');
}

// Gera uma nova senha aleatória
function gerarSenha() {
	return substr(str_shuffle('abcdefghjklmnpqrstuvwxyz23456789'), 0, 8);
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

// Transforma em HTML seguro
function assegurarHTML($str) {
	return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

// Imprime uma string na forma HTML de forma segura, dentro de uma tag opcional
// $tags é uma sequência de tags separada por espaço, cada tag pode ter uma classe associada após um ponto
// Exemplo: echoTag('oi', 'div.painel strong') => '<div class="painel"><strong>oi</strong></p>'
// Se $gerarHTML for true, passa a $str pelo interpretador gerarHTML($str)
function imprimir($str, $tags='p', $gerarHTML=false) {
	$tags = explode(' ', $tags);
	$str = $gerarHTML ? gerarHTML($str) : assegurarHTML($str);
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
		morrerComErro('Não foi possível conectar ao banco de dados: ' . Query::$conexao->connect_error);
	Query::$conexao->set_charset('utf8');
}

// Valida o login
// Se o login for inválido, $_usuario será NULL
// Se o login for válido, $_usuario terá os índices do seu registro no banco de dados
function validarLogin() {
	global $_usuario, $_config;
	$_usuario = NULL;
	if (isset($_COOKIE['central_login']) && isset($_COOKIE['central_id'])) {
		$id = (int)$_COOKIE['central_id'];
		$cookie = $_COOKIE['central_login'];
		if (preg_match('@^.{22}[0-9]{10}$@', $cookie)) {
			$H = substr($cookie, -2, 2);
			$m = substr($cookie, -4, 2);
			$d = substr($cookie, -6, 2);
			$Y = substr($cookie, -10, 4);
			$time = mktime($H, 0, 0, $m, $d, $Y);
			if (time()-$time < 3600*$_config['tempoSessao'])
				if (Query::existe('SELECT 1 FROM usuarios WHERE id=? AND cookie=? LIMIT 1', $id, $cookie))
					$_usuario = Query::query(true, NULL, 'SELECT * FROM usuarios WHERE id=?', $id);
		}
	}
}

// Retorna o caminho da pasta pai
// Exemplo: '/a/b/c' => '/a/b' => '/a' => '/' => '/'
function getCaminhoAcima($caminho) {
	if ($caminho == '/')
		return '';
	$partes = explode('/', substr($caminho, 1));
	array_pop($partes);
	return '/' . implode('/', $partes);
}

// Interpreta um caminho, levando em conta a visibilidade das pastas
// Se houver algum erro, a função irá retornar false (retorna true em caso de sucesso)
// Em caso de sucesso, $dados irá conter todas as colunas do registro do item no banco de dados
// Em caso de erro, $dados irá ser um inteiro com o código do erro:
// (1 = caminho inválido, 2 = caminho não encontrado, 3 = caminho invisível, 4 = item não encontrado, 5 = item invisível)
// Altera $caminho, deixando-o normalizado na forma "/a/b"
// $tipo é 'pasta', 'post', 'anexo' ou 'form' e indica o tipo do item apontado pelo caminho
function interpretarCaminho(&$caminho, &$dados, $tipo='pasta') {
	$pastas = preg_split('@/@', $caminho, -1, PREG_SPLIT_NO_EMPTY);
	$caminho = '/' . implode('/', $pastas);
	
	// Diretório raiz
	if (!count($pastas)) {
		if ($tipo == 'pasta') {
			$dados = Query::query(true, NULL, 'SELECT * FROM pastas WHERE id=0 LIMIT 1');
			return true;
		} else {
			$dados = 1;
			return false;
		}
	}
	
	// Percorre o caminho, verificando a permissão de acesso
	$dados = array('id' => 0);
	$max = $tipo=='anexo' ? count($pastas)-2 : count($pastas)-1;
	for ($i=0; $i<$max; $i++) {
		// Carrega o id da próxima pasta
		$pasta = $pastas[$i];
		try {
			$dados = Query::query(true, NULL, 'SELECT id, visibilidade, criador FROM pastas WHERE nome=? AND pai=? LIMIT 1', $pasta, $dados['id']);
		} catch (Exception $e) {
			// Pasta não encontrada
			$dados = 2;
			return false;
		}
		
		// Verifica se a pasta é visível para esse usuário
		if (!verificarVisibilidade('pasta', $dados['id'], $dados['visibilidade'], $dados['criador'])) {
			$dados = 3;
			return false;
		}
	}
	
	// Pega o último item
	if ($max<0) {
		$dados = 1;
		return false;
	}
	$item = $pastas[$max];
	try {
		if ($tipo == 'pasta')
			$dados = Query::query(true, NULL, 'SELECT * FROM pastas WHERE nome=? AND pai=? LIMIT 1', $item, $dados['id']);
		else if ($tipo == 'form')
			$dados = Query::query(true, NULL, 'SELECT * FROM forms WHERE nome=? AND pasta=? LIMIT 1', $item, $dados['id']);
		else
			$dados = Query::query(true, NULL, 'SELECT * FROM posts WHERE nome=? AND pasta=? LIMIT 1', $item, $dados['id']);
	} catch (Exception $e) {
		// Item não encontrado
		$dados = 4;
		return false;
	}
	
	// Verifica se o item é visível para esse usuário
	if (!verificarVisibilidade($tipo=='anexo' ? 'post' : $tipo, $dados['id'], $tipo=='form' ? $dados['ativo'] : $dados['visibilidade'], $dados['criador'])) {
		$dados = $tipo == 'anexo' ? 3 : 5;
		return false;
	}
	
	// Verifica o anexo finalmente
	if ($tipo == 'anexo') {
		$criador = $dados['criador'];
		$dados = Query::query(true, NULL, 'SELECT * FROM anexos WHERE nome=? AND post=? LIMIT 1', $pastas[$max+1], $dados['id']);
		if (!verificarVisibilidade('anexo', $dados['id'], $dados['visibilidade'], $criador)) {
			$dados = 5;
			return false;
		}
	}
	
	return true;
}

// Mostra a página de erro no layout padrão em layout.php e termina o programa
// $erro é uma string que será impressa sem processar o HTML
function morrerComErro($erro) {
	global $_usuario, $_erro;
	$_GET['p'] = 'erro';
	$_usuario = NULL;
	$_erro = $erro;
	header('HTTP/1.1 400 Bad Request');
	require_once 'layout.php';
	exit;
}

// Transforma de número de kiB (int) para string
function kiB2str($num) {
	if ($num < 1000)
		return round($num) . ' kiB';
	if ($num < 10240)
		return round($num/1024, 2) . ' MiB';
	if ($num < 102400)
		return round($num/1024, 1) . ' MiB';
	if ($num < 1024000)
		return round($num/1024) . ' MiB';
	return round($num/1024/1024, 2) . ' GiB';
}

// Converte da data do banco de dados para um formato mais legível
// Ex: "2013-06-09 12:39:27" => "Há 14 horas e 12 minutos"
function data2str($data) {
	// Calcula a diferença em segundos
	$Y = substr($data, 0, 4);
	$m = substr($data, 5, 2);
	$d = substr($data, 8, 2);
	$H = substr($data, 11, 2);
	$i = substr($data, 14, 2);
	$s = substr($data, 17, 2);
	$diff = time()-mktime($H, $i, $s, $m, $d, $Y);
	
	// Calcula a diferença em várias unidades
	$s = $diff;
	$min = floor($s/60);
	$h = floor($min/60);
	$d = floor($h/24);
	$m = floor($d/30.4375);
	$a = floor($m/12);
	$m %= 12;
	$d %= 30;
	$h %= 24;
	$min %= 60;
	$s %= 60;
	
	// Transforma num formato melhor
	if ($a)
		return 'há ' . ($a==1 ? 'um ano' : $a . ' anos') . ' e ' . ($m==1 ? 'um mês' : $m . ' meses');
	if ($m)
		return 'há ' . ($m==1 ? 'um mês' : $m . ' meses') . ' e ' . ($d==1 ? 'um dia' : $d . ' dias');
	if ($d)
		return 'há ' . ($d==1 ? 'um dia' : $d . ' dias') . ' e ' . ($h==1 ? 'uma hora' : $h . ' horas');
	if ($h)
		return 'há ' . ($h==1 ? 'uma hora' : $h . ' horas') . ' e ' . ($min==1 ? 'um minuto' : $min . ' minutos');
	if ($min)
		return 'há ' . ($min==1 ? 'um minuto' : $min . ' minutos') . ' e ' . ($s==1 ? 'um segundo' : $s . ' segundos');
	return 'há ' . ($s==1 ? 'um segundo' : $s . ' segundos');
}

// Transforma do formato de bytes no php.ini em kiB
// Exemplo: "123456" => 120; "2M" => 2048
function ini2kiB($str) {
	$fator = 1/1024;
	if (strtoupper(substr($str, -1)) == 'K')
		$fator = 1;
	else if (strtoupper(substr($str, -1)) == 'M')
		$fator = 1024;
	else if (strtoupper(substr($str, -1)) == 'G')
		$fator = 1024*1024;
	if ($fator >= 1)
		$str = substr($str, 0, -1);
	$num = (int)$str;
	return ceil($num*$fator);
}

// Exclui um anexo da árvore de arquivos
// Recebe o $id (int) do anexo
// Atenção: isso não irá remover o registro do anexo do banco de dados, somente use essa função após fazer isso
function unlinkAnexo($id) {
	$dir = "arquivos/$id";
	if (file_exists($dir)) {
		foreach (scandir($dir) as $cada)
			if ($cada != '.' && $cada != '..')
				unlink("$dir/$cada");
		rmdir($dir);
	}
}

// Retorna a URL para ser usada num href de um link
// $tipo, $caminho e $nome são strings, o resultado será algo como "/tipo/caminho/nome"
// $absoluto indica se a URL gerada será relativa ("/...") ou absoluta ("http://...") (padrão: false)
function getHref($tipo, $caminho, $nome, $absoluto=false) {
	global $_config;
	$href = ($absoluto ? $_config['urlBase'] : '/') . $tipo . ($caminho=='/' ? '' : $caminho) . '/' . $nome;
	$href = strtr($href, array('?' => '%3F', '\\' => '%5C', '#' => '%23', '&' => '%26', '+' => '%2B', '`' => '%60'));
	$href = str_replace('%', '%25', $href);
	return assegurarHTML($href);
}

// Imprime uma nuvem de tags (com no o máximo de tags desejado)
function imprimirNuvemTags($num) {
	$nuvem = Query::query(false, NULL, 'SELECT t.nome, COUNT(*) AS num FROM tags AS t JOIN tagsEmPosts AS tEP ON tEP.tag=t.id GROUP BY t.id HAVING COUNT(*)>0 ORDER BY COUNT(*) DESC LIMIT ?', $num);
	if (!count($nuvem))
		return;
	echo '<div>';
	$max = $nuvem[0]['num'];
	foreach ($nuvem as $cada) {
		$href = getHref('tag', '', $cada['nome']);
		echo '<a class="tag' . round(5-4*$cada['num']/$max) . '" href="' . $href . '">' . assegurarHTML($cada['nome']) . '</a>';
	}
	echo '</div>';
}

// Retorna a parte da query que verifica a visibilidade de um item
// $tipo deve ser 'pasta', 'post', 'anexo' ou 'form'
function getQueryVisibilidade($tipo) {
	global $_usuario;
	if (!$_usuario)
		return $tipo=='form' ? 'ativo=1' : "visibilidade='publico'";
	else if ($_usuario['admin'])
		return "1";
	else if ($tipo == 'form')
		return "(ativo=1 OR criador=$_usuario[id])";
	else if ($tipo != 'anexo')
		return "(visibilidade='publico'
		OR visibilidade='geral'
		OR criador=$_usuario[id]
		OR EXISTS (SELECT * FROM visibilidades WHERE tipoItem='$tipo' AND item=id AND usuario=$_usuario[id]))";
	else
		return "(visibilidade='publico'
		OR visibilidade='geral'
		OR EXISTS (SELECT * FROM visibilidades WHERE tipoItem='anexo' AND item=id AND usuario=$_usuario[id])
		OR $_usuario[id]=(SELECT criador FROM posts WHERE id=post LIMIT 1))";
}

// Retorna a configuração de visibilidade de um item num formato legível
// $tipo é uma das strings: 'pasta', 'post', 'anexo'
function visibilidade2str($tipo, $id, $visibilidade, $idCriador) {
	// Cache dos nomes de usuários
	static $nomes = array();
	
	if ($visibilidade == 'publico')
		return 'Visível publicamente';
	else if ($visibilidade == 'geral')
		return 'Visível para todos os usuários logados';
	else {
		// Pega o nome do criador
		if (!isset($nomes[$idCriador]))
			$nomes[$idCriador] = Query::getValor('SELECT nome FROM usuarios WHERE id=? LIMIT 1', $idCriador);
		$criador = $nomes[$idCriador];
		
		$selecionados = Query::query(false, 0, 'SELECT u.nome FROM usuarios AS u JOIN visibilidades AS v ON v.usuario=u.id WHERE v.tipoItem=? AND v.item=? ORDER BY u.nome', $tipo, $id);
		if (count($selecionados))
			return 'Visível para somente para ' . implode(', ', $selecionados) . " e $criador";
		else
			return "Visível somente para $criador";
	}
}

// Imprime a representação visual (HTML) de um caminho
// $caminho deve ser uma string no formato '/a/b/c'
function imprimirCaminho($caminho) {
	if ($caminho == '')
		return;
	
	echo '<div class="caminho">';
	echo '<a href="' . getHref('pasta', '/', '') . '" class="botao">Raiz</a>';
	
	if ($caminho != '/') {
		$partes = explode('/', $caminho);
		$caminho = '';
		for ($i=1; $i<count($partes); $i++) {
			echo '<span class="caminho-divisao"></span>';
			echo '<a href="' . getHref('pasta', $caminho, $partes[$i]) . '" class="botao">' . assegurarHTML($partes[$i]) . '</a>';
			$caminho .= '/' . $partes[$i];
		}
	}
	
	echo '</div>';
}
