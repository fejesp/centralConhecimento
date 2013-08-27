
<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 06/06/2013
*/

// Carrega as configurações e conecta ao banco de dados
require_once 'config.php';
require_once 'utils.php';
require_once 'Query.php';
conectar();
validarLogin();

// Interpreta o caminho do item
$dados = NULL;
$caminho = @$_POST['caminho'];
$sucesso = interpretarCaminho($caminho, $dados, 'form');
if (!$sucesso)
	morrerComErro('Form não encontrado');

// Carrega a identificação do usuário
if ($_usuario) {
	$email = $_usuario['email'];
	$EJ = $_usuario['nome'];
} else {
	$email = $_POST['email'];
	$EJ = $_POST['ej'];
	if (!validarEmail($email))
		morrerComErro('O email informado não parece ser válido');
}

$nome = @$_POST['nome'];
$data = @$_POST['data'];

// Valida o nome
if (!preg_match('@^[^/]+$@', $nome))
	morrerComErro('Nome inválido');

// Valida a data (para garantir que o formulário não foi alterado de lá pra cá)
if ($data != $dados['data'])
	morrerComErro('O formulário foi alterado, por favor volte e responda o novo');

// Monta o conteúdo do post
$campos = empty($_POST['campos']) ? array() : $_POST['campos'];
$conteudo = array();
foreach (json_decode($dados['conteudo'], true) as $i=>$campo) {
	if (empty($campos[$i]))
		continue;
	if ($campo['tipo'] == 'checkbox') {
		$valores = array_keys($campos[$i]);
		for ($i=0; $i<count($valores); $i++)
			$valores[$i] = '* ' . $valores[$i];
		$conteudo[] = "*$campo[nome]:*  \n" . implode("\n", $valores);
	} else
		$conteudo[] = "*$campo[nome]:*  \n" . $campos[$i];
}
$conteudo = implode("\n\n", $conteudo);

// Evita conflitos de nomes, adicionando "(n)" no fim se necessário
$i = 1;
$nome2 = $nome;
while (Query::existe('SELECT 1 FROM posts WHERE nome=? AND pasta=? LIMIT 1', $nome2, $dados['pasta'])) {
	$i++;
	$nome2 = "$nome ($i)";
}

// Salva no banco de dados
Query::$conexao->autocommit(false);
$novosAnexos = array();
$resumoAnexos = array();
try {
	// Cria o post
	new Query('INSERT INTO posts VALUES (NULL, ?, ?, ?, NOW(), NOW(), "seleto", ?)', $dados['pasta'], $nome2, $conteudo, $dados['criador']);
	$idPost = Query::$conexao->insert_id;
	
	// Adiciona o usuário que submeteu o post na lista de visibilidades
	if ($_usuario && $dados['criador'] != $_usuario['id'])
		new Query('INSERT INTO visibilidades VALUES ("post", ?, ?)', $idPost, $_usuario['id']);
	
	// Trata os novos anexos
	if (isset($_FILES['arquivos'])) {
		$espacoLivre = $_config['espacoTotal']-Query::getValor('SELECT SUM(tamanho) FROM anexos');
		$nomes = $_FILES['arquivos']['name'];
		$tmp_names = $_FILES['arquivos']['tmp_name'];
		$erros = $_FILES['arquivos']['error'];
		$tamanhos = $_FILES['arquivos']['size'];
		foreach ($nomes as $i=>$nomeAnexo) {
			if ($erros[$i])
				throw new ErrorException('Falha no upload do arquivo ' . $nomeAnexo);
			
			// Verifica se não passa da cota
			$tamanho = round($tamanhos[$i]/1024);
			$espacoLivre -= $tamanho;
			if ($espacoLivre < 0)
				throw new ErrorException('O sistema não possui mais espaço livre');
			
			// Insere no BD
			new Query('INSERT INTO anexos VALUES (NULL, ?, ?, "publico", ?)', $nomeAnexo, $idPost, $tamanho);
			
			// Marca para mover depois
			$novosAnexos[] = array(Query::$conexao->insert_id, $nomeAnexo, $tmp_names[$i]);
			$resumoAnexos[] = '<li>' . assegurarHTML($nomeAnexo) . ' (' . kiB2str($tamanho) . ')</li>';
		}
	}
	
	// Torna efetivo as modificações
	Query::$conexao->commit();
	foreach ($novosAnexos as $cada) {
		mkdir("arquivos/$cada[0]");
		move_uploaded_file($cada[2], "arquivos/$cada[0]/$cada[1]");
	}
	
	if ($_usuario) {
		$link = getHref('post', getCaminhoAcima($caminho), $nome2, true);
		$link = "<p>Você também pode ver sua resposta diretamente na central:<br><a href='$link'>$link</a></p>";
	} else
		$link = '';
	
	// Envia um e-mail confirmando a submissão do formulário
	$assunto = '[FEJESP][Central de conhecimento] ' . $dados['nome'];
	$mensagem = '<p>Olá ' . assegurarHTML($EJ) . ',</p>
	<p>Sua submissão ao formulário ' . assegurarHTML($dados['nome']) . ' foi efetuada com sucesso. Abaixo segue um resumo das suas respostas e dos arquivos anexados.</p>
	<div>' . gerarHTML($conteudo) . '</div>
	<p>' . (count($resumoAnexos) ? '<strong>Anexos</strong>:<br><ul>' . implode("\n", $resumoAnexos) . '</ul>' : '<strong>Nenhum anexo</strong>') . '</p>
	' . $link . '
	<p>Att,<br>
	Núcleo de TI - FEJESP</p>';
	$emailCriador = Query::getValor('SELECT email FROM usuarios WHERE id=? LIMIT 1', $dados['criador']);
	$cabecalhos = "From: ti@fejesp.org.br\r\nCc: $emailCriador\r\nContent-type: text/html; charset=UTF-8";
	mail($email, $assunto, $mensagem, $cabecalhos, '-r ti@fejesp.org.br');
	
	// Vai para a pasta
	redirecionar('pasta', getCaminhoAcima($caminho));
} catch (Exception $e) {
	Query::$conexao->rollback();
	morrerComErro('Falha ao gravar os dados: ' . $e->getMessage());
}
