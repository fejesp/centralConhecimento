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
$pastas = array(0 => ''); // Armazena as pastas no formato id => nomeCompleto
$idPastas = array(0); // Armazena os ids das pastas todas
$nivel = array(0); // Armazena os ids das pastas do nível atual
while (true) {
	$novoNivel = Query::query(false, NULL, 'SELECT id, nome, pai FROM pastas WHERE id!=0 AND pai IN ? AND ' . getQueryVisibilidade('pasta'), $nivel);
	if (!count($novoNivel))
		break;
	$nivel = array();
	foreach ($novoNivel as $cada) {
		$pastas[$cada['id']] = $pastas[$cada['pai']] . '/' . $cada['nome'];
		$nivel[] = $cada['id'];
		$idPastas[] = $cada['id'];
	}
}

// Busca os posts visíveis nessas pastas com a dada tag
if (count($idPastas))
	$posts = Query::query(false, NULL, 'SELECT p.* FROM posts AS p JOIN tagsEmPosts AS t ON t.post=p.id WHERE t.tag=? AND p.pasta IN ? AND ' . getQueryVisibilidade('post') . ' ORDER BY p.data DESC', $idTag, $idPastas);
else
	$posts = array();

imprimir("Tag $tag", 'h2');
imprimir("Lista de todos os posts com a tag $tag");
$n = count($posts);
imprimir($n ? ($n==1 ? 'Um resultado' : "$n resultados") : 'Nenhum resultado');

echo '<div class="listagem">';
foreach ($posts as $post) {
	$href = getHref('post', $pastas[$post['pasta']], $post['nome']);
	echo "<a class='item item-post' href='$href'>";
	imprimir($post['nome'], 'span.item-nome');
	imprimir('Postado ' . data2str($post['data']), 'span.item-descricao');
	imprimir(visibilidade2str($post['id'], $post['visibilidade']), 'span.item-visibilidade');
	echo '</a>';
}
echo '</div>';

// Gera a nuvem de tags
imprimir('Nuvem de tags', 'h2');
echo '<div>';
$nuvem = Query::query(false, NULL, 'SELECT t.nome, COUNT(*) AS num FROM tags AS t JOIN tagsEmPosts AS tEP ON tEP.tag=t.id GROUP BY t.id HAVING COUNT(*)>0 ORDER BY COUNT(*) DESC LIMIT 25');
if (count($nuvem)) {
	$max = $nuvem[0]['num'];
	foreach ($nuvem as $cada) {
		$href = getHref('tag', '', $cada['nome']);
		echo '<a class="tag' . round(5-4*$cada['num']/$max) . '" href="' . $href . '">' . assegurarHTML($cada['nome']) . '</a>';
	}
}
echo '</div>';

// Retorna a parte da query que verifica a visibilidade de um item
// $tipo deve ser 'pasta' ou 'post'
function getQueryVisibilidade($tipo) {
	global $_usuario;
	if (!$_usuario)
		return "visibilidade='publico'";
	else if ($_usuario['admin'])
		return "1";
	else
		return "(visibilidade='publico'
		OR visibilidade='geral'
		OR criador=$_usuario[id]
		OR EXISTS (SELECT * FROM visibilidades WHERE tipoItem='$tipo' AND item=id AND usuario=$_usuario[id]))";
}

function visibilidade2str($id, $visibilidade) {
	if ($visibilidade == 'publico')
		return 'Visível publicamente';
	else if ($visibilidade == 'geral')
		return 'Visível para todos os usuários logados';
	else {
		$selecionados = Query::query(false, 0, 'SELECT u.nome FROM usuarios AS u JOIN visibilidades AS v ON v.usuario=u.id WHERE v.tipoItem="post" AND v.item=? ORDER BY u.nome', $id);
		if (count($selecionados))
			return 'Visível para somente para ' . implode(', ', $selecionados) . ' e o criador';
		else
			return 'Visível somente para o criador';
	}
}
?>
