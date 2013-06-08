<?php
// Se já estiver logado, redireciona para a pasta principal
if ($_usuario)
	redirecionar('pasta');
?>
<div class="conteudoEsquerdo">
	<h2>Bem-vindo</h2>
	<p>Esta é a central de conhecimento da FEJESP - Federação de Empresas Juniores do Estado de São Paulo</p>
	<p>[Texto explicando sobre a central]</p>
</div>
<div class="conteudoDireito">
	<h2>Faça o login</h2>
	<form method="post" action="login.php">
		Email: <input type="email" size="30" autofocus name="email" required><br>
		Senha: <input type="password" name="senha" required><br>
		<input type="submit" style="display:none" id="submit">
		<input type="hidden" name="continuar" value="<?=@$_GET['continuar'];?>">
		<span class="botao" id="comLogin"><img src="/imgs/enviar.png"> Entrar</span>
	</form>
	<?php
	$erroLogin = (int)(@$_GET['erroLogin']);
	switch ($erroLogin) {
	case 1:
		echo '<p><strong>Erro no login</strong>: email não encontrado</p>';
		break;
	case 2:
		echo '<p><strong>Erro no login</strong>: conta inativa, contate o administrador</p>';
		break;
	case 3:
		echo '<p><strong>Erro no login</strong>: conta bloqueada temporariamente, aguarde alguns minutos</p>';
		break;
	case 4:
		echo '<p><strong>Erro no login</strong>: senha incorreta, esqueceu sua senha?</p>';
	}
	?>
	<p>ou <span class="botao" id="semLogin"><img src="/imgs/enviar.png"> entre sem login</span></p>
</div>
