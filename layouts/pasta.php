<?php
// Interpreta o caminho da pasta
$dados = NULL;
$caminho = $_GET['q'];
$sucesso = interpretarCaminho($caminho, $dados);

imprimir($dados['nome'], 'h2');
// Mostra a descrição e informações de visibilidade
if ($sucesso && $dados['id']) {
	imprimir(Query::getValor('SELECT descricao FROM pastas WHERE id=? LIMIT 1', $dados['id']));
	if ($dados['visibilidade'] == 'publico')
		imprimir('Pasta visível publicamente', 'p.detalhe');
	else if ($dados['visibilidade'] == 'geral')
		imprimir('Pasta visível para todos os usuários logados', 'p.detalhe');
	else {
		$selecionados = Query::query(false, 0, 'SELECT u.nome FROM usuarios AS u JOIN visibilidades AS v ON v.usuario=u.id WHERE v.tipoItem="pasta" AND v.item=?', $dados['id']);
		if (count($selecionados))
			imprimir('Pasta visível para somente para ' . implode(', ', $selecionados) . ' e o seu criador', 'p.detalhe');
		else
			imprimir('Pasta visível para somente para o criador', 'p.detalhe');
	}
}

// Monta a representação visual do caminho
imprimir($caminho, 'div.caminho');

// Envia para o JS as variáveis que ele precisa
gerarJSVar('caminho', $caminho);
gerarJSVar('admin', $_usuario['admin']);
gerarJSVar('usuario', $_usuario['id']);

// Pasta invisível ou não encontrada
if (!$sucesso)
	imprimir('Erro: pasta não encontrada', 'p strong');

// Imprime os botões de ação somente se estiver logado
if ($_usuario && $sucesso) {
?>
<div class="acoes">
	<span class="botao" id="criarPasta"><img src="/imgs/criar_pasta.png"> Criar pasta</span>
	<span class="botao" id="criarPost"><img src="/imgs/criar_post.png"> Criar postagem</span>
	<span class="botao" id="criarForm"><img src="/imgs/criar_form.png"> Criar formulário</span>
</div>
<?php
}

// Imprime a listagem de sub-itens
if ($sucesso) {
	$subitens = array();
	
	// Carrega as subpastas
	$subpastas = Query::query(false, NULL, 'SELECT "pasta" AS tipo, id, nome, descricao, visibilidade, criador FROM pastas WHERE id != 0 AND pai=? ORDER BY nome', $dados['id']);
	for ($i=0; $i<count($subpastas); $i++)
		if (verificarVisibilidade('pasta', $subpastas[$i]['id'], $subpastas[$i]['visibilidade'], $subpastas[$i]['criador']))
			$subitens[] = $subpastas[$i];
	
	// Carrega os posts
	if ($dados['id']) {
		$posts = Query::query(false, NULL, 'SELECT "post" AS tipo, id, nome, data, visibilidade, criador FROM posts WHERE pasta=? ORDER BY nome', $dados['id']);
		for ($i=0; $i<count($posts); $i++)
			if (verificarVisibilidade('post', $posts[$i]['id'], $posts[$i]['visibilidade'], $posts[$i]['criador']))
				$subitens[] = $posts[$i];
	}
	
	// Carrega os forms
	if ($dados['id']) {
		$forms = Query::query(false, NULL, 'SELECT "form" AS tipo, id, nome, data, ativo, criador FROM forms WHERE pasta=? ORDER BY nome', $dados['id']);
		for ($i=0; $i<count($forms); $i++)
			if (verificarVisibilidade('form', $posts[$i]['id'], $posts[$i]['ativo'], $posts[$i]['criador']))
				$subitens[] = $forms[$i];
	}
	
	// Imprime cada item
	echo '<div class="listagem" id="listagem">';
	for ($i=0; $i<count($subitens); $i++) {
		$subitem = $subitens[$i];
		$itemCriador = $subitem['criador'];
		if ($subitem['tipo'] == 'pasta') {
			echo "<div class='item item-pasta' onclick='ir(this, \"pasta\")' oncontextmenu='menu(\"pasta\", $itemCriador, event)'>";
			imprimir($subitem['nome'], 'span.item-nome');
			imprimir($subitem['descricao'], 'span.item-descricao');
			echo '</div>';
		} else if ($subitem['tipo'] == 'post') {
			echo "<div class='item item-post' onclick='ir(this, \"post\")' oncontextmenu='menu(\"post\", $itemCriador, event)'>";
			imprimir($subitem['nome'], 'span.item-nome');
			imprimir($subitem['data'], 'span.item-descricao');
			echo '</div>';
		} else if ($subitem['tipo'] == 'form') {
			echo "<div class='item item-form'" . ($subitem['ativo'] ? '' : ' inativo') . " onclick='ir(this, \"form\")' oncontextmenu='menu(\"form\", $itemCriador, event)'>";
			imprimir($subitem['nome'], 'span.item-nome');
			imprimir($subitem['data'], 'span.item-descricao');
			echo '</div>';
		}
	}
	echo '</div>';
	
	// Diz que não tem nada
	if (!count($subitens))
		imprimir('Pasta vazia');
}
?>
