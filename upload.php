<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<title>Untitled Document</title>
</head>

<body>
<form method="post" enctype="multipart/form-data">
<input name="nome"><br>
<input type="file" name="lista[abc]"> <input name="dicas[abc]"><br>
<input type="file" name="lista[def]"> <input name="dicas[def]"><br>
<input type="submit">
</form>
<pre>
<?php
var_dump($_POST);
var_dump($_FILES);
?>
</pre>
</body>
</html>