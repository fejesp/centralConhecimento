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
function verificarVisibilidade($tipo, $id, $criador, $visibilidade) {
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
	if ($visibilidade == 'seleto' && Query::existe('SELECT 1 FROM visibilidades WHERE tipoItem=? AND item=? AND usuario=? LIMIT 1', $tipo, $pasta, $_usuario['id']))
		// Somente os usuários selecionados podem ver
		return true;
	return false;
}

// Verifica se uma dada pasta é visível para o usuário atual
// $pasta é o id da pasta a ser verificada (ela deve existir)
// $recursivo indica se a verificação deve ser feita com as pastas acima também
// Obs.: não usado ainda!!!
function pastaEVisivel($pasta, $recursivo=true) {
	global $_usuario;
	if ($_usuario && $_usuario['admin'])
		// Para um administrador, tudo é visível
		return true;
	if ($pasta === NULL)
		// Pasta raiz sempre é visível
		return true;
	$dados = Query::query(true, NULL, 'SELECT visibilidade, criador, pai FROM pastas WHERE id=? LIMIT 1', $pasta);
	if ($_usuario && $dados['criador'] == $_usuario['id'])
		// A pasta é visível para seu criador
		return $recursivo ? pastaEVisivel($dados['pai']) : true;
	if ($dados['visibilidade'] == 'publico')
		// Visibilidade pública
		return $recursivo ? pastaEVisivel($dados['pai']) : true;
	if (!$_usuario)
		// Não é público e o usuário não está logado
		return false;
	if ($dados['visibilidade'] == 'geral')
		// Todos os usuários podem ver
		return $recursivo ? pastaEVisivel($dados['pai']) : true;
	if ($dados['visibilidade'] == 'seleto' && Query::existe('SELECT 1 FROM visibilidades WHERE tipoItem="pasta" AND item=? AND usuario=? LIMIT 1', $pasta, $_usuario['id']))
		// Somente os usuários selecionados podem ver
		return $recursivo ? pastaEVisivel($dados['pai']) : true;
	return false;
}

// Imprime uma string na forma HTML de forma segura, dentro de uma tag opcional
// $tags é uma sequência de tags separada por espaço, cada tag pode ter uma classe associada após um ponto
// Exemplo: echoTag('oi', 'div.painel strong') => '<div class="painel"><strong>oi</strong></p>'
function imprimir($str, $tags='p') {
	$tags = explode(' ', strtolower($tags));
	$str = htmlentities($str, ENT_COMPAT, 'UTF-8');
	for ($i=count($tags)-1; $i>=0; $i--) {
		$tag = $tags[$i];
		if (($pos=strpos($tag, '.')) !== false) {
			$class = substr($tag, $pos+1);
			$tag = substr($tag, 0, $pos);
			$str = "<$tag class=\"$class\">$str</$tag>";
		} else
			$str = "<$tag>$str</$tag>";
	}
	echo $str;
}
