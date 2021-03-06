<!DOCTYPE HTML>
<html>
<head>
<!--
 - Central de conhecimento FEJESP
 - Contato: ti@fejesp.org.br
 - Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 - Data: 06/06/2013
-->
<meta charset="utf-8">
<title>Central de conhecimento - FEJESP</title>
<link rel="stylesheet" href="/layout.css">
<?php
if (file_exists("css/$_GET[p].css"))
	echo "<link rel='stylesheet' href='/css/$_GET[p].css'>";
?>
<link rel="shortcut icon" href="/imgs/logoCC.png">
<script src="/ajax.js"></script>
<script src="/utils.js"></script>
<script src="/layout.js"></script>
<?php
if (file_exists("js/$_GET[p].js"))
	echo "<script src='/js/$_GET[p].js'></script>";
?>
</head>

<body>
<header>
	<a class="container" href="/">
		<img src="/imgs/logo.png" align="absmiddle">
		<h1><span class="cinzaEscuro"><span class="preto">C</span>entral de <span class="vermelho">C</span>onhecimento</span></h1>
	</a>
</header>

<div class="conteudo container">
<div class="menuPrincipal">
	<?php
	if ($_usuario) {
		echo 'Olá ' . assegurarHTML($_usuario['nome']) . ', <a class="botao" href="/logout.php"><img src="/imgs/logout.png"> Logout</a> ';
		echo '<a href="/editarUsuario" class="botao"><img src="/imgs/editarUsuario.png"> Alterar conta</a> ';
		if ($_usuario['admin'])
			echo '<a class="botao" href="/admin"><img src="/imgs/admin.png"> Administração</a> ';
	} else
		echo 'Olá anônimo, <span class="botao" id="layout-login"><img src="/imgs/login.png"> Login</span> ';
	?>
	<a class="botao" href="/busca" id="layout-buscar"><img src="/imgs/buscar.png"> Buscar</a>
</div>
<?php
require_once "layouts/$_GET[p].php";
?>
</div>

<footer class="container">
	Central de conhecimento da Federação de Empresas Juniores do Estado de São Paulo<br>
	Desenvolvido por FEJESP/núcleo de TI (<a href="/contato">contato</a>)
</footer>

<div class="fundoJanela" style="display:none" id="fundoJanela"></div>
<div class="janela" style="display:none" id="janela"></div>
</body>
</html>