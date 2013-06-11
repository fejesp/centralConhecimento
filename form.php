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
$sucesso = interpretarCaminho($caminho, $dados, 'form');
if (!$sucesso)
	morrerComErro('Form não encontrado');

// Carrega a identificação do usuário
if ($_usuario) {
	$email = $_usuario['email'];
	$criador = $_usuario['id'];
	$EJ = NULL;
} else {
	$email = $_POST['email'];
	$EJ = $_POST['ej'];
	$criador = 1; // FEJESP
}

$nome = @$_POST['nome'];
$data = @$_POST['data'];

// Valida o nome
if (!preg_match('@^[^/]+$@', $nome))
	morrerComErro('Nome inválido');

// Valida a data (para garantir que o formulário não foi alterado de lá pra cá)
if ($data != $dados['data'])
	morrerComErro('O formulário foi alterado, por favor volte e responda o novo');

// Monta o conteúdo do post
$campos = $_POST['campos'];
$conteudo = array();
foreach (json_decode($dados['conteudo'], true) as $i=>$campo) {
	if (empty($campos[$i]))
		continue;
	if ($campo['tipo'] == 'checkbox') {
		$valores = array_keys($campos[$i]);
		for ($i=0; $i<count($valores); $i++)
			$valores[$i] = "-\t" . $valores[$i];
		$conteudo[] = "$campo[nome]:\n" . implode("\n", $valores);
	} else
		$conteudo[] = "$campo[nome]:\n" . $campos[$i];
}
$conteudo = implode("\n\n", $conteudo);

// Salva no banco de dados
try {
	// Cria o post
	new Query('INSERT INTO posts VALUES (NULL, ?, ?, ?, NOW(), "seleto", ?)', $dados['pasta'], $nome, $conteudo, $criador);
	$idPost = Query::$conexao->insert_id;
	
	// Vai para a pasta
	redirecionar('pasta' . getCaminhoAcima($caminho));
} catch (Exception $e) {
	morrerComErro('Falha ao gravar os dados: ' . $e->getMessage());
}