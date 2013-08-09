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
	
	// Pega os dados da pasta inicial
	$dados = NULL;
	$sucesso = interpretarCaminho($pasta, $dados, 'pasta');
	if (!$sucesso)
		morrerComErro('Pasta não encontrada');
	
	// Monta os dados do caminho da pasta inicial até a raiz
	$idPastas = array($dados['id']); // Armazena os ids das pastas que não são parte do resultado
	$nivel = array($dados['id']); // Armazena os ids das pastas do nível atual
	$nomesPastas = array(); // Armazena os nomes completos das pastas associado por id
	$rPastas = array(); // Irá reunir as pastas que são resposta da busca
	$scoresPastas = array(); // Armazena os scores finais das pastas fora do resultado, associado por id
	$scoresPastas[$dados['id']] = 0;
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
	$query = getQueryBuscaPasta($termos, $naoTermos);
	$scoreMax = (1 << (count($termos)+count($naoTermos)))-1;
	while (count($nivel)) {
		$temp = Query::query(false, NULL, $query, $nivel);
		$nivel = array();
		foreach ($temp as $cada) {
			// Trata os resultados desse nível, separando o que irá continuar a ser buscado 
			$nomesPastas[$cada['id']] = $nomesPastas[$cada['pai']] . '/' . $cada['nome'];
			$score = $cada['resultado'] | $scoresPastas[$cada['pai']];
			if ($score == $scoreMax)
				$rPastas[] = $cada;
			else {
				$nivel[] = $cada['id'];
				$idPastas[] = $cada['id'];
				$scoresPastas[$cada['id']] = $score;
			}
		}
	}
	
	// Busca os posts visíveis nas pastas fora do resultado que se encaixam na busca
	$nomesPosts = array(); // Armazena os nomes dos posts que serão usados para buscar pelos anexos
	$idPosts = array(); // Armazena os ids dos posts que não são parte do resultado
	$rPosts = array(); // Vetor de resultados de posts
	$criadoresPosts = array(); // Armazena os criadores do posts em $idPosts
	$scoresPosts = array(); // Armazena os scores finais dos posts fora do resultado, associado por id
	if (count($idPastas)) {
		$query = getQueryBuscaPost($termos, $naoTermos);
		foreach (Query::query(false, NULL, $query, $idPastas) as $cada) {
			$score = $cada['resultado'] | $scoresPastas[$cada['pasta']];
			if ($score == $scoreMax)
				$rPosts[] = $cada;
			else {
				$nomesPosts[$cada['id']] = $nomesPastas[$cada['pasta']] . '/' . $cada['nome'];
				$idPosts[] = $cada['id'];
				$criadoresPosts[] = $cada['criador'];
				$scoresPosts[$cada['id']] = $score;
			}
		}
	}
	
	// Busca os anexos visíveis nos posts fora do resultado que se encaixam na busca
	$rAnexos = array();
	if (count($idPosts)) {
		$query = getQueryBuscaAnexo($termos, $naoTermos);
		foreach (Query::query(false, NULL, $query, $idPosts) as $cada) {
			$score = $cada['resultado'] | $scoresPosts[$cada['post']];
			if ($score == $scoreMax)
				$rAnexos[] = $cada;
		}
	}
	
	// Busca os forms visíveis nas pastas fora do resultado que se encaixam na busca
	$rForms = array();
	if (count($idPastas)) {
		$query = getQueryBuscaForm($termos, $naoTermos);
		foreach (Query::query(false, NULL, $query, $idPastas) as $cada) {
			$score = $cada['resultado'] | $scoresPastas[$cada['pasta']];
			if ($score == $scoreMax)
				$rForms[] = $cada;
		}
	}
	
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

// Retorna a query de busca para pastas
function getQueryBuscaPasta($termos, $naoTermos) {
	$valor = 1;
	$partes = array();
	for ($i=0; $i<count($termos); $i++) {
		$termo = $termos[$i];
		$partes[] = "$valor*(nome LIKE '%$termo%' OR descricao LIKE '%$termo%')";
		$valor *= 2;
	}
	for ($i=0; $i<count($naoTermos); $i++) {
		$termo = $naoTermos[$i];
		$partes[] = "$valor*(nome NOT LIKE '%$termo%' AND descricao NOT LIKE '%$termo%')";
		$valor *= 2;
	}
	$query = implode('+', $partes);
	
	return "SELECT id, nome, descricao, pai, visibilidade, criador, $query AS resultado FROM pastas WHERE id!=0 AND pai IN ? AND " . getQueryVisibilidade('pasta');
}

// Retorna a query de busca para posts
function getQueryBuscaPost($termos, $naoTermos) {
	$valor = 1;
	$partes = array();
	for ($i=0; $i<count($termos); $i++) {
		$termo = $termos[$i];
		$partes[] = "$valor*(nome LIKE '%$termo%' OR conteudo LIKE '%$termo%')";
		$valor *= 2;
	}
	for ($i=0; $i<count($naoTermos); $i++) {
		$termo = $naoTermos[$i];
		$partes[] = "$valor*(nome NOT LIKE '%$termo%' AND conteudo NOT LIKE '%$termo%')";
		$valor *= 2;
	}
	$query = implode('+', $partes);
	
	return "SELECT id, pasta, nome, data, visibilidade, criador, $query AS resultado FROM posts WHERE pasta IN ? AND " . getQueryVisibilidade('post');
}

// Retorna a query de busca para anexos
function getQueryBuscaAnexo($termos, $naoTermos) {
	$valor = 1;
	$partes = array();
	for ($i=0; $i<count($termos); $i++) {
		$termo = $termos[$i];
		$partes[] = "$valor*(nome LIKE '%$termo%')";
		$valor *= 2;
	}
	for ($i=0; $i<count($naoTermos); $i++) {
		$termo = $naoTermos[$i];
		$partes[] = "$valor*(nome NOT LIKE '%$termo%')";
		$valor *= 2;
	}
	$query = implode('+', $partes);
	
	return "SELECT *, $query AS resultado FROM anexos WHERE post IN ? AND " . getQueryVisibilidade('anexo');
}

// Retorna a query de busca para forms
function getQueryBuscaForm($termos, $naoTermos) {
	$valor = 1;
	$partes = array();
	for ($i=0; $i<count($termos); $i++) {
		$termo = $termos[$i];
		$partes[] = "$valor*(nome LIKE '%$termo%' OR descricao LIKE '%$termo%')";
		$valor *= 2;
	}
	for ($i=0; $i<count($naoTermos); $i++) {
		$termo = $naoTermos[$i];
		$partes[] = "$valor*(nome NOT LIKE '%$termo%' AND descricao NOT LIKE '%$termo%')";
		$valor *= 2;
	}
	$query = implode('+', $partes);
	
	return "SELECT pasta, nome, data, ativo, $query AS resultado FROM forms WHERE pasta IN ? AND " . getQueryVisibilidade('form');
}

imprimir('Busca por tag', 'h2');
imprimir('Escolhe entre as tags mais comuns');
imprimirNuvemTags(25);
