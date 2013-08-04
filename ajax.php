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
} else if ($op == 'getTags') {
	// Retorna todas as tags para o cliente (JS) sugerir as melhores ao usuário
	retornar(Query::query(false, 0, 'SELECT nome FROM tags'));
} else if ($op == 'preverHTML') {
	// Converte texto simples em HTML com base numa sintaxe parecida com markdown
	require_once 'gerarHTML.php';
	retornar(gerarHTML($_POST['str']));
} else if ($op == 'getDownloads') {
	// Retorna os nomes de quem baixou um anexo (pelo id) na forma de uma array
	// Cada elemento dessa array tem os índices "usuario", "email", "empresa" e "data"
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	$id = (int)$_GET['id'];
	retornar(Query::query(false, NULL, 'SELECT
		"" AS usuario, email, empresa, data
		FROM downloads
		WHERE anexo=? AND usuario IS NULL
		UNION ALL SELECT
		u.nome, NULL, NULL, d.data
		FROM usuarios AS u
		JOIN downloads AS d ON d.usuario=u.id
		WHERE anexo=? AND usuario IS NOT NULL
		ORDER BY data DESC', $id, $id));
} else if ($op == 'getLinkPost') {
	// Retorna o link completo para um post (pelo id)
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	
	// Pega o id da pasta
	$id = (int)$_GET['id'];
	$post = Query::query(true, NULL, 'SELECT nome, pasta FROM posts WHERE id=? LIMIT 1', $id);
	
	// Monta o caminho (de trás para frente)
	$caminho = '';
	$pasta = array('id' => $post['pasta']);
	while ($pasta['id']) {
		$pasta = Query::query(true, NULL, 'SELECT nome, pai FROM pastas WHERE id=? LIMIT 1', $pasta['id']);
		$caminho = $pasta['nome'] . '/' . $caminho;
		$pasta['id'] = $pasta['pai'];
	}
	$caminho = '/' . substr($caminho, 0, -1);
	
	// Retorna o caminho relativo a partir da raiz
	retornar(html_entity_decode(getHref('post', $caminho, $post['nome']), ENT_QUOTES, 'UTF-8'));
} else if ($op == 'getAnexos') {
	// Retorna os posts baixados por uma pessoa externa (pelo email+empresa)
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	$email = $_GET['email'];
	$empresa = $_GET['empresa'];
	retornar(Query::query(false, NULL, 'SELECT
	p.nome AS post, a.nome AS anexo, d.data AS data, a.id AS id, p.id AS idPost
	FROM posts AS p
	JOIN anexos AS a ON a.post=p.id
	JOIN downloads AS d ON d.anexo=a.id
	WHERE d.email=? AND d.empresa=?
	ORDER BY data DESC', $email, $empresa));
} else if ($op == 'getTodosDownloads') {
	// Retorna as estatísticas de todos os downloads
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	$query = 'SELECT
		a.id AS id, p.id AS idPost, p.nome AS post, a.nome AS anexo, COUNT(*) AS downloads
		FROM posts AS p
		JOIN anexos AS a ON a.post=p.id
		JOIN downloads AS d ON d.anexo=a.id
		WHERE d.usuario IS NULL OR d.usuario NOT IN (SELECT id FROM usuarios WHERE admin=1)
		GROUP BY a.id
		ORDER BY COUNT(*) DESC';
	retornar(Query::query(false, NULL, $query));
} else if ($op == 'getTodosDownloadsExternos') {
	// Retorna as estatísticas de todos os downloaders externos
	if (!$_usuario || !$_usuario['admin'])
		retornarErro();
	$query = 'SELECT
		email, empresa, COUNT(*) AS downloads
		FROM downloads
		WHERE usuario IS NULL
		GROUP BY email, empresa
		ORDER BY COUNT(*) DESC';
	retornar(Query::query(false, NULL, $query));
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
