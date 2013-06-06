<?php
$pagina = $_GET['pagina'];
?><!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<title>Central de conhecimento - FEJESP</title>
<link rel="stylesheet" href="layout.css">
<link rel="stylesheet" href="layouts/<?=$pagina;?>.css">
<link rel="shortcut icon" href="imgs/logoCC.png">
<script src="ajax.js"></script>
</head>

<body>
<header>
	<div class="container">
		<a href="http://fejesp.org.br"><img src="imgs/logo.png" align="absmiddle"></a>
		<h1><span class="cinzaEscuro"><span class="preto">C</span>entral de <span class="vermelho">C</span>onhecimento</span></h1>
	</div>
</header>

<div class="conteudo container">
<div class="menu">Menu</div>
<?php
readfile("layouts/$pagina.html");
?>
</div>

<footer class="container">
	Central de conhecimento da Federação de Empresas Juniores do Estado de São Paulo<br>
	Desenvolvido por FEJESP/núcleo TI (ti@fejesp.org.br)
</footer>
</body>
</html>