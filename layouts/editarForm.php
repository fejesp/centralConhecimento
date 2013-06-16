<?php
// Interpreta o caminho da pasta
$dados = NULL;
$caminho = $_GET['q'];
$criar = isset($_GET['criar']);
$sucesso = interpretarCaminho($caminho, $dados, $criar ? 'pasta' : 'form');
imprimir($criar ? 'Criar formulário' : 'Editar formulário', 'h2');
if (!$sucesso) {
	imprimir('Erro: formulário não encontrado', 'p strong');
	return;
}

gerarJSVar('_caminho', $criar ? $caminho : getCaminhoAcima($caminho));
	
// Valida as permissões do usuário
if (!$_usuario || (!$criar && !$_usuario['admin'] && $dados['criador'] != $_usuario['id'])) {
	imprimir('Erro: o usuário atual não tem permissão para isso', 'p strong');
	return;
}

// Trata os parâmetros para serem HTML seguro
if ($criar) {
	$dados['nome'] = '';
	$dados['descricao'] = '';
	$dados['conteudo'] = '[]';
	$dados['ativo'] = 1;
	$dados['criador'] = $_usuario['id'];
}
$nomeHTML = assegurarHTML($dados['nome']);
$descricaoHTML = assegurarHTML($dados['descricao']);
?>
<form method="post" action="/editarForm.php<?=$criar ? '?criar' : ''?>" enctype="multipart/form-data" id="form">
<p><label for="nome">Nome:</label> <input size="30" name="nome" id="nome" required pattern="[^/]+" value="<?=$nomeHTML?>" autofocus></p>
<p><label for="descricao">Descrição:</label><br>
<textarea name="descricao" id="descricao"><?=$descricaoHTML?></textarea><br>
<span style="font-size:smaller">Você pode usar cabeçalhos, negrito e outros recursos. <a href="/ajudaHTML" target="_blank">Saiba mais</a></span></p>
<p><input type="checkbox" name="ativo" id="ativo"<?=$dados['ativo'] ? ' checked' : ''?>> <label for="ativo">Formulário ativo (aceitando respostas)</labeL></p>
<input type="hidden" name="caminho" value="<?=assegurarHTML($caminho)?>">

<h2>Campos</h2>
<div class="acoes"><span class="botao" id="adicionarCampo"><img src="/imgs/adicionarCampo.png"> Adicionar campo</span></div>
<div id="campos" class="campos"><?php
foreach (json_decode($dados['conteudo'], true) as $campo) {
	$idCampo = gerarSenha();
	$tipoCampo = $campo['tipo'];
	$tituloCampo = $tipoCampo=='input' ? 'Texto' : ($tipoCampo=='textarea' ? 'Texto longo' : ($tipoCampo=='radio' ? 'Múltipla escolha' : 'Checkboxes'));
	$nomeCampo = assegurarHTML($campo['nome']);
	$campoObrigatorio = $campo['obrigatorio'] ? ' checked' : '';
	echo '<div class="campo">
	<div class="campo-acoes">
	<span class="botao" onclick="moverCampoAcima(this)"><img src="/imgs/praCima.png"> Mover para cima</span>
	<span class="botao" onclick="moverCampoAbaixo(this)"><img src="/imgs/praBaixo.png"> Mover para baixo</span>
	<span class="botao" onclick="removerCampo(this)"><img src="/imgs/remover.png"> Remover</span>
	</div>';
	echo "<p><strong>$tituloCampo</strong></p>";
	echo "<p>Título da questão: <input size='40' name='nomes[$idCampo]' value='$nomeCampo' required> ";
	echo "<input type='checkbox' id='campo$idCampo' name='obrigatorio[$idCampo]'$campoObrigatorio> <label for='campo$idCampo'>Preenchimento obrigatório</label>";
	echo "<input type='hidden' name='campos[]' value='$idCampo:$tipoCampo'></p>";
	if ($tipoCampo == 'radio' || $tipoCampo == 'checkbox') {
		$valoresCampo = assegurarHTML(implode("\n", $campo['valores']));
		echo "<p>Digite as opções, uma por linha:<br><textarea required name='valores[$idCampo]'>$valoresCampo</textarea></p>";
	}
	echo '</div>';
}
?></div>

<input type="submit" style="display:none" id="submit">
<span class="botao" id="voltar"><img src="/imgs/voltar.png"> Voltar</span>
<span class="botao" id="salvar"><img src="/imgs/enviar.png"> <?=$criar ? 'Criar' : 'Salvar'?></span>
</form>
