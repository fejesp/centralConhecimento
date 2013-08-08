<?php
// Pega o termo de busca
$busca = @$_GET['busca'];
$pasta = @$_GET['pasta'];
?>
<h2>Buscar</h2>

<form>
<p>Buscar por: <input size="50" autofocus name="busca" value="<?=assegurarHTML($busca)?>">
<input type="submit" style="display:none" id="submit">
<span class="botao" onclick="get('submit').click()"><img src="/imgs/enviar.png"> Buscar</span>
</p>
<p>Você pode aspas para agrupar palavras numa expressão, como em <em>"processo seletivo"</em><br>
Para não retornar resultados relacionados a uma palavra coloque um traço antes dela, como em <em>projeto -interno</em></p>
<?php
if ($pasta != '' && $pasta != '/') {
	echo '<p>
	<input type="radio" name="pasta" value="' . assegurarHTML($pasta) . '" id="radioPasta" checked> <label for="radioPasta">Buscar somente na pasta <strong>' . assegurarHTML($pasta) . '</strong></label><br>
	<input type="radio" name="pasta" value="" id="radioPasta2"> <label for="radioPasta2">Buscar em toda a central</label>
	</p>';
}
?>
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
	
	// Limita o tamanho máximo da busca
	if (count($termos)+count($naoTermos)>16)
		morrerComErro('A busca tem muitos termos');
	
	// Montas as querys de busca SQL
	$queryNome = getQueryBusca($termos, $naoTermos, 'nome');
	$queryDescricao = getQueryBusca($termos, $naoTermos, 'descricao');
	$queryConteudo = getQueryBusca($termos, $naoTermos, 'conteudo');
	
	// Pega os dados da pasta inicial
	$dados = NULL;
	$sucesso = interpretarCaminho($pasta, $dados, 'pasta');
	if (!$sucesso)
		morrerComErro('Pasta não encontrada');
	
	// Monta os dados do caminho da pasta inicial até a raiz
	$idPastas = array($dados['id']); // Armazena os ids das pastas que não são parte do resultado
	$nivel = array($dados['id']); // Armazena os ids das pastas do nível atual
	$nomesPastas = array(); // Armazena os nomes completos das pastas associado por id
	while ($dados['id'] != 0) {
		foreach ($nomesPastas as $id=>$valor)
			$nomesPastas[$id] = $dados['nome'] . '/' . $valor;
		$nomesPastas[$dados['id']] = $dados['nome'];
		$dados = Query::query(true, NULL, 'SELECT id, nome, pai FROM pastas WHERE id=? LIMIT 1', $dados['pai']);
	}
	$nomesPastas[0] = ''; // Pasta raiz
	foreach ($nomesPastas as $id=>$valor)
		$nomesPastas[$id] = '/' . $valor;
	
	// Vai montando toda a árvore de pastas visíveis e separando as pastas da resposta
	$rPastas = array(); // Irá reunir as pastas que são resposta da busca
	$query = "SELECT id, nome, descricao, pai, visibilidade, criador, ($queryNome OR $queryDescricao) AS resultado FROM pastas WHERE id!=0 AND pai IN ? AND " . getQueryVisibilidade('pasta');
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
	$criadoresPosts = array(); // Armazena os criadores do posts em $idPosts
	if (count($idPastas)) {
		$query = "SELECT id, pasta, nome, data, visibilidade, criador, ($queryNome OR $queryConteudo) AS resultado FROM posts WHERE pasta IN ? AND " . getQueryVisibilidade('post');
		foreach (Query::query(false, NULL, $query, $idPastas) as $cada) {
			if ($cada['resultado'])
				$rPosts[] = $cada;
			else {
				$nomesPosts[$cada['id']] = $nomesPastas[$cada['pasta']] . '/' . $cada['nome'];
				$idPosts[] = $cada['id'];
				$criadoresPosts[] = $cada['criador'];
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
		imprimir(visibilidade2str('pasta', $pasta['id'], $pasta['visibilidade'], $pasta['criador']), 'span.item-visibilidade');
		echo '</a>';
	}
	foreach ($rPosts as $post) {
		echo '<a class="item item-post" href="' . getHref('post', $nomesPastas[$post['pasta']], $post['nome']) . '">';
		imprimir($post['nome'], 'span.item-nome');
		imprimir('Postado ' . data2str($post['data']), 'span.item-descricao');
		imprimir(visibilidade2str('post', $post['id'], $post['visibilidade'], $post['criador']), 'span.item-visibilidade');
		echo '</a>';
	}
	foreach ($rAnexos as $anexo) {
		echo '<a class="item item-anexo" href="' . getHref('anexo', $nomesPosts[$anexo['post']], $anexo['nome']) . '">';
		imprimir($anexo['nome'], 'span.item-nome');
		imprimir(kiB2str($anexo['tamanho']), 'span.item-descricao');
		imprimir(visibilidade2str('anexo', $anexo['id'], $anexo['visibilidade'], $criadoresPosts[$anexo['post']]), 'span.item-visibilidade');
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
function getQueryBusca($termos, $naoTermos, $campo) {
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
