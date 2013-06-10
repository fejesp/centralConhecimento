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

// Salva os novos dados
$novosAnexos = array();
$anexosRemovidos = array();
try {
	// Salva/cria o post
	Query::$conexao->autocommit(false);
	if ($criar) {
		new Query('INSERT INTO posts VALUES (NULL, ?, ?, ?, NOW(), ?, ?)', $dados['id'], $nome, $conteudo, $visibilidade, $_usuario['id']);
		$dados['id'] = Query::$conexao->insert_id;
		$dados['criador'] = $_usuario['id'];
	} else {
		new Query('UPDATE posts SET nome=?, conteudo=?, data=NOW(), visibilidade=? WHERE id=? LIMIT 1', $nome, $conteudo, $visibilidade, $dados['id']);
		new Query('DELETE FROM visibilidades WHERE tipoItem="post" AND item=?', $dados['id']);
	}
	for ($i=0; $i<count($selecionados); $i++)
		if ((int)$selecionados[$i] != $dados['criador'])
			new Query('INSERT INTO visibilidades VALUES ("post", ?, ?)', $dados['id'], (int)$selecionados[$i]);
	
	// Remove os anexos selecionados
	if (isset($_POST['removidos']))
		foreach ($_POST['removidos'] as $cada) {
			new Query();
			$anexosRemovidos[] = $cada; // TODO: parei aqui!!!
		}
	
	// Trata os novos anexos
	if (isset($_FILES['arquivos'])) {
		$nomes = $_FILES['arquivos']['name'];
		$tmp_names = $_FILES['arquivos']['tmp_name'];
		$erros = $_FILES['arquivos']['error'];
		$tamanhos = $_FILES['arquivos']['size'];
		$infos = $_POST['infos'];
		foreach ($nomes as $idAnexo=>$nome) {
			if ($erros[$idAnexo])
				morrerComErro('Falha no upload do arquivo ' . $nome);
			
			// Insere no BD
			$visibilidade = $infos[$idAnexo];
			if (substr($visibilidade, 0, 6) == 'seleto') {
				$selecionados = json_decode(substr($visibilidade, 6));
				$visibilidade = 'seleto';
			}
			new Query('INSERT INTO anexos VALUES (NULL, ?, ?, ?, ?)', $nome, $dados['id'], $visibilidade, round($tamanhos[$idAnexo]/1024));
			$id = Query::$conexao->insert_id;
			if ($visibilidade == 'seleto')
				for ($i=0; $i<count($selecionados); $i++)
					new Query('INSERT INTO visibilidades VALUES ("anexo", ?, ?)', $id, $selecionados[$i]);
			
			// Move para a pasta correta
			$novosAnexos[] = array("arquivos/$id", "arquivos/$id/$nome");
			mkdir("arquivos/$id");
			move_uploaded_file($tmp_names[$idAnexo], "arquivos/$id/$nome");
		}
	}
	
	Query::$conexao->commit();
	
	// Tudo ok, volta para a página anterior
	redirecionar('post' . ($criar ? $caminho . '/' . $nome : $caminho));
} catch (Exception $e) {
	// Elimina todos os novos anexos das pastas
	foreach ($novosAnexos as $cada) {
		unlink($cada[1]);
		rmdir($cada[0]);
	}
	
	morrerComErro('Falha ao gravar os dados: ' . $e);
}
