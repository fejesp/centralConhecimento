<h2>Editar usuário</h2>
<?php
if (!$_usuario)
	redirecionar('index', '', '', 'continuar=editarUsuario');
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

<h3>Uso do espaço</h3>
<?php
// Mede o espaço utilizado
$usado = Query::getValor('SELECT SUM(a.tamanho) FROM anexos AS a JOIN posts AS p ON a.post=p.id JOIN usuarios AS u ON p.criador=u.id WHERE u.id=?', $_usuario['id']);
$total = $_usuario['usoMax'];
if ($total) {
	// Com espaço máximo definido
	$livre = $total-$usado;
	$porcemUsado = round(100*$usado/$total);
	echo "<div class='espacoTotal'><div class='espacoUsado' style='width:$porcemUsado%'>$porcemUsado%</div></div>
	<p><span class='legendaUso'>@</span> Espaço utilizado: " . kiB2str($usado) . "<br>
	<span class='legendaLivre'>@</span> Espaço livre: " . kiB2str($livre) . "</p>";
} else {
	// Sem limite de espaço
	echo "<p>Espaço utilizado: " . kiB2str($usado) . "</p>";
}
?>
