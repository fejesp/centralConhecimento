<?php
// Protege o acesso à página
if (!$_usuario || !$_usuario['admin'])
	redirecionar('index');
gerarJSVar('_meuId', $_usuario['id']);
if (isset($_GET['novoUsuario']))
	gerarJSVar('_novoUsuario', $_GET['novoUsuario']);
?>
<h2>Administração</h2>

<h3>Usuários</h3>
<p><span class="botao" id="criarUsuario"><img src="/imgs/criarUsuario.png"> Criar usuário</span></p>
<table id="tabelaUsuarios">
	<tr><th>Nome</th><th>Email</th><th>Acessos esse ano</th><th>Espaço usado</th></tr>
	<?php
	// Carrega os dados
	$usuarios = Query::query(false, NULL, 'SELECT id, nome, email, ativo, usoMax FROM usuarios ORDER BY nome');
	$acessos = Query::query(false, NULL, 'SELECT u.id, COUNT(*) AS acessos FROM usuarios AS u JOIN logins AS l ON l.usuario=u.id WHERE l.sucesso=1 AND YEAR(l.data)=YEAR(NOW()) GROUP BY u.id');
	$usos = Query::query(false, NULL, 'SELECT u.id, SUM(a.tamanho) AS uso FROM usuarios AS u JOIN posts AS p ON p.criador=u.id JOIN anexos AS a ON a.post=p.id GROUP BY u.id');
	
	// Coloca num formato melhor
	$dados = array();
	foreach ($usuarios as $cada) {
		$dados[$cada['id']] = $cada;
		$dados[$cada['id']]['acessos'] = 0;
		$dados[$cada['id']]['uso'] = 0;
	}
	foreach ($acessos as $cada)
		$dados[$cada['id']]['acessos'] = $cada['acessos'];
	foreach ($usos as $cada)
		$dados[$cada['id']]['uso'] = $cada['uso'];
	
	// Monta a tabela de usuários
	foreach ($dados as $cada) {
		$inativo = $cada['ativo'] ? '' : 'inativo';
		$nome = assegurarHTML($cada['nome']);
		$email = assegurarHTML($cada['email']);
		echo "<tr data-id='$cada[id]' data-nome='$nome' data-email='$email' data-uso-max='$cada[usoMax]' class='$inativo'>";
		imprimir($cada['nome'], 'td');
		imprimir($cada['email'], 'td');
		imprimir($cada['acessos'], 'td');
		if ($cada['usoMax'])
			imprimir(kiB2str($cada['uso']) . ' de ' . kiB2str($cada['usoMax']), 'td');
		else
			imprimir(kiB2str($cada['uso']), 'td');
		echo '</tr>';
	}
	?>
</table>

<h3>Uso do espaço</h3>
<?php
// Mede o espaço utilizado
$usoAdmin = Query::getValor('SELECT SUM(a.tamanho) FROM anexos AS a JOIN posts AS p ON a.post=p.id JOIN usuarios AS u ON p.criador=u.id WHERE u.admin=1');
$usoNaoAdmin = Query::getValor('SELECT SUM(a.tamanho) FROM anexos AS a JOIN posts AS p ON a.post=p.id JOIN usuarios AS u ON p.criador=u.id WHERE u.admin=0');
$usoComReserva = Query::getValor('SELECT SUM(a.tamanho) FROM anexos AS a JOIN posts AS p ON a.post=p.id JOIN usuarios AS u ON p.criador=u.id WHERE u.usoMax>0');
$reserva = Query::getValor('SELECT SUM(usoMax) FROM usuarios');
$total = $_config['espacoTotal'];
$livre = $total-$usoAdmin-$usoNaoAdmin;
$usoLivreReservado = $reserva-$usoComReserva;
$porcemAdmin = round(100*$usoAdmin/$total);
$porcemNaoAdmin = round(100*$usoNaoAdmin/$total);
$porcemLivreReservado = round(100*$usoLivreReservado/$total);
?>
<div class="espacoTotal">
	<div class="espacoUsadoAdmin" style="width:<?=$porcemAdmin?>%"><?=$porcemAdmin?>%</div>
	<div class="espacoUsado" style="width:<?=$porcemNaoAdmin?>%"><?=$porcemNaoAdmin?>%</div>
	<div class="espacoLivreReservado" style="width:<?=$porcemLivreReservado?>%"><?=$porcemLivreReservado?>%</div>
</div>
<p><span class="legendaUsoAdmin">@</span> Espaço utilizado pelo administrador: <?=kiB2str($usoAdmin)?><br>
<span class="legendaUso">@</span> Espaço utilizado pelos outros usuários: <?=kiB2str($usoNaoAdmin)?><br>
<span class="legendaLivreReservado">@</span> Espaço livre reservado para outros usuários: <?=kiB2str($usoLivreReservado)?><br>
<span class="legendaLivre">@</span> Espaço livre: <?=kiB2str($livre)?></p>

<h3>Anexos mais baixados</h3>
<table id="tabelaDownloads">
<tr><th>Post</th><th>Anexo</th><th>Downloads</th></tr>
<?php
// Carrega os itens mais acessados
$query = 'SELECT
	a.id AS id, p.id AS idPost, p.nome AS post, a.nome AS anexo, COUNT(*) AS downloads
	FROM posts AS p
	JOIN anexos AS a ON a.post=p.id
	JOIN downloads AS d ON d.anexo=a.id
	GROUP BY a.id
	ORDER BY COUNT(*) DESC
	LIMIT 7';
foreach (Query::query(false, NULL, $query) as $cada) {
	echo "<tr data-id='$cada[id]' data-id-post='$cada[idPost]' data-nome='" . assegurarHTML($cada['anexo']) . "'>";
	imprimir($cada['post'], 'td.nomePost');
	imprimir($cada['anexo'], 'td');
	imprimir($cada['downloads'], 'td');
	echo '</tr>';
}
?>
</table>
<div style="text-align:center">
	<span class="botao" id="btMaisDownloads"><img src="/imgs/praBaixo.png"> Mostrar tudo</span>
</div>

<h3>Downloads externos</h3>
<table id="tabelaDownloadsExternos">
<tr><th>Email</th><th>Empresa</th><th>Downloads</th></tr>
<?php
// Carrega os maiores downloaders externos
$query = 'SELECT
	email, empresa, COUNT(*) AS downloads
	FROM downloads
	WHERE usuario IS NULL
	GROUP BY email, empresa
	ORDER BY COUNT(*) DESC
	LIMIT 7';
foreach (Query::query(false, NULL, $query) as $cada) {
	echo '<tr data-email="' . assegurarHTML($cada['email']) . '" data-empresa="' . assegurarHTML($cada['empresa']) . '">';
	imprimir($cada['email'], 'td');
	imprimir($cada['empresa'], 'td');
	imprimir($cada['downloads'], 'td');
	echo '</tr>';
}
?>
</table>
<div style="text-align:center">
	<span class="botao" id="btMaisDownloadsExternos"><img src="/imgs/praBaixo.png"> Mostrar tudo</span>
</div>
