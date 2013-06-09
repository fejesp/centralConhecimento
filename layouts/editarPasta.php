<?php
// Interpreta o caminho da pasta
$dados = NULL;
$caminho = $_GET['q'];
$criar = isset($_GET['criar']);
$sucesso = interpretarCaminho($caminho, $dados);
if (!$sucesso || (!$criar && !$dados['id'])) {
	imprimir($criar ? 'Criar pasta' : 'Editar pasta', 'h2');
	imprimir('Erro: pasta não encontrada', 'p strong');
	return;
}

imprimir($criar ? 'Criar pasta' : 'Editar pasta ' . $dados['nome'], 'h2');
gerarJSVar('caminho', $caminho);
	
// Valida as permissões do usuário
if (!$_usuario || (!$criar && !$_usuario['admin'] && $dados['criador'] != $_usuario['id'])) {
	imprimir('Erro: o usuário atual não tem permissão para isso', 'p strong');
	return;
}

// Trata os parâmetros para serem HTML seguro
if ($criar) {
	$dados['nome'] = '';
	$dados['descricao'] = '';
	$dados['visibilidade'] = 'publico';
	$dados['criador'] = $_usuario['id'];
}
$nomeHTML = assegurarHTML($dados['nome']);
$descricaoHTML = assegurarHTML($dados['descricao']);
$radio1 = $dados['visibilidade']=='publico' ? ' checked' : '';
$radio2 = $dados['visibilidade']=='geral' ? ' checked' : '';
$radio3 = $dados['visibilidade']=='seleto' ? ' checked' : '';
?>
<form method="post" action="/editarPasta.php<?=$criar ? '?criar' : ''?>">
<p><label for="nome">Nome:</label> <input size="30" name="nome" id="nome" required pattern="[^/]+" value="<?=$nomeHTML?>" autofocus></p>
<p><label for="descricao">Descrição:</label><br>
<textarea name="descricao" id="descricao"><?=$descricaoHTML?></textarea></p>
<p class="rotuloEsquerdo">Visibilidade:</p>
<p class="opcoesDireita">
<input type="radio" name="visibilidade" value="publico" id="publico"<?=$radio1?>> <label for="publico">para qualquer um</label><br>
<input type="radio" name="visibilidade" value="geral" id="geral"<?=$radio2?>> <label for="geral">para qualquer usuário logado</label><br>
<input type="radio" name="visibilidade" value="seleto" id="seleto"<?=$radio3?>> <label for="seleto">para um grupo definido de usuários</label><br>
</p>
<div class="opcoesDireita" id="lista">
	<p>Usuários permitidos:</p>
	<?php
	// Imprime uma lista dos usuários
	$usuarios = Query::query(false, NULL, 'SELECT id, nome FROM usuarios ORDER BY nome');
	$selecionados = $criar ? array() : Query::query(false, 0, 'SELECT usuario FROM visibilidades WHERE tipoItem="pasta" AND item=?', $dados['id']);
	for ($i=0; $i<count($usuarios); $i++) {
		$usuario = $usuarios[$i];
		if ($usuario['id'] == $dados['criador'])
			$selecionado = ' checked disabled';
		else if (in_array($usuario['id'], $selecionados))
			$selecionado = ' checked';
		else
			$selecionado = '';
		echo '<input type="checkbox" value="' . $usuario['id'] . '" id="usuario' . $usuario['id'];
		echo '" name="selecionados[]"' . $selecionado . '> ';
		echo '<label for="usuario' . $usuario['id'] . '">' . assegurarHTML($usuario['nome']) . '</label><br>';
	}
	?>
</div>
<div class="clear"></div>
<input type="hidden" name="caminho" value="<?=assegurarHTML($caminho)?>">
<input type="submit" style="display:none" id="submit">
<span class="botao" id="voltar"><img src="/imgs/voltar.png"> Voltar</span>
<span class="botao" id="salvar"><img src="/imgs/enviar.png"> <?=$criar ? 'Criar' : 'Salvar'?></span>
</form>
