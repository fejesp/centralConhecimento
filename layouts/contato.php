<?php
if (isset($_POST['nome'])) {
	$nome = assegurarHTML($_POST['nome']);
	$email = $_POST['email'];
	$emailHTML = assegurarHTML($email);
	$mensagem = nl2br(assegurarHTML($_POST['mensagem']));
	$assunto = '[FEJESP][Central de conhecimento] Contato';
	$mensagem = "<p><strong>Nome</strong>: $nome</p>
	<p><strong>Email</strong>: $emailHTML</p>
	<p><strong>Mensagem</strong>: $mensagem</p>";
	$cabecalhos = "From: ti@fejesp.org.br\r\nReply-to:$email\r\nContent-type: text/html; charset=UTF-8";
	mail('ti@fejesp.org.br', $assunto, $mensagem, $cabecalhos);
	redirecionar('index');
}
?>
<h2>Contato</h2>
<p>Entre em contato com a equipe FEJESP. Estamos sempre dispostos a ajudar e abertos a sugestões</p>
<form method="post">
<div class="rotuloEsquerdo">
<p>Seu nome:</p>
<p>Seu email:</p>
</div>
<div class="opcoesDireita">
<p><input name="nome" size="30" required autofocus></p>
<p><input name="email" size="30" type="email" required></p>
</div>
<p class="clear">Mensagem:<br>
<textarea name="mensagem" required></textarea></p>
<input type="submit" style="display:none" id="submit">
<span class="botao" onclick="get('submit').click()"><img src="/imgs/enviar.png"> Enviar</span>
</form>
