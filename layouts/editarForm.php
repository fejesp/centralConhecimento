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
	$dados['conteudo'] = '';
	$dados['ativo'] = 1;
	$dados['criador'] = $_usuario['id'];
}
$nomeHTML = assegurarHTML($dados['nome']);
$descricaoHTML = assegurarHTML($dados['descricao']);
?>
<form method="post" action="/editarForm.php<?=$criar ? '?criar' : ''?>" enctype="multipart/form-data" id="form">
<p><label for="nome">Nome:</label> <input size="30" name="nome" id="nome" required pattern="[^/]+" value="<?=$nomeHTML?>" autofocus></p>
<p><label for="descricao">Conteúdo:</label><br>
<textarea name="descricao" id="descricao"><?=$descricaoHTML?></textarea></p>
<p><input type="checkbox" name="ativo" id="ativo"<?=$dados['ativo'] ? ' checked' : ''?>> <label for="ativo">Formulário ativo (aceitando respostas)</labeL></p>
<input type="hidden" name="caminho" value="<?=assegurarHTML($caminho)?>">

<h2>Campos</h2>
<div class="acoes"><span class="botao" id="adicionarCampo"><img src="/imgs/adicionarCampo.png"> Adicionar campo</span></div>
<div id="campos" class="campos">
</div>

<input type="submit" style="display:none" id="submit">
<span class="botao" id="voltar"><img src="/imgs/voltar.png"> Voltar</span>
<span class="botao" id="salvar"><img src="/imgs/enviar.png"> <?=$criar ? 'Criar' : 'Salvar'?></span>
</form>
