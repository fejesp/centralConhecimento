<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Carrega as configurações e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();
validarLogin();

// Interpreta o caminho do item
$dados = NULL;
$caminho = @$_POST['caminho'];
$criar = isset($_GET['criar']);
$sucesso = interpretarCaminho($caminho, $dados, $criar ? 'pasta' : 'post');
if (!$sucesso)
	morrerComErro('Post não encontrado');
	
// Valida as permissões do usuário
if (!$_usuario || (!$criar && !$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	morrerComErro('O usuário atual não tem permissão para isso');

// Carrega os novos dados
$nome = $_POST['nome'];
$conteudo = $_POST['conteudo'];
$visibilidade = $_POST['visibilidade'];
$selecionados = isset($_POST['selecionados']) ? $_POST['selecionados'] : array();

// Valida o nome
if (!preg_match('@^[^/]+$@', $nome))
	morrerComErro('Nome inválido');

// O processo de criar/alterar, se falhar, deve falhar como um todo
// Assim, primeiro é alterado o banco de dados e por último o sistema de arquivos

// Salva as mudanças dos anexos
$novosAnexos = array();
$anexosRemovidos = array();

// Tenta fazer todas as alterações no banco de dados
Query::$conexao->autocommit(false);
try {
	// Altera/cria o básico do post
	if ($criar) {
		new Query('INSERT INTO posts VALUES (NULL, ?, ?, ?, NOW(), ?, ?)', $dados['id'], $nome, $conteudo, $visibilidade, $_usuario['id']);
		$dados['id'] = Query::$conexao->insert_id;
		$dados['criador'] = $_usuario['id'];
		$meusAnexos = array();
	} else {
		new Query('UPDATE posts SET nome=?, conteudo=?, data=NOW(), visibilidade=? WHERE id=? LIMIT 1', $nome, $conteudo, $visibilidade, $dados['id']);
		new Query('DELETE FROM visibilidades WHERE tipoItem="post" AND item=?', $dados['id']);
		$meusAnexos = Query::query(false, 0, 'SELECT id FROM anexos WHERE post=?', $dados['id']);
	}
	
	// Insere a seleção de visibilidade
	for ($i=0; $i<count($selecionados); $i++)
		if ((int)$selecionados[$i] != $dados['criador'])
			new Query('INSERT INTO visibilidades VALUES ("post", ?, ?)', $dados['id'], (int)$selecionados[$i]);
	
	// Remove os anexos selecionados
	if (isset($_POST['removidos']))
		foreach ($_POST['removidos'] as $id=>$temp) {
			$id = (int)$id;
			if (!in_array($id, $meusAnexos))
				throw new ErrorException('Tentativa inválida de remover um anexo');
			new Query('DELETE FROM anexos WHERE id=? LIMIT 1', $id);
			$anexosRemovidos[] = $id;
		}
	
	// Atualiza a visibilidade dos anexos alterados
	if (isset($_POST['mudancas']))
		foreach ($_POST['mudancas'] as $id=>$cada) {
			$id = (int)$id;
			if (!in_array($id, $meusAnexos) || in_array($id, $anexosRemovidos))
				throw new ErrorException('Tentativa inválida de alterar um anexo');
			new Query('DELETE FROM visibilidades WHERE tipoItem="anexo" AND item=?', $id);
			$visibilidade = $cada;
			if (substr($visibilidade, 0, 6) == 'seleto') {
				foreach (json_decode(substr($visibilidade, 6)) as $cada2)
					new Query('INSERT INTO visibilidades VALUES ("anexo", ?, ?)', $id, $cada2);
				$visibilidade = 'seleto';
			}
			new Query('UPDATE anexos SET visibilidade=? WHERE id=? LIMIT 1', $visibilidade, $id);
		}
	
	// Trata os novos anexos
	if (isset($_FILES['arquivos'])) {
		$cotaUsada = Query::getValor('SELECT SUM(a.tamanho) FROM anexos AS a JOIN posts AS p ON a.post=p.id WHERE p.criador=?', $_usuario['id']);
		$cotaLivre = $_usuario['usoMax']-$cotaUsada;
		$nomes = $_FILES['arquivos']['name'];
		$tmp_names = $_FILES['arquivos']['tmp_name'];
		$erros = $_FILES['arquivos']['error'];
		$tamanhos = $_FILES['arquivos']['size'];
		$infos = $_POST['infos'];
		foreach ($nomes as $idAnexo=>$nomeAnexo) {
			if ($erros[$idAnexo])
				throw new ErrorException('Falha no upload do arquivo ' . $nomeAnexo);
			
			// Verifica se não passa da cota
			$tamanho = round($tamanhos[$idAnexo]/1024);
			$cotaLivre -= $tamanho;
			if ($_usuario['usoMax'] && $cotaLivre < 0)
				throw new ErrorException('O usuário não possui mais cota livre');
			
			// Insere no BD
			$visibilidade = $infos[$idAnexo];
			if (substr($visibilidade, 0, 6) == 'seleto') {
				$selecionados = json_decode(substr($visibilidade, 6));
				$visibilidade = 'seleto';
			}
			new Query('INSERT INTO anexos VALUES (NULL, ?, ?, ?, ?)', $nomeAnexo, $dados['id'], $visibilidade, $tamanho);
			$id = Query::$conexao->insert_id;
			if ($visibilidade == 'seleto')
				for ($i=0; $i<count($selecionados); $i++)
					new Query('INSERT INTO visibilidades VALUES ("anexo", ?, ?)', $id, $selecionados[$i]);
			
			// Marca para mover depois
			$novosAnexos[] = array($id, $nomeAnexo, $tmp_names[$idAnexo]);
		}
	}
	
	Query::$conexao->commit();
	
	// Agora que tudo no banco de dados correu bem, altera os arquivos no servidor
	foreach ($anexosRemovidos as $cada)
		unlinkAnexo($cada);
	foreach ($novosAnexos as $cada) {
		mkdir("arquivos/$cada[0]");
		move_uploaded_file($cada[2], "arquivos/$cada[0]/$cada[1]");
	}
	
	// Tudo ok, volta para a página anterior
	redirecionar('post' . ($criar ? $caminho . '/' . $nome : $caminho));
} catch (Exception $e) {
	Query::$conexao->rollback();
	morrerComErro('Falha ao gravar os dados: ' . $e->getMessage());
}
