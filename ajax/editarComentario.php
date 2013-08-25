<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 25/08/2013
*/

// Edita/cria um comentário
$post = $_POST['post']; // Caminho do post
$id = (int)$_POST['id']; // Id do comentário (0 indica novo comentário)
$conteudo = $_POST['conteudo']; // Conteúdo do comentário

// Busca o post
$dados = NULL;
$sucesso = interpretarCaminho($post, $dados, 'post');
if (!$sucesso || !$_usuario)
	retornarErro();

$conteudoHTML = gerarHTML($conteudo);

if ($id) {
	// Edita um comentário
	$criador = Query::getValor('SELECT criador FROM comentarios WHERE post=? AND id=? LIMIT 1', $dados['id'], $id);
	if (!$criador || (!$_usuario['admin'] && $criador != $_usuario['id']))
		retornarErro();
	new Query('UPDATE comentarios SET conteudo=?, modificacao=NOW() WHERE id=? LIMIT 1', $conteudo, $id);
} else {
	// Cria um comentário
	new Query('INSERT INTO comentarios VALUES (NULL, ?, ?, NOW(), NOW(), ?)', $dados['id'], $conteudo, $_usuario['id']);
	$id = Query::$conexao->insert_id;
	
	// Pega o email de todos os envolvidos no post (criador do post e criadores dos comentários)
	$emails = Query::query(false, 0, 'SELECT email FROM usuarios WHERE id IN (
		SELECT criador FROM comentarios WHERE post=? UNION SELECT ?
	) AND id != ? ORDER BY nome', $dados['id'], $dados['criador'], $_usuario['id']);
	
	if (count($emails)) {
		// Envia o email
		
		$emails = implode(',', $emails);
		$assunto = '[FEJESP][Central de conhecimento] Novo comentário em ' . $dados['nome'];
		$link = getHref('post', getCaminhoAcima($post), $dados['nome'], true);
		$mensagem = "<p>" . assegurarHTML($_usuario['nome']) . " comentou no post " . assegurarHTML($dados['nome']) . ":</p>
		
		<blockquote>$conteudoHTML</blockquote>
		
		<p><a href='$link'>Clique aqui</a> para acessar a central e responder ao comentário</p>
		
		<p style='font-size:smaller'>Você recebeu este email por estar envolvido na discussão deste post</p>
		
		<p>Att,<br>
		Núcleo de TI - FEJESP</p>";
		$cabecalhos = "From: ti@fejesp.org.br\r\nContent-type: text/html; charset=UTF-8";
		mail($emails, $assunto, $mensagem, $cabecalhos, '-r ti@fejesp.org.br');
	}
}

retornar(array('id' => $id, 'conteudo' => $conteudoHTML));

