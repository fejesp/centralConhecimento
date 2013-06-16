<?php
/*
 * Central de conhecimento FEJESP
 * Contato: ti@fejesp.org.br
 * Autor: Guilherme de Oliveira Souza (http://sitegui.com.br)
 * Data: 15/06/2013
*/

// Converte texto simples em HTML com base numa sintaxe parecida com markdown
// A sintaxe está descrita em sintaxeHTML.md
function gerarHTML($str) {
	// Separa as linhas da entrada
	$linhas = explode("\n", str_replace("\r", '', $str));
	
	// Classifica as linhas em tipos
	$tiposLinhas = array();
	for ($i=0; $i<count($linhas); $i++) {
		if (preg_match('@^[ \t]*$@', $linhas[$i]))
			$tiposLinhas[] = '';
		else if (preg_match('@^[-*+ ]{3,}$@', $linhas[$i]))
			$tiposLinhas[] = 'hr';
		else if (preg_match('@^#{1,5} @', $linhas[$i], $matches)) {
			$tiposLinhas[] = 'h' . strlen($matches[0]);
			$linhas[$i] = substr($linhas[$i], strlen($matches[0]));
		} else if (preg_match('@^> @', $linhas[$i])) {
			$tiposLinhas[] = 'blockquote';
			$linhas[$i] = substr($linhas[$i], 2);
		} else if (preg_match('@^[*+-] @', $linhas[$i])) {
			$tiposLinhas[] = 'ul';
			$linhas[$i] = substr($linhas[$i], 2);
		} else if (preg_match('@^[1-9][0-9]?\. @', $linhas[$i], $matches)) {
			$tiposLinhas[] = 'ol';
			$linhas[$i] = substr($linhas[$i], strlen($matches[0]));
		} else
			$tiposLinhas[] = 'p';
	}
	
	// Vai gerando o html
	$cache = '';
	$tipoAntes = '';
	$html = '';
	for ($i=0; $i<count($linhas); $i++) {
		$tipo = $tiposLinhas[$i];
		$linha = assegurarHTML($linhas[$i]);
		if (substr($tipo, 0, 1) == 'h') {
			// Elementos imediatos: hr, h2, h3, h4, h5, h6
			auxGerarHTML($cache, $html, $tipoAntes);
			if ($tipo == 'hr')
				$html .= "<hr>\n";
			else
				$html .= "<$tipo>$linha</$tipo>\n";
			$tipo = '';
		} else {
			if (substr($linha, -2) == '  ')
				$linha .= "<br>\n";
			else
				$linha .= "\n";
			if ($tipo == 'p') {
				$cache .= $linha;
				$tipo = $tipoAntes;
			} else if ($tipo == 'ul' || $tipo == 'ol') {
				auxGerarHTML($cache, $html, $tipoAntes);
				$cache = $linha;
			} else if ($tipo == 'blockquote') {
				if ($tipoAntes != $tipo)
					auxGerarHTML($cache, $html, $tipoAntes);
				$cache .= $linha;
			} else if ($tipo == '') {
				auxGerarHTML($cache, $html, $tipoAntes);
			}
		}
		$tipoAntes = $tipo;
	}
	auxGerarHTML($cache, $html, $tipoAntes);
	auxGerarHTML($cache, $html, ''); // Fecha listas abertas
	
	return $html;
}

// Função auxiliar de gerarHTML
function auxGerarHTML(&$cache, &$html, $tipo) {
	static $lista = '';
	if ($lista && $tipo != $lista) {
		$html .= "</$lista>\n";
		$lista = '';
	}
	if ($cache) {
		$cache = substr($cache, 0, -1);
		$cache = preg_replace('@(^|[\s_])\*([^ ][^*]*?)(?<! )\*($|[\s_])@', '$1<strong>$2</strong>$3', $cache);
		$cache = preg_replace('@(^|[\s*])_([^ ][^*]*?)(?<! )_($|[\s*])@', '$1<em>$2</em>$3', $cache);
		$cache = preg_replace('@\[([^]]+)\]\(([^)]+)\)@', '<a href="$2">$1</a>', $cache);
		if ($tipo == 'ul' || $tipo == 'ol') {
			if ($lista != $tipo)
				$html .= "<$tipo>\n";
			$lista = $tipo;
			$html .= "<li>$cache</li>\n";
		} else if ($tipo == 'blockquote') {
			$html .= "<blockquote>$cache</blockquote>\n";
		} else if ($tipo == '') {
			$html .= "<p>$cache</p>\n";
		}
		$cache = '';
	}
}
