<h2>Editar usuário</h2>
<?php
if (!$_usuario) {
	imprimir('Erro: usuário não encontrado', 'p strong');
	return;
}
$nome = assegurarHTML($_usuario['nome']);
$email = assegurarHTML($_usuario['email']);
?>

<script>
setBotao("enviar", function () {
	get("submit").click()
})
</script>

<form method="post" action="/editarUsuario.php">
<div class="rotuloEsquerdo">
	Senha antiga:<br>
	<br>
	Nome:<br>
	Email:<br>
	Nova senha:<br>
	Repetir nova senha:
</div>
<div class="opcoesDireita">
	<input name="senha" id="senha" type="password" required autofocus><br>
	<br>
	<input name="nome" id="nome" type="text" value="<?=$nome?>" size="30"><br>
	<input name="email" id="email" type="email" value="<?=$email?>" size="30"><br>
	<input name="novaSenha" id="novaSenha" type="password"><br>
	<input name="novaSenha2" id="novaSenha2" type="password">
</div>
<div class="clear"></div>
<input type="submit" style="display:none" id="submit">
<span class="botao" id="enviar"><img src="/imgs/enviar.png"> Alterar</span>
</form>
