<?php
// Pega o termo de busca
$busca = @$_GET['busca'];
?>
<h2>Buscar</h2>

<form>
<p>Buscar por: <input size="50" autofocus name="busca" value="<?=assegurarHTML($busca)?>">
<input type="submit" style="display:none" id="submit">
<span class="botao" onclick="get('submit').click()"><img src="/imgs/enviar.png"> Buscar</span>
</p>
</form>

<?php
if ($busca) {
	// Interpreta os termos de busca
	// A sintaxe aceita é de palavras separadas por espaços
	// Pode-se reunir palavras numa expressão com aspas duplas
	// Pode-se negar uma palavra colocando um sinal de menos antes
	// Exemplo: um "duas palavras" -remover -"isso também não"
	$len = strlen($busca);
	$cache = '';
	$naoIncluir = false;
	$termos = array();
	$naoTermos = array();
	$aspas = false;
	for ($i=0; $i<$len; $i++) {
		$c = $busca[$i];
		if ($c == '-' && $cache == '' && !$aspas)
			$naoIncluir = true;
		else if ($c == '"') {
			if ($aspas)
				salvarCache();
			$aspas = !$aspas;
		} else if ($c == ' ' && !$aspas)
			salvarCache();
		else
			$cache .= $c;
	}
	salvarCache();
	
	// Montas as querys de busca SQL
	$queryNome = getQueryBusca('nome');
	$queryDescricao = getQueryBusca('descricao');
	$queryConteudo = getQueryBusca('conteudo');
	
	// Vai montando toda a árvore de pastas visíveis e separando as pastas da resposta
	$rPastas = array(); // Irá reunir as pastas que são resposta da busca
	$nomesPastas = array(0 => ''); // Armazena os nomes completos das pastas associado por id
	$idPastas = array(0); // Armazena os ids das pastas que não são parte do resultado
	$nivel = array(0); // Armazena os ids das pastas do nível atual
	$query = "SELECT id, nome, descricao, pai, visibilidade, ($queryNome OR $queryDescricao) AS resultado FROM pastas WHERE id!=0 AND pai IN ? AND " . getQueryVisibilidade('pasta');
	while (count($nivel)) {
		$temp = Query::query(false, NULL, $query, $nivel);
		$nivel = array();
		foreach ($temp as $cada) {
			// Trata os resultados desse nível, separando o que irá continuar a ser buscado 
			$nomesPastas[$cada['id']] = $nomesPastas[$cada['pai']] . '/' . $cada['nome'];
			if ($cada['resultado'])
				$rPastas[] = $cada;
			else {
				$nivel[] = $cada['id'];
				$idPastas[] = $cada['id'];
			}
		}
	}
	
	// Busca os posts visíveis nas pastas fora do resultado que se encaixam na busca
	$nomesPosts = array(); // Armazena os nomes dos posts que serão usados para buscar pelos anexos
	$idPosts = array(); // Armazena os ids dos posts que não são parte do resultado
	$rPosts = array(); // Vetor de resultados de posts
	if (count($idPastas)) {
		$query = "SELECT id, pasta, nome, data, visibilidade, ($queryNome OR $queryConteudo) AS resultado FROM posts WHERE pasta IN ? AND " . getQueryVisibilidade('post');
		foreach (Query::query(false, NULL, $query, $idPastas) as $cada) {
			if ($cada['resultado'])
				$rPosts[] = $cada;
			else {
				$nomesPosts[$cada['id']] = $nomesPastas[$cada['pasta']] . '/' . $cada['nome'];
				$idPosts[] = $cada['id'];
			}
		}
	}
	
	// Busca os anexos visíveis nos posts fora do resultado que se encaixam na busca
	if (count($idPosts)) {
		$query = 'SELECT * FROM anexos WHERE post IN ? AND ' . getQueryVisibilidade('anexo') . " AND $queryNome";
		$rAnexos = Query::query(false, NULL, $query, $idPosts);
	} else
		$rAnexos = array();
	
	// Busca os forms visíveis nas pastas fora do resultado que se encaixam na busca
	if (count($idPastas)) {
		$query = 'SELECT pasta, nome, data, ativo FROM forms WHERE pasta IN ? AND ' . getQueryVisibilidade('form') . " AND ($queryNome OR $queryDescricao)";
		$rForms = Query::query(false, NULL, $query, $idPastas);
	} else
		$rForms = array();
	
	imprimir("Resultados", 'h2');
	$n = count($rPastas)+count($rPosts)+count($rAnexos)+count($rForms);
	imprimir($n ? ($n==1 ? 'Um resultado' : "$n resultados") : 'Nenhum resultado');
	
	echo '<div class="listagem">';
	foreach ($rForms as $form) {
		echo '<a class="item item-form' . ($form['ativo'] ? '' : ' inativo') . '" href="' . getHref('form', $nomesPastas[$form['pasta']], $form['nome']) . '">';
		imprimir($form['nome'], 'span.item-nome');
		imprimir('Criado ' . data2str($form['data']), 'span.item-descricao');
		echo '</a>';
	}
	foreach ($rPastas as $pasta) {
		echo '<a class="item item-pasta" href="' . getHref('pasta', $nomesPastas[$pasta['pai']], $pasta['nome']) . '">';
		imprimir($pasta['nome'], 'span.item-nome');
		if ($pasta['descricao'])
			imprimir($pasta['descricao'], 'span.item-descricao');
		imprimir(visibilidade2str('pasta', $pasta['id'], $pasta['visibilidade']), 'span.item-visibilidade');
		echo '</a>';
	}
	foreach ($rPosts as $post) {
		echo '<a class="item item-post" href="' . getHref('post', $nomesPastas[$post['pasta']], $post['nome']) . '">';
		imprimir($post['nome'], 'span.item-nome');
		imprimir('Postado ' . data2str($post['data']), 'span.item-descricao');
		imprimir(visibilidade2str('post', $post['id'], $post['visibilidade']), 'span.item-visibilidade');
		echo '</a>';
	}
	foreach ($rAnexos as $anexo) {
		echo '<a class="item item-anexo" href="' . getHref('anexo', $nomesPosts[$anexo['post']], $anexo['nome']) . '">';
		imprimir($anexo['nome'], 'span.item-nome');
		imprimir(kiB2str($anexo['tamanho']), 'span.item-descricao');
		imprimir(visibilidade2str('anexo', $anexo['id'], $anexo['visibilidade']), 'span.item-visibilidade');
		echo '</a>';
	}
	echo '</div>';
}

// Função auxiliar na interpretação dos termos da busca
function salvarCache() {
	global $cache, $naoIncluir, $termos, $naoTermos;
	if ($cache) {
		$cache = str_replace('%', '\\%', str_replace('_', '\\_', Query::$conexao->real_escape_string($cache)));
		if ($naoIncluir)
			$naoTermos[] = $cache;
		else
			$termos[] = $cache;
		$cache = '';
	}
	$naoIncluir = false;
}

// Retorna a query de busca no campo com o nome dado
// Exemplo: 'nome' => '(nome LIKE \'%a%\' AND nome NOT LIKE \'%b%\')'
function getQueryBusca($campo) {
	global $termos, $naoTermos;
	$partes = array();
	foreach ($termos as $cada)
		$partes[] = "$campo LIKE '%$cada%'";
	foreach ($naoTermos as $cada)
		$partes[] = "$campo NOT LIKE '%$cada%'";
	return '(' . implode(' AND ', $partes) . ')';
}

imprimir('Busca por tag', 'h2');
imprimir('Escolhe entre as tags mais comuns');
imprimirNuvemTags(25);
