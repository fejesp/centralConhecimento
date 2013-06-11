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
$sucesso = interpretarCaminho($caminho, $dados, $criar ? 'pasta' : 'form');
if (!$sucesso)
	morrerComErro('Form não encontrado');
	
// Valida as permissões do usuário
if (!$_usuario || (!$criar && !$_usuario['admin'] && $dados['criador'] != $_usuario['id']))
	morrerComErro('O usuário atual não tem permissão para isso');

// Carrega os novos dados
$nome = $_POST['nome'];
$descricao = $_POST['descricao'];
$ativo = !empty($_POST['ativo']);

// Valida o nome
if (!preg_match('@^[^/]+$@', $nome))
	morrerComErro('Nome inválido');

// Monta a definição (conteudo) do formulário
$campos = array();
foreach ($_POST['campos'] as $cada) {
	list($idCampo, $tipoCampo) = explode(':', $cada);
	$campo = array('tipo' => $tipoCampo);
	$campo['nome'] = $_POST['nomes'][$idCampo];
	$campo['obrigatorio'] = !empty($_POST['obrigatorio'][$idCampo]);
	if ($tipoCampo == 'radio' || $tipoCampo == 'checkbox')
		$campo['valores'] = explode("\n", str_replace("\r", '', $_POST['valores'][$idCampo]));
	$campos[] = $campo;
}
$conteudo = json_encode($campos);

// Salva no banco de dados
try {
	if ($criar)
		new Query('INSERT INTO forms VALUES (NULL, ?, ?, ?, ?, NOW(), ?, ?)', $dados['id'], $nome, $descricao, $conteudo, $ativo, $_usuario['id']);
	else
		new Query('UPDATE forms SET nome=?, descricao=?, conteudo=?, data=NOW(), ativo=? WHERE id=? LIMIT 1', $nome, $descricao, $conteudo, $ativo, $dados['id']);
		
	// Vai para o form
	redirecionar('pasta' . ($criar ? $caminho : getCaminhoAcima($caminho)));
} catch (Exception $e) {
	morrerComErro('Falha ao gravar os dados: ' . $e->getMessage());
}
