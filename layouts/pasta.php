<?php
// Interpreta o caminho da pasta
$dados = NULL;
$caminho = $_GET['q'];
$sucesso = interpretarCaminho($caminho, $dados);

if (!$sucesso) {
	// Pasta invisível ou não encontrada
	if (!$_usuario && ($dados == 3 || $dados == 5)) {
		// Como o usuário não está logado e o item está invisível para ele,
		// redireciona para a página de login
		redirecionar('index', '', '', 'itemInvisivel&continuar=' . urlencode('/pasta' . $caminho));
	} else {
		// Simplesmente mostra a mensagem de erro
		imprimir('Erro', 'h2');
		imprimir('Pasta não encontrada', 'p strong');
	}
	return;
}

// Mostra a descrição e informações de visibilidade
imprimir($dados['nome'], 'h2');
if ($dados['descricao'])
	imprimir($dados['descricao']);
imprimir(visibilidade2str('pasta', $dados['id'], $dados['visibilidade'], $dados['criador']), 'p.detalhe');

// Monta a representação visual do caminho
imprimirCaminho($caminho);

// Envia para o JS as variáveis que ele precisa
gerarJSVar('_caminho', $caminho);
gerarJSVar('_admin', $_usuario['admin']);
gerarJSVar('_usuario', $_usuario['id']);

// Imprime os botões de ação somente se estiver logado
if ($_usuario) {
?>
<div class="acoes">
	<span class="botao" id="criarPasta"><img src="/imgs/criarPasta.png"> Criar pasta</span>
	<span class="botao" id="criarPost"><img src="/imgs/criarPost.png"> Criar postagem</span>
	<span class="botao" id="criarForm"><img src="/imgs/criarForm.png"> Criar formulário</span>
</div>
<?php
}

// Imprime a listagem de sub-itens
$subitens = array();

// Carrega os forms
$forms = Query::query(false, NULL, 'SELECT "form" AS tipo, id, nome, data, ativo, criador FROM forms WHERE pasta=? ORDER BY nome', $dados['id']);
for ($i=0; $i<count($forms); $i++)
	if (verificarVisibilidade('form', $forms[$i]['id'], $forms[$i]['ativo'], $forms[$i]['criador']))
		$subitens[] = $forms[$i];

// Carrega as subpastas
$subpastas = Query::query(false, NULL, 'SELECT "pasta" AS tipo, id, nome, descricao, visibilidade, criador FROM pastas WHERE id!=0 AND pai=? ORDER BY nome', $dados['id']);
for ($i=0; $i<count($subpastas); $i++)
	if (verificarVisibilidade('pasta', $subpastas[$i]['id'], $subpastas[$i]['visibilidade'], $subpastas[$i]['criador']))
		$subitens[] = $subpastas[$i];

// Carrega os posts
$posts = Query::query(false, NULL, 'SELECT "post" AS tipo, id, nome, data, visibilidade, criador FROM posts WHERE pasta=? ORDER BY nome', $dados['id']);
for ($i=0; $i<count($posts); $i++)
	if (verificarVisibilidade('post', $posts[$i]['id'], $posts[$i]['visibilidade'], $posts[$i]['criador']))
		$subitens[] = $posts[$i];

// Imprime cada item
echo '<div class="listagem" id="listagem">';
for ($i=0; $i<count($subitens); $i++) {
	$subitem = $subitens[$i];
	$itemCriador = $subitem['criador'];
	if ($subitem['tipo'] == 'pasta') {
		echo "<a draggable='false' class='item item-pasta' href='" . getHref('pasta', $caminho, $subitem['nome']) . "' oncontextmenu='menu(event)' data-criador='$itemCriador' data-tipo='pasta'>";
		imprimir($subitem['nome'], 'span.item-nome');
		if ($subitem['descricao'])
			imprimir($subitem['descricao'], 'span.item-descricao');
		imprimir(visibilidade2str('pasta', $subitem['id'], $subitem['visibilidade'], $itemCriador), 'span.item-visibilidade');
		echo '</a>';
	} else if ($subitem['tipo'] == 'post') {
		echo "<a draggable='false' class='item item-post' href='" . getHref('post', $caminho, $subitem['nome']) . "' oncontextmenu='menu(event)' data-criador='$itemCriador' data-tipo='post'>";
		imprimir($subitem['nome'], 'span.item-nome');
		imprimir('Postado ' . data2str($subitem['data']), 'span.item-descricao');
		imprimir(visibilidade2str('post', $subitem['id'], $subitem['visibilidade'], $itemCriador), 'span.item-visibilidade');
		echo '</a>';
	} else if ($subitem['tipo'] == 'form') {
		echo "<a draggable='false' class='item item-form" . ($subitem['ativo'] ? '' : ' inativo') . "' href='" . getHref('form', $caminho, $subitem['nome']) . "' oncontextmenu='menu(event)' data-criador='$itemCriador' data-tipo='form'>";
		imprimir($subitem['nome'], 'span.item-nome');
		imprimir('Criado ' . data2str($subitem['data']), 'span.item-descricao');
		echo '</a>';
	}
}
echo '</div>';

// Diz que não tem nada
if (!count($subitens))
	imprimir('Pasta vazia');
?>
