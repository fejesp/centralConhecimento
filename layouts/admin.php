<?php
// Protege o acesso à página
if (!$_usuario || !$_usuario['admin'])
	redirecionar('');
gerarJSVar('_meuId', $_usuario['id']);
if (isset($_GET['novoUsuario']))
	gerarJSVar('_novoUsuario', $_GET['novoUsuario']);
?>
<h2>Administração</h2>

<h3>Usuários</h3>
<p><span class="botao" id="criarUsuario"><img src="/imgs/criarUsuario.png"> Criar usuário</span></p>
<table class="usuarios" id="tabelaUsuarios">
<tbody>
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
</tbody>
</table>

<h3>Uso do espaço</h3>
<?php
// Mede o espaço utilizado
$usoAdmin = Query::getValor('SELECT SUM(a.tamanho) FROM anexos AS a JOIN posts AS p ON a.post=p.id JOIN usuarios AS u ON p.criador=u.id WHERE u.admin=1');
$usoNaoAdmin = Query::getValor('SELECT SUM(a.tamanho) FROM anexos AS a JOIN posts AS p ON a.post=p.id JOIN usuarios AS u ON p.criador=u.id WHERE u.admin=0');
$total = $_config['espacoTotal'];
$livre = $total-$usoAdmin-$usoNaoAdmin;
$porcemAdmin = round(100*$usoAdmin/$total);
$porcemNaoAdmin = round(100*$usoNaoAdmin/$total);
?>
<div class="espacoTotal">
	<div class="espacoUsadoAdmin" style="width:<?=$porcemAdmin?>%"><?=$porcemAdmin?>%</div>
	<div class="espacoUsado" style="width:<?=$porcemNaoAdmin?>%"><?=$porcemNaoAdmin?>%</div>
</div>
<p><span class="legendaUsoAdmin">@</span> Espaço utilizado pelo administrador: <?=kiB2str($usoAdmin)?><br>
<span class="legendaUso">@</span> Espaço utilizado pelos outros usuários: <?=kiB2str($usoNaoAdmin)?><br>
<span class="legendaLivre">@</span> Espaço livre: <?=kiB2str($livre)?></p>

<h3>Estatísticas</h3>
<p>[Gráfico de acessos]</p>
<p>[Top10 itens]</p>
