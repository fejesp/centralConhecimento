<h2>Sintaxe de marcação</h2>
<p>É possível usar cabeçalhos, negrito e outros recursos nos conteúdos de posts e formulários.<br>
Abaixo estão listados vários exemplos de como usa-los:</p>

<h3>Negrito e itálico</h3>
<p>Para deixar uma expressão em negrito basta colocar asterisco (*) antes e depois dela. Para o itálico faça o mesmo com underline (_)</p>
<div class="exemplo">
<pre>Um texto com *expressões em negrito*
e também _em itálico_

É possível _*misturar*_ os dois também</pre>
<div>
	<p>Um texto com <strong>express&otilde;es em negrito</strong>
	e tamb&eacute;m <em>em it&aacute;lico</em></p>
	<p>&Eacute; poss&iacute;vel <em><strong>misturar</strong></em> os dois tamb&eacute;m</p>
</div>
</div>

<h3>Links</h3>
<p>Links são criados com base na URL e no texto a ser mostrado, veja o exemplo:</p>
<div class="exemplo">
<pre>Um [link](/pasta/) para a pasta raiz</pre>
<div>
	<p>Um <a href="/pasta/">link</a> para a pasta raiz</p>
</div>
</div>

<h3>Parágrafos</h3>
<p>Um parágrafo é um conjunto de linhas separadas por linhas em branco<br>
Para forçar uma quebra de linha dentro de um parágrafo, termine a linha de cima com 2 espaços</p>
<div class="exemplo">
<pre>Um parágrafo único
sem quebra de linha

Outro parágrafo  
Dessa vez com quebra de linha,
pois a linha terminou com 2 espaços</pre>
<div>
	<p>Um parágrafo único sem quebra de linha</p>
	<p>Outro parágrafo<br>Dessa vez com quebra de linha, pois a linha terminou com 2 espaços</p>
</div>
</div>

<h3>Cabeçalhos</h3>
<p>Um cabeçalho deve começar com 1 ou mais hash-tags (#)</p>
<div class="exemplo">
<pre># Principal
Parágrafo normal

## Sub-título
### Sub-sub-título
Outro parágrafo normal
</pre>
<div>
	<h2>Principal</h2>
	<p>Parágrafo normal</p>
	<h3>Sub-título</h3>
	<h4>Sub-sub-título</h4>
	<p>Outro parágrafo normal</p>
</div>
</div>

<h3>Listas</h3>
<p>Existem dois tipos de listas: ordenadas e não ordenadas</p>
<div class="exemplo">
<pre>Lista simples
* um
* dois
* três

Lista numerada
1. alpha
2. beta
3. gama
</pre>
<div>
	<p>Lista simples</p>
	<ul>
	<li>um</li>
	<li>dois</li>
	<li>três</li>
	</ul>
	<p>Lista numerada</p>
	<ol>
	<li>alpha</li>
	<li>beta</li>
	<li>gama</li>
	</ol>
</div>
</div>

<h3>Régua horizontal</h3>
<div class="exemplo">
<pre>Um assunto
---
Outro</pre>
<div>
	<p>Um assunto</p>
	<hr>
	<p>Outro</p>
</div>
</div>
