<?php
// Busca os dados do anexo
$caminho = $_GET['q'];
$anexo = NULL;
$sucesso = interpretarCaminho($caminho, $anexo, 'anexo');

if (!$sucesso) {
	// Anexo invisível ou não encontrado
	imprimir('Erro', 'h2');
	imprimir('Anexo não encontrado', 'p strong');
	return;
}

// Busca o nome real do arquivo
$arquivo = '';
foreach (scandir("arquivos/$anexo[id]") as $cada)
	if ($cada != '.' && $cada != '..')
		$arquivo = "arquivos/$anexo[id]/$cada";
if (!$arquivo) {
	imprimir('Erro', 'h2');
	imprimir('Anexo não encontrado', 'p strong');
	return;
}

// TODO: anotar a estatística
// TODO: pesquisa de download com usuários não logados

// Gera o download do anexo
ob_end_clean();
header('Content-Disposition: attachment; filename="' . $anexo['nome'] . '"');
header('Content-Type: application/octet-stream'); // Deixa o MIME para o navegador decidir
readfile($arquivo);
exit;
?>
