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
	<div class="container" onclick="window.location='/'" style="cursor:pointer">
		<img src="/imgs/logo.png" align="absmiddle">
		<h1><span class="cinzaEscuro"><span class="preto">C</span>entral de <span class="vermelho">C</span>onhecimento</span></h1>
	</div>
</header>

<div class="conteudo container">
<div class="menuPrincipal">
	<?php
	if ($_usuario) {
		echo 'Olá ' . $_usuario['nome'] . ', <span class="botao" id="layout-logout"><img src="/imgs/logout.png"> Logout</span>';
		echo '<span class="botao" id="layout-editarUsuario"><img src="/imgs/editarUsuario.png"> Alterar conta</span>';
	} else {
		echo 'Olá anônimo, <span class="botao" id="layout-login"><img src="/imgs/login.png"> Login</span>';
	}
	?>
	<span class="botao" id="layout-buscar"><img src="/imgs/buscar.png"> Buscar</span>
</div>
<?php
require_once "layouts/$_GET[p].php";
?>
</div>

<footer class="container">
	Central de conhecimento da Federação de Empresas Juniores do Estado de São Paulo<br>
	Desenvolvido por FEJESP/núcleo TI (ti@fejesp.org.br)
</footer>
</body>
</html>