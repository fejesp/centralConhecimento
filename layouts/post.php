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
imprimirCaminho(getCaminhoAcima($caminho));

// Envia algumas variáveis para o JS
gerarJSVar('_caminho', $caminho);
gerarJSVar('_nome', $dados['nome']);
gerarJSVar('_nomeUsuario', $_usuario ? $_usuario['nome'] : NULL);

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

// Exibe as tags
$tags = Query::query(false, 0, 'SELECT t2.nome FROM tagsEmPosts AS t JOIN tags AS t2 ON t.tag=t2.id WHERE t.post=?', $dados['id']);
if (count($tags)) {
	imprimir('Tags', 'h2');
	echo '<p>';
	foreach ($tags as $tag)
		echo '<a class="tag" href="' . getHref('tag', '', $tag) . '">' . assegurarHTML($tag) . '</a>';
	echo '</p>';
}

// Carrega os anexos
$anexos = Query::query(false, NULL, 'SELECT id, nome, visibilidade, tamanho FROM anexos WHERE post=? ORDER BY nome', $dados['id']);
if (count($anexos)) {
	imprimir('Anexos', 'h2');
	echo '<div class="listagem">';
	foreach ($anexos as $anexo)
		if (verificarVisibilidade('anexo', $anexo['id'], $anexo['visibilidade'], $dados['criador'])) {
			echo '<a class="item item-anexo" href="' . getHref('anexo', $caminho, $anexo['nome']) . '">';
			imprimir($anexo['nome'], 'span.item-nome');
			imprimir(kiB2str($anexo['tamanho']), 'span.item-descricao');
			imprimir(visibilidade2str('anexo', $anexo['id'], $anexo['visibilidade'], $dados['criador']), 'span.item-visibilidade');
			echo '</a>';
		}
	echo '</div>';
}

// Mostra a seção de comentários
imprimir('Comentários', 'h2');
if ($_usuario)
	echo '<div class="acoes"><span class="botao" id="comentar"><img src="/imgs/comentar.png"> Adicionar comentário</span></div>';

$comentarios = Query::query(false, NULL, 'SELECT
	c.id, c.conteudo, c.data, c.modificacao, c.criador, u.nome
	FROM comentarios AS c
	JOIN usuarios AS u ON c.criador=u.id
	WHERE post=? ORDER BY id', $dados['id']);
echo '<div class="comentarios" id="comentarios">';
if (count($comentarios)) {
	// Mostra os comentários
	foreach ($comentarios as $comentario) {
		echo '<div class="comentario" data-id="' . $comentario['id'] . '" data-conteudo="' . assegurarHTML($comentario['conteudo']) . '">';
		$ultimaEdicao = $comentario['data'] != $comentario['modificacao'] ? ' (editado ' . data2str($comentario['modificacao']) . ')' : '';
		imprimir($comentario['nome'] . ' disse ' . data2str($comentario['data']) . $ultimaEdicao, 'p.detalhe');
		if ($_usuario && ($_usuario['admin'] || $comentario['criador'] == $_usuario['id']))
			echo '<div class="acoes">
			<span class="botao" onclick="excluirComentario(' . $comentario['id'] . ')"><img src="/imgs/excluirComentario.png"> Excluir</span>
			<span class="botao" onclick="editarComentario(' . $comentario['id'] . ')"><img src="/imgs/editarComentario.png"> Editar</span>
			</div>';
		imprimir($comentario['conteudo'], 'div.subConteudo', true);
		echo '</div>';
	}
}
echo '</div>';
?>

<div id="editarComentario" style="display:none" class="comentario">
<p><label for="conteudo">Conteúdo [<a href="#" style="font-size:smaller" onClick="visualizar();return false">visualizar resultado</a>]:</label><br>
<textarea name="conteudo" id="conteudo"></textarea><br>
<span style="font-size:smaller">Você pode usar cabeçalhos, negrito e outros recursos. <a href="/ajudaHTML" target="_blank">Saiba mais</a></span></p>
<span class="botao" id="cancelar"><img src="/imgs/voltar.png"> Cancelar</span>
<span class="botao" id="salvar"><img src="/imgs/enviar.png"> Salvar</span>
</div>
