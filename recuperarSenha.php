<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 08/06/2013
*/

// Recupera a senha de um usuário

// A processo ocorre em duas etapas:
// 1. O sistema envia para o usuário um email com um link
// 2. O sistema gera um nova senha e a envia para o usuário por email (em gerarSenha.php)

// Inclui os arquivos básicos e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();

// Pega o usuário pelo email
$email = @$_GET['email'];
try {
	$dados = Query::query(true, NULL, 'SELECT nome, id, senha FROM usuarios WHERE email=? LIMIT 1', $email);
} catch (Exception $e) {
	morrerComErro('Email não encontrado');
}

// Envia um email para o usuário com seu id e sua senha criptografada (duas vezes)
$assunto = '[FEJESP][Central de conhecimento] Recuperação de senha';
$link = $_config['urlBase'] . 'gerarSenha.php?id=' . $dados['id'] . '&chave=' . md5($dados['senha']);
$mensagem = "<p>Olá " . assegurarHTML($dados['nome']) . ",</p>

<p>Recebemos um pedido de recuperação de senha da sua conta na central de conhecimento da FEJESP.<br>
Caso você tenha efetuado esse pedido, <a href='$link'>clique aqui</a> para receber uma nova senha.<br>
Em qualquer outro caso, basta ignorar este email.</p>

<p>Att,<br>
Núcleo de TI - FEJESP</p>";
$cabecalhos = "From: ti@fejesp.org.br\r\nContent-type: text/html; charset=UTF-8";
mail($email, $assunto, $mensagem, $cabecalhos, '-r ti@fejesp.org.br');

// Retorna para a página inicial
redirecionar('index', '', '', 'senhaRecuperada');
