<?php
// Busca os dados do post
$caminho = $_GET['q'];
$dados = NULL;
$sucesso = interpretarCaminho($caminho, $dados, 'post');

if (!$sucesso) {
	// Post invisível ou não encontrado
	if (!$_usuario && ($dados == 3 || $dados == 5)) {
		// Como o usuário não está logado e o item está invisível para ele,
		// redireciona para a página de login
		redirecionar('index', '', '', 'itemInvisivel&continuar=' . urlencode('/post' . $caminho));
	} else {
		// Simplesmente mostra a mensagem de erro
		imprimir('Erro', 'h2');
		imprimir('Post não encontrado', 'p strong');
	}
	return;
}

// Mostra a descrição e informações de visibilidade
imprimir($dados['nome'], 'h2');
imprimir(visibilidade2str('post', $dados['id'], $dados['visibilidade'], $dados['criador']), 'p.detalhe');

// Mostra quem e quando postou
$criador = Query::getValor('SELECT nome FROM usuarios WHERE id=? LIMIT 1', $dados['criador']);
imprimir('Postado por ' . $criador . ' ' . data2str($dados['data']), 'p.detalhe');

// Coloca a sequência do caminho
imprimir(getCaminhoAcima($caminho), 'div.caminho');

// Envia algumas variáveis para o JS
gerarJSVar('_caminho', $caminho);
gerarJSVar('_nome', $dados['nome']);

// Mostra as opções de edição
if ($_usuario && ($_usuario['admin'] || $dados['criador'] == $_usuario['id']))
	echo '<div class="acoes">
	<span class="botao" id="remover"><img src="/imgs/remover.png"> Remover</span>
	<span class="botao" id="editar"><img src="/imgs/editar.png"> Editar</span>
	</div>';

// Mostra o conteúdo
imprimir('', 'div.clear');
if (strlen($dados['conteudo']))
	imprimir($dados['conteudo'], 'div.subConteudo', true);
?>
<h2>Tags</h2>
<p><?php
// Exibe as tags
foreach (Query::query(false, 0, 'SELECT t2.nome FROM tagsEmPosts AS t JOIN tags AS t2 ON t.tag=t2.id WHERE t.post=?', $dados['id']) as $tag)
	echo '<a class="tag" href="' . getHref('tag', '', $tag) . '">' . assegurarHTML($tag) . '</a>';
?></p>
<h2>Anexos</h2>
<div class="listagem">
	<?php
	// Carrega os anexos
	$anexos = Query::query(false, NULL, 'SELECT id, nome, visibilidade, tamanho FROM anexos WHERE post=? ORDER BY nome', $dados['id']);
	foreach ($anexos as $anexo) {
		if (verificarVisibilidade('anexo', $anexo['id'], $anexo['visibilidade'], $dados['criador'])) {
			echo '<a class="item item-anexo" href="' . getHref('anexo', $caminho, $anexo['nome']) . '">';
			imprimir($anexo['nome'], 'span.item-nome');
			imprimir(kiB2str($anexo['tamanho']), 'span.item-descricao');
			imprimir(visibilidade2str('anexo', $anexo['id'], $anexo['visibilidade'], $dados['criador']), 'span.item-visibilidade');
			echo '</a>';
		}
	}
	?>
</div>
