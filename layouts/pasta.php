<?php
// Interpreta o caminho da pasta
$caminho = @$_GET['q'];
$pastas = preg_split('@/@', $caminho, -1, PREG_SPLIT_NO_EMPTY);
if (count($pastas))
	$nomePasta = $pastas[count($pastas)-1];
else
	$nomePasta = 'Diretório raiz';

// Percorre o caminho, verificando a permissão de acesso
$idPasta = NULL; // Irá armazenar o id da pasta em caso de sucesso
$erro = false; // Irá indicar se ocorreu algum erro (pasta inexistente ou inacessível)
for ($i=0; $i<count($pastas); $i++) {
	// Carrega o id da próxima pasta
	$pasta = $pastas[$i];
	if ($pasta == '')
		continue;
	$pai = $idPasta;
	try {
		if (!$pai)
			$dados = Query::query(true, NULL, 'SELECT id, visibilidade, criador FROM pastas WHERE nome=? AND pai IS NULL LIMIT 1', $pasta);
		else
			$dados = Query::query(true, NULL, 'SELECT id, visibilidade, criador FROM pastas WHERE nome=? AND pai=? LIMIT 1', $pasta, $pai);
		$idPasta = $dados['id'];
	} catch (Exception $e) {
		// Pasta não encontrada
		$erro = true;
		break;
	}
	
	// Verifica se a pasta é visível para esse usuário
	if (!verificarVisibilidade('pasta', $idPasta, $dados['visibilidade'], $dados['criador'])) {
		$erro = true;
		break;
	}
}

imprimir($nomePasta, 'h2');

if (!$erro && $idPasta)
	imprimir(Query::getValor('SELECT descricao FROM pastas WHERE id=? LIMIT 1', $idPasta));

// Monta a representação visual do caminho
imprimir(implode('/', $pastas), 'div.caminho');

// Pasta invisível ou não encontrada
if ($erro)
	imprimir('Erro: pasta não encontrada', 'p strong');

// Imprime os botões de ação somente se estiver logado
if ($_usuario && !$erro) {
?>
<div class="acoes">
	<span class="botao"><img src="/imgs/criar_pasta.png"> Criar sub-pasta</span>
	<span class="botao"><img src="/imgs/criar_post.png"> Criar postagem</span>
	<span class="botao"><img src="/imgs/criar_form.png"> Criar formulário</span>
</div>
<?php
}

// Imprime a listagem de sub-itens
if (!$erro) {
	$subitens = array();
	
	// Carrega as subpastas
	if ($idPasta)
		$subpastas = Query::query(false, NULL, 'SELECT "pasta" AS tipo, id, nome, descricao, visibilidade, criador FROM pastas WHERE pai=? ORDER BY nome', $idPasta);
	else
		$subpastas = Query::query(false, NULL, 'SELECT "pasta" AS tipo, id, nome, descricao, visibilidade, criador FROM pastas WHERE pai IS NULL ORDER BY nome');
	for ($i=0; $i<count($subpastas); $i++)
		if (verificarVisibilidade('pasta', $subpastas[$i]['id'], $subpastas[$i]['visibilidade'], $subpastas[$i]['criador']))
			$subitens[] = $subpastas[$i];
	
	// Carrega os posts
	if ($idPasta) {
		$posts = Query::query(false, NULL, 'SELECT "post" AS tipo, id, nome, data, visibilidade, criador FROM posts WHERE pasta=? ORDER BY nome', $idPasta);
		for ($i=0; $i<count($posts); $i++)
			if (verificarVisibilidade('post', $posts[$i]['id'], $posts[$i]['visibilidade'], $posts[$i]['criador']))
				$subitens[] = $posts[$i];
	}
	
	// Carrega os forms
	if ($idPasta) {
		$forms = Query::query(false, NULL, 'SELECT "form" AS tipo, id, nome, data, ativo, criador FROM forms WHERE pasta=? ORDER BY nome', $idPasta);
		for ($i=0; $i<count($forms); $i++)
			if (verificarVisibilidade('form', $posts[$i]['id'], $posts[$i]['ativo'], $posts[$i]['criador']))
				$subitens[] = $forms[$i];
	}
	
	// Imprime cada item
	echo '<div class="listagem">';
	for ($i=0; $i<count($subitens); $i++) {
		$subitem = $subitens[$i];
		if ($subitem['tipo'] == 'pasta') {
			?><div class="item item-pasta">
			<span class="item-nome"><?=$subitem['nome']?></span>
			<span class="item-descricao"><?=$subitem['descricao']?></span>
			</div><?php
		} else if ($subitem['tipo'] == 'post') {
			?><div class="item item-post">
			<span class="item-nome"><?=$subitem['nome']?></span>
			<span class="item-descricao"><?=$subitem['data']?></span>
			</div><?php
		} else if ($subitem['tipo'] == 'form') {
			?><div class="item item-form<?=$subitem['ativo'] ? '' : ' inativo'?>">
			<span class="item-nome"><?=$subitem['nome']?></span>
			<span class="item-descricao"><?=$subitem['data']?></span>
			</div><?php
		}
	}
	echo '</div>';
	
	// Diz que não tem nada
	if (!count($subitens))
		imprimir('Pasta vazia');
}
?>
