<?php
// Carrega o nome da tag
$tag = substr($_GET['q'], 1);

// Carrega o id da tag
$idTag = Query::getValor('SELECT id FROM tags WHERE nome=? LIMIT 1', $tag);

if (!$idTag) {
	imprimir('Erro', 'h2');
	imprimir("Tag $tag não encontrada");
	return;
}

// Vai montando toda a árvore de pastas visíveis
$nomesPastas = array(0 => ''); // Armazena os nomes completos das pastas associado por id
$idPastas = array(0); // Armazena os ids das pastas todas
$nivel = array(0); // Armazena os ids das pastas do nível atual
$query = 'SELECT id, nome, pai FROM pastas WHERE id!=0 AND pai IN ? AND ' . getQueryVisibilidade('pasta');
while (count($nivel)) {
	$temp = Query::query(false, NULL, $query, $nivel);
	$nivel = array();
	foreach ($temp as $cada) {
		$nomesPastas[$cada['id']] = $nomesPastas[$cada['pai']] . '/' . $cada['nome'];
		$nivel[] = $cada['id'];
		$idPastas[] = $cada['id'];
	}
}

// Busca os posts visíveis nessas pastas com a dada tag
$query = 'SELECT p.id, p.pasta, p.nome, p.data, p.modificacao, p.visibilidade, p.criador FROM posts AS p JOIN tagsEmPosts AS t ON t.post=p.id WHERE t.tag=? AND p.pasta IN ? AND ' . getQueryVisibilidade('post') . ' ORDER BY p.data DESC';
if (count($idPastas))
	$posts = Query::query(false, NULL, $query, $idTag, $idPastas);
else
	$posts = array();

imprimir("Tag $tag", 'h2');
imprimir("Lista de todos os posts com a tag $tag");
$n = count($posts);
imprimir($n ? ($n==1 ? 'Um resultado' : "$n resultados") : 'Nenhum resultado');

echo '<div class="listagem">';
foreach ($posts as $post) {
	echo '<a class="item item-post" href="' . getHref('post', $nomesPastas[$post['pasta']], $post['nome']) . '">';
	imprimir($post['nome'], 'span.item-nome');
	if ($post['modificacao'] != $post['data'])
		imprimir('Modificado ' . data2str($post['modificacao']), 'span.item-descricao');
	else
		imprimir('Postado ' . data2str($post['data']), 'span.item-descricao');
	imprimir(visibilidade2str('post', $post['id'], $post['visibilidade'], $post['criador']), 'span.item-visibilidade');
	echo '</a>';
}
echo '</div>';

// Gera a nuvem de tags
imprimir('Nuvem de tags', 'h2');
imprimirNuvemTags(25);
