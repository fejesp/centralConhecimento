<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 07/06/2013
*/

// Responde às chamadas em AJAX

// Carrega as configurações e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();
validarLogin();

// Vai para a rotina correta
$op = @$_GET['op'];
if ($op == 'getArvoreInicial') {
	// Retorna a árvore inicial para um dado caminho
	$caminho = @$_GET['caminho'];
	$pastas = preg_split('@/@', $caminho, -1, PREG_SPLIT_NO_EMPTY);
	$arvore = array('' => NULL);
	$nivelAtual = &$arvore[''];
	$pai = 0;
	
	for ($i=0; $i<count($pastas); $i++) {
		// Carrega cada nível da árvore, a partir da raiz
		$nivelAtual = array();
		$subpastas = Query::query(false, NULL, 'SELECT id, nome, visibilidade, criador FROM pastas WHERE pai=? AND id!=0 ORDER BY nome', $pai);
		$achou = false;
		foreach ($subpastas as $dados)
			if (verificarVisibilidade('pasta', $dados['id'], $dados['visibilidade'], $dados['criador'])) {
				$nivelAtual[$dados['nome']] = NULL;
				if ($dados['nome'] == $pastas[$i]) {
					$achou = true;
					$pai = $dados['id'];
				}
			}
		
		// Verifica se a pasta está no caminho
		if (!$achou)
			retornarErro();
		$nivelAtual = &$nivelAtual[$pastas[$i]];
	}
	
	retornar($arvore);
} else if ($op == 'getSubPastas') {
	// Interpreta o caminho da pasta
	$dados = NULL;
	$caminho = @$_GET['caminho'];
	$sucesso = interpretarCaminho($caminho, $dados);
	
	if (!$sucesso)
		retornarErro();
	
	// Carrega cada nível da árvore, a partir da raiz
	$subpastas = Query::query(false, NULL, 'SELECT id, nome, visibilidade, criador FROM pastas WHERE pai=? AND id!=0 ORDER BY nome', $dados['id']);
	$visiveis = array();
	foreach ($subpastas as $dados)
		if (verificarVisibilidade('pasta', $dados['id'], $dados['visibilidade'], $dados['criador']))
			$visiveis[$dados['nome']] = NULL;
	
	retornar($visiveis);
} else if ($op == 'ativarUsuario') {
	// Ativa uma conta de usuário
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	$id = (int)@$_GET['id'];
	if ($id != $_usuario['id'])
		new Query('UPDATE usuarios SET ativo=1 WHERE id=? LIMIT 1', $id);
	retornar(true);
} else if ($op == 'desativarUsuario') {
	// Desativa uma conta de usuário
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	$id = (int)@$_GET['id'];
	if ($id != $_usuario['id'])
		new Query('UPDATE usuarios SET ativo=0 WHERE id=? LIMIT 1', $id);
	retornar(false);
} else if ($op == 'gerarSenha') {
	// Gera uma nova senha para uma conta de usuário
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	$id = (int)@$_GET['id'];
	$senha = gerarSenha();
	if ($id != $_usuario['id'])
		new Query('UPDATE usuarios SET senha=? WHERE id=? LIMIT 1', md5($senha), $id);
	retornar($senha);
} else if ($op == 'gerarLink') {
	// Retorna o link para gera uma nova senha para uma conta de usuário
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	$id = (int)@$_GET['id'];
	$chave = md5(Query::getValor('SELECT senha FROM usuarios WHERE id=? LIMIT 1', $id));
	$link = $_config['urlBase'] . "gerarSenha.php?id=$id&chave=$chave";
	retornar($link);
} else if ($op == 'ativarForm') {
	$caminho = $_GET['caminho'];
	$dados = NULL;
	$sucesso = interpretarCaminho($caminho, $dados, 'form');
	if ($sucesso && $_usuario && ($_usuario['admin'] || $dados['criador']==$_usuario['id'])) {
		new Query('UPDATE forms SET ativo=1 WHERE id=? LIMIT 1', $dados['id']);
	} else
		retornarErro();
} else if ($op == 'desativarForm') {
	$caminho = $_GET['caminho'];
	$dados = NULL;
	$sucesso = interpretarCaminho($caminho, $dados, 'form');
	if ($sucesso && $_usuario && ($_usuario['admin'] || $dados['criador']==$_usuario['id'])) {
		new Query('UPDATE forms SET ativo=0 WHERE id=? LIMIT 1', $dados['id']);
	} else
		retornarErro();
} else if ($op == 'excluirForm') {
	$caminho = $_GET['caminho'];
	$dados = NULL;
	$sucesso = interpretarCaminho($caminho, $dados, 'form');
	if ($sucesso && $_usuario && ($_usuario['admin'] || $dados['criador']==$_usuario['id'])) {
		new Query('DELETE FROM forms WHERE id=? LIMIT 1', $dados['id']);
	} else
		retornarErro();
} else if ($op == 'sugerirTags') {
	// Busca tags que comecem ou contenham $_GET['str']
	retornar(Query::query(false, 0, 'SELECT nome, (nome LIKE ?) AS ini FROM tags WHERE nome LIKE ? ORDER BY ini DESC, nome LIMIT 5', "$_GET[str]%", "%$_GET[str]%"));
} else
	retornarErro();

// Gera um erro com o status 400
function retornarErro() {
	header('HTTP/1.1 400 Bad Request');
	exit;
}

// Retorna o valor com status de sucesso
function retornar($valor) {
	echo json_encode($valor);
	exit;
}
