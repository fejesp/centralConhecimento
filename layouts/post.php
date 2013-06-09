<?php
// Busca os dados do post
$caminho = $_GET['q'];
$dados = NULL;
$sucesso = interpretarCaminho($caminho, $dados, 'post');

if (!$sucesso) {
	// Post invisível ou não encontrado
	imprimir('Erro', 'h2');
	imprimir('Post não encontrado', 'p strong');
	return;
}

// Mostra a descrição e informações de visibilidade
imprimir($dados['nome'], 'h2');
if ($dados['visibilidade'] == 'publico')
	imprimir('Post visível publicamente', 'p.detalhe');
else if ($dados['visibilidade'] == 'geral')
	imprimir('Post visível para todos os usuários logados', 'p.detalhe');
else {
	$selecionados = Query::query(false, 0, 'SELECT u.nome FROM usuarios AS u JOIN visibilidades AS v ON v.usuario=u.id WHERE v.tipoItem="post" AND v.item=?', $dados['id']);
	if (count($selecionados))
		imprimir('Post visível para somente para ' . implode(', ', $selecionados) . ' e o seu criador', 'p.detalhe');
	else
		imprimir('Post visível para somente para o criador', 'p.detalhe');
}

// Mostra quem e quando postou
$criador = Query::getValor('SELECT nome FROM usuarios WHERE id=? LIMIT 1', $dados['criador']);
imprimir('Pastado por ' . $criador . ' ' . data2str($dados['data']), 'p.detalhe');

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
imprimir($dados['conteudo'], 'div.subConteudo');
?>

<h2>Anexos</h2>
<div class="listagem">
	<div class="item">
		<img src="/imgs/anexo.png">
		<span class="item-nome">[Item 1]</span>
	</div>
	<div class="item">
		<img src="/imgs/anexo.png">
		<span class="item-nome">[Item 2]</span>
	</div>
</div>
