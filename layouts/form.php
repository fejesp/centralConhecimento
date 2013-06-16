<?php
// Busca os dados do form
$caminho = $_GET['q'];
$dados = NULL;
$sucesso = interpretarCaminho($caminho, $dados, 'form');

if (!$sucesso) {
	// Form invisível ou não encontrado
	imprimir('Erro', 'h2');
	imprimir('Formulário não encontrado', 'p strong');
	return;
}

// Mostra quem e quando postou
imprimir($dados['nome'], 'h2');
$criador = Query::getValor('SELECT nome FROM usuarios WHERE id=? LIMIT 1', $dados['criador']);
imprimir('Criado por ' . $criador . ' ' . data2str($dados['data']), 'p.detalhe');

// Coloca a sequência do caminho
imprimir(getCaminhoAcima($caminho), 'div.caminho');

// Envia algumas variáveis para o JS
gerarJSVar('_caminho', $caminho);
gerarJSVar('_pasta', getCaminhoAcima($caminho));

// Mostra as opções de edição
if ($_usuario && ($_usuario['admin'] || $dados['criador'] == $_usuario['id'])) {
	echo '<div class="acoes">
	<span class="botao" id="remover"><img src="/imgs/remover.png"> Remover</span>
	<span class="botao" id="editar"><img src="/imgs/editar.png"> Editar</span> ';
	if ($dados['ativo'])
		echo '<span class="botao" id="desativar"><img src="/imgs/desativar.png"> Desativar form</span>';
	else
		echo '<span class="botao" id="ativar"><img src="/imgs/ativar.png"> Reativar form</span>';
	echo '</div>';
}

// Informa as limitações de upload e espaço
gerarJSVar('_maxNum', (int)ini_get('max_file_uploads'));
gerarJSVar('_maxTotal', ini2kiB(ini_get('post_max_size')));
gerarJSVar('_maxCada', ini2kiB(ini_get('upload_max_filesize')));

// Mostra o conteúdo
imprimir('', 'div.clear');
imprimir($dados['descricao'], 'div.descricaoForm', true);

// Monta os campos iniciais do formulário (identificação do post e do usuário)
echo '<form' . ($dados['ativo'] ? '' : ' class="inativo"') . ' action="/form.php" method="post" enctype="multipart/form-data">';
if (!$_usuario)
	echo '<p>Se você possui cadastro no sistema, por favor faça o login</p>
	<p>Se não, informe seu email e sua empresa júnior<br>
	Email do responsável: <input size="30" type="email" name="email" required><br>
	Nome da Empresa Júnior: <input name="ej" required></p>';
echo '<p><strong>Nome da postagem</strong>:<br><input size="30" name="nome" required pattern="[^/]+"></p>';
echo '<input type="hidden" name="caminho" value="' . assegurarHTML($caminho) . '">';
echo '<input type="hidden" name="data" value="' . $dados['data'] . '">';

// Monta os campos
foreach (json_decode($dados['conteudo'], true) as $i=>$campo) {
	$tipoCampo = $campo['tipo'];
	$nomeHTML = assegurarHTML($campo['nome']);
	$ajudaHTML = assegurarHTML($campo['ajuda']);
	$obrigatorio = $campo['obrigatorio'] ? ' required' : '';
	// Imprime o HTML de cada tipo de campo
	switch ($campo['tipo']) {
	case 'input':
		echo "<p><strong>$nomeHTML</strong>:<br><input size='40'$obrigatorio name='campos[$i]'></p>";
		break;
	case 'textarea':
		echo "<p><strong>$nomeHTML</strong>:<br>$ajudaHTML<br><textarea$obrigatorio name='campos[$i]'></textarea></p>";
		break;
	case 'radio':
		echo "<p><strong>$nomeHTML</strong>:<br>";
		foreach ($campo['valores'] as $valor) {
			$idCampo = gerarSenha();
			$valor = assegurarHTML($valor);
			echo "<input type='radio' id='radio$idCampo'$obrigatorio name='campos[$i]' value='$valor'> <label for='radio$idCampo'>$valor</label><br>";
		}
		echo '</p>';
		break;
	case 'checkbox':
		echo "<p><strong>$nomeHTML</strong>:<br>";
		foreach ($campo['valores'] as $valor) {
			$idCampo = gerarSenha();
			$valor = assegurarHTML($valor);
			echo "<input type='checkbox' id='radio$idCampo'$obrigatorio name='campos[$i][$valor]' value='1'> <label for='radio$idCampo'>$valor</label><br>";
		}
		echo '</p>';
		break;
	}
}

?>

<h2>Anexos</h2>
<div class="acoes"><span class="botao" id="adicionarAnexo"><img src="/imgs/adicionar.png"> Adicionar anexo</span></div>
<div class="listagem" id="anexos"></div>

<?php
// Imprime o fim do HTML do form
echo '<input type="submit" style="display:none" id="submit">
<span class="botao" id="voltar"><img src="/imgs/voltar.png"> Voltar</span>
<span class="botao" id="enviar"><img src="/imgs/enviar.png"> Enviar</span>
</form>';
?>
