<?php
// Interpreta o caminho da pasta
$dados = NULL;
$caminho = $_GET['q'];
$criar = isset($_GET['criar']);
$sucesso = interpretarCaminho($caminho, $dados, $criar ? 'pasta' : 'post');
imprimir($criar ? 'Criar postagem' : 'Editar postagem', 'h2');
if (!$sucesso) {
	imprimir('Erro: post não encontrado', 'p strong');
	return;
}

gerarJSVar('_caminho', $criar ? $caminho : getCaminhoAcima($caminho));
gerarJSVar('_caminhoPost', $caminho);
	
// Valida as permissões do usuário
if (!$_usuario || (!$criar && !$_usuario['admin'] && $dados['criador'] != $_usuario['id'])) {
	imprimir('Erro: o usuário atual não tem permissão para isso', 'p strong');
	return;
}

// Trata os parâmetros para serem HTML seguro
if ($criar) {
	$dados['nome'] = '';
	$dados['conteudo'] = '';
	$dados['visibilidade'] = 'publico';
	$dados['criador'] = $_usuario['id'];
}
$nomeHTML = assegurarHTML($dados['nome']);
$conteudoHTML = assegurarHTML($dados['conteudo']);
$radio1 = $dados['visibilidade']=='publico' ? ' checked' : '';
$radio2 = $dados['visibilidade']=='geral' ? ' checked' : '';
$radio3 = $dados['visibilidade']=='seleto' ? ' checked' : '';

// Informa as limitações de upload e espaço
gerarJSVar('_criador', $dados['criador']);
gerarJSVar('_maxNum', (int)ini_get('max_file_uploads'));
gerarJSVar('_maxTotal', ini2kiB(ini_get('post_max_size')));
gerarJSVar('_maxCada', ini2kiB(ini_get('upload_max_filesize')));
if ($_usuario['id'] == $dados['criador'] && $_usuario['usoMax']) {
	$uso = Query::getValor('SELECT SUM(a.tamanho) FROM anexos AS a JOIN posts AS p ON a.post=p.id WHERE p.criador=?', $_usuario['id']);
	gerarJSVar('_quotaLivre', $_usuario['usoMax']-$uso);
} else
	gerarJSVar('_quotaLivre', NULL);
gerarJSVar('_quota', $_usuario['usoMax']);

// Carrega as tags
if (!$criar)
	$tags = Query::query(false, 0, 'SELECT t2.nome FROM tagsEmPosts AS t JOIN tags AS t2 ON t.tag=t2.id WHERE t.post=?', $dados['id']);
else
	$tags = array();
?>
<form method="post" action="/editarPost.php<?=$criar ? '?criar' : ''?>" enctype="multipart/form-data" id="form">
<p><label for="nome">Nome:</label> <input size="40" maxlength="200" name="nome" id="nome" required pattern="[^/]+" value="<?=$nomeHTML?>" autofocus></p>
<p><label for="conteudo">Conteúdo:</label><br>
<textarea name="conteudo" id="conteudo"><?=$conteudoHTML?></textarea><br>
<span style="font-size:smaller">Você pode usar cabeçalhos, negrito e outros recursos. <a href="/ajudaHTML" target="_blank">Saiba mais</a></span></p>
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
	gerarJSVar('_usuarios', $usuarios);
	$selecionados = $criar ? array() : Query::query(false, 0, 'SELECT usuario FROM visibilidades WHERE tipoItem="post" AND item=?', $dados['id']);
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

<h2>Tags</h2>
<div class="tags">
<?php
imprimirNuvemTags(10);
?>
</div>
<input type="hidden" name="tags" id="tags" value="<?=assegurarHTML(json_encode($tags))?>">
<p>Adicione tags a este post ou selecione entre as mais usadas</p>
<p><input id="campoTags" size="25"> <span class="botao" id="adicionarTag"><img src="/imgs/adicionar.png"> Adicionar</span></p>
<p id="tagsSelecionadas"><?php
foreach ($tags as $tag)
	echo '<span class="tag" onclick="removerTag(this)">' . assegurarHTML($tag) . '</span>';
?></p>
<div class="clear"></div>

<h2>Anexos</h2>
<div class="acoes"><span class="botao" id="adicionarAnexo"><img src="/imgs/adicionar.png"> Adicionar anexo</span></div>
<div class="listagem" id="anexos">
	<?php
	if (!$criar) {
		// Carrega os anexos
		$anexos = Query::query(false, NULL, 'SELECT id, nome, visibilidade, tamanho FROM anexos WHERE post=? ORDER BY nome', $dados['id']);
		foreach ($anexos as $anexo) {
			$visibilidade = $anexo['visibilidade'];
			$idsSelecionados = array();
			$nomesSelecionados = array();
			if ($visibilidade == 'seleto') {
				$idsSelecionados = Query::query(false, 0, 'SELECT usuario FROM visibilidades WHERE tipoItem="anexo" AND item=?', $anexo['id']);
				$info = 'seleto' . json_encode($idsSelecionados);
			} else
				$info = $visibilidade;
			echo '<div class="item item-anexo" oncontextmenu="menu(event)" data-visibilidade="' . $info . '" data-novo="0" data-id="' . $anexo['id'] . '" data-tamanho="' . $anexo['tamanho'] . '">';
			imprimir($anexo['nome'], 'span.item-nome');
			imprimir(kiB2str($anexo['tamanho']), 'span.item-descricao');
			imprimir(visibilidade2str('anexo', $anexo['id'], $anexo['visibilidade']), 'span.item-visibilidade');
			echo '</div>';
		}
	}
	?>
</div>

<input type="submit" style="display:none" id="submit">
<span class="botao" id="voltar"><img src="/imgs/voltar.png"> Voltar</span>
<span class="botao" id="salvar"><img src="/imgs/enviar.png"> <?=$criar ? 'Criar' : 'Salvar'?></span>
</form>
