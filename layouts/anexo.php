<?php
// Busca os dados do anexo
$caminho = $_GET['q'];
$anexo = NULL;
$sucesso = interpretarCaminho($caminho, $anexo, 'anexo');

if (!$sucesso) {
	// Anexo invisível ou não encontrado
	if (!$_usuario && ($anexo == 3 || $anexo == 5)) {
		// Como o usuário não está logado e o item está invisível para ele,
		// redireciona para a página de login
		redirecionar('index', '', '', 'itemInvisivel&continuar=' . urlencode('/anexo' . $caminho));
	} else {
		// Simplesmente mostra a mensagem de erro
		imprimir('Erro', 'h2');
		imprimir('Anexo não encontrado', 'p strong');
	}
	return;
}

// Processa o email e empresa, caso tenha enviado ou informado anteriormente
$info = NULL;
if (isset($_POST['email'])) {
	$info = array('email' => $_POST['email'], 'empresa' => @$_POST['empresa']);
	setcookie('central_email', $info['email'], 0, '/');
	setcookie('central_empresa', $info['empresa'], 0, '/');
} else if (isset($_COOKIE['central_email']))
	$info = array('email' => $_COOKIE['central_email'], 'empresa' => @$_COOKIE['central_empresa']);

// Se estiver logado ou já informou o email e a EJ, libera o download
if ($_usuario || $info) {
	// Busca o nome real do arquivo
	$arquivo = '';
	foreach (scandir("arquivos/$anexo[id]") as $cada)
		if ($cada != '.' && $cada != '..')
			$arquivo = "arquivos/$anexo[id]/$cada";
	if (!$arquivo) {
		imprimir('Erro', 'h2');
		imprimir('Anexo não encontrado', 'p strong');
		return;
	}
	
	// Grava a estatística
	if ($_usuario)
		new Query('INSERT INTO downloads VALUES (?, ?, NOW(), NULL, NULL)', $anexo['id'], $_usuario['id']);
	else
		new Query('INSERT INTO downloads VALUES (?, NULL, NOW(), ?, ?)', $anexo['id'], $info['email'], $info['empresa']);
	
	// Gera o download do anexo
	ob_end_clean();
	header('Content-Disposition: attachment; filename="' . $anexo['nome'] . '"');
	header('Content-Type: application/octet-stream'); // Deixa o MIME para o navegador decidir
	readfile($arquivo);
	exit;
}
?>
<h2>Download</h2>
<p>Se você possui cadastro no sistema, por favor faça o login</p>
<p>Se não, antes de fazer o download por favor nos diga seu email de contato e sua empresa júnior. Caso não seja um empresário júnior, basta deixar o campo em branco.</p>
<form action="" method="post">
<div class="rotuloEsquerdo">
	Email: <br>
	Empresa:
</div>
<div class="opcoesDireita">
	<input type="email" required size="30" autofocus name="email"><br>
	<input size="20" name="empresa">
</div>
<div class="clear"></div>
<input type="submit" style="display:none" id="submit">
<span class="botao" id="voltar" onclick="window.history.back()"><img src="/imgs/voltar.png"> Voltar</span>
<span class="botao" id="baixar" onclick="get('submit').click()"><img src="/imgs/enviar.png"> Baixar</span>
</form>
