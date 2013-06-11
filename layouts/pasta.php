<?php
// Interpreta o caminho da pasta
$dados = NULL;
$caminho = $_GET['q'];
$sucesso = interpretarCaminho($caminho, $dados);

if (!$sucesso) {
	// Post invisível ou não encontrado
	imprimir('Erro', 'h2');
	imprimir('Post não encontrado', 'p strong');
	return;
}

// Mostra a descrição e informações de visibilidade
imprimir($dados['nome'], 'h2');
if ($dados['id']) {
	imprimir($dados['descricao']);
	if ($dados['visibilidade'] == 'publico')
		imprimir('Pasta visível publicamente', 'p.detalhe');
	else if ($dados['visibilidade'] == 'geral')
		imprimir('Pasta visível para todos os usuários logados', 'p.detalhe');
	else {
		$selecionados = Query::query(false, 0, 'SELECT u.nome FROM usuarios AS u JOIN visibilidades AS v ON v.usuario=u.id WHERE v.tipoItem="pasta" AND v.item=?', $dados['id']);
		if (count($selecionados))
			imprimir('Pasta visível somente para ' . implode(', ', $selecionados) . ' e o seu criador', 'p.detalhe');
		else
			imprimir('Pasta visível para somente para o criador', 'p.detalhe');
	}
}

// Monta a representação visual do caminho
imprimir(getCaminhoAcima($caminho), 'div.caminho');

// Envia para o JS as variáveis que ele precisa
gerarJSVar('caminho', $caminho);
gerarJSVar('admin', $_usuario['admin']);
gerarJSVar('usuario', $_usuario['id']);

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

// Carrega os forms
$forms = Query::query(false, NULL, 'SELECT "form" AS tipo, id, nome, data, ativo, criador FROM forms WHERE pasta=? ORDER BY nome', $dados['id']);
for ($i=0; $i<count($forms); $i++)
	if (verificarVisibilidade('form', $forms[$i]['id'], $forms[$i]['ativo'], $forms[$i]['criador']))
		$subitens[] = $forms[$i];

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
		imprimir('Postado ' . data2str($subitem['data']), 'span.item-descricao');
		echo '</div>';
	} else if ($subitem['tipo'] == 'form') {
		echo "<div class='item item-form" . ($subitem['ativo'] ? '' : ' inativo') . "' onclick='ir(this, \"form\")' oncontextmenu='menu(\"form\", $itemCriador, event)'>";
		imprimir($subitem['nome'], 'span.item-nome');
		imprimir('Criado ' . data2str($subitem['data']), 'span.item-descricao');
		echo '</div>';
	}
}
echo '</div>';

// Diz que não tem nada
if (!count($subitens))
	imprimir('Pasta vazia');
?>
