<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 16/07/2013
*/

// Recupera a senha de um usuário

// A processo ocorre em duas etapas:
// 1. O sistema envia para o usuário um email com um link (em recuperarSenha.php)
// 2. O sistema gera um nova senha e a envia para o usuário por email

// Inclui os arquivos básicos e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();

// Valida a combinação id/chave
$id = (int)@$_GET['id'];
$chave = @$_GET['chave'];
try {
	$dados = Query::query(true, NULL, 'SELECT nome, senha, email FROM usuarios WHERE id=? LIMIT 1', $id);
} catch (Exception $e) {
	morrerComErro('Usuário não encontrado');
}

if ($chave != md5($dados['senha']))
	morrerComErro('Chave inválida');

// Gera uma nova senha
$nova = gerarSenha();
new Query('UPDATE usuarios SET senha=? WHERE id=? LIMIT 1', md5($nova), $id);
$assunto = '[FEJESP][Central de conhecimento] Recuperação de senha';
$link = $_config['urlBase'] . 'editarUsuario';
$mensagem = "<p>Olá " . assegurarHTML($dados['nome']) . ",</p>

<p>Uma nova senha foi gerada para sua conta: <strong>$nova</strong><br>
Para acessar a central e alterá-la <a href='$link'>clique aqui</a>.</p>

<p>Att,<br>
Núcleo de TI - FEJESP</p>";
$cabecalhos = "From: ti@fejesp.org.br\r\nContent-type: text/html; charset=UTF-8";
mail($dados['email'], $assunto, $mensagem, $cabecalhos, '-r ti@fejesp.org.br');

redirecionar('index', '', '', 'senhaRecuperada2');
