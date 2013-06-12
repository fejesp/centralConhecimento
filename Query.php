<?php
/*

Camada de abstração para fazer consultas query ao banco de dados
Versão 2.1 - 06/11/2012
Guilherme de Oliveira Souza
http://sitegui.com.br

Changelog:
- 2.1
	Suporte a argumentos de array indexadas, útil para UPDATEs: new Query("UPDATE t SET ? WHERE id=?", array('a' => 17), 27)
	Suporte a argumentos de objetos com o método __toString definido
	Métodos estáticos de apoio sintático (query, existe, getValor)
- 2.0
	Troca do driver do MySQL, da versão antiga mysql para a nova mysqli
	Reescrito com orientação a objetos (classe Query)
- 1.1
	Erro na função query quando algum dos argumentos é uma string com o caractere '?'
	Mensagens de erro mais completas

*/

class Query {
	// Armazena os tipos
	public static $tipos = array(
		'int' => array(
			MYSQLI_TYPE_BIT,
			MYSQLI_TYPE_TINY,
			MYSQLI_TYPE_SHORT,
			MYSQLI_TYPE_LONG,
			MYSQLI_TYPE_LONGLONG,
			MYSQLI_TYPE_INT24
		), 'float' => array(
			MYSQLI_TYPE_DECIMAL,
			MYSQLI_TYPE_NEWDECIMAL,
			MYSQLI_TYPE_FLOAT,
			MYSQLI_TYPE_DOUBLE
		)
	);
	
	// Armazena a conexão com o banco de dados
	// Deve ser criada com algo do tipo:
	// Query::$conexao = new MySQLi('localhost', 'usuario', 'senha', 'bd');
	// Query::$conexao->set_charset('utf8');
	public static $conexao;
	
	// Indica se new Query deve gerar os delimitadores '' e () também
	public static $delimitadores = true;
	
	// Armazena o objeto MySQLi_Resul
	public $result = NULL;
	
	// Monta e executa uma query partindo de um padrão base com '?' marcando lugares para adicionar informações
	// O primeiro argumento é a query base, os restantes são os valores para montar a query
	// Essa função já cuida de usar addslashes nos argumentos
	// Retorna a query executada por MySQLi::query
	// Uso:         new Query("SELECT id FROM usuarios WHERE login=? LIMIT 1", $login);
	// O mesmo que: mysqli_query($link, "SELECT id FROM usuarios WHERE login='" . addslashes($login) . "' LIMIT 1");
	// Para usar um '?' literal, basta escapa-lo com uma barra invertida: '\\?'
	// A função trata strings, inteiros e ararys de forma diferente. Veja mais detalhes em prepararArg
	// Por padrão, ela já coloca parênteses e aspas onde necessário (como no exemplo abaixo)
	// new Query('DELETE FROM usuarios WHERE id IN ? OR nome=?', array(3, 1, 4), 'gui')
	// Fica "DELETE FROM usuarios WHERE id IN (3,1,4) OR nome='gui'"
	// Para desligar isso, altere o valor de Query::$delimitadores para false
	public function __construct($base='' /*, $args... */) {
		$query = '';
		$ultimo = 0;
		$arg = 1;
		$numArgs = func_num_args();
		
		// Vazio, ok
		if ($numArgs == 0)
			return;
		
		// Percorre a string em busca de um '?' não escapado
		for ($i=0, $len=strlen($base); $i<$len; $i++)
			if ($base[$i] == '?' && ($i == 0 || $base[$i-1] != '\\')) {
				if ($numArgs <= $arg)
					throw new ErrorException("Não encontrado ${arg}º argumento, esperado para incluir na posição $i da $query");
				$query .= substr($base, $ultimo, $i-$ultimo) . $this->prepararArg(func_get_arg($arg));
				$ultimo = $i+1;
				$arg++;
			}
		$query .= substr($base, $ultimo);
		
		// Executa a query
		if (!(Query::$conexao instanceof MySQLi))
			throw new ErrorException('Conexão não estabelecida');
		if (!($this->result = Query::$conexao->query($query)))
			throw new ErrorException("Erro na execução da query: $query\nErro: " . Query::$conexao->error);
	}
	
	// Executa a query e retorna tudo o que ela retornou
	// Simplificação sintática para new Query()->get()
	// Uso: $usuarios = Query::query(false, 0, 'SELECT id FROM usuarios');
	public static function query($primeiro /*=false*/, $coluna /*=NULL*/, $base /*, $args... */) {
		$novo = new Query;
		$obrigadoPHPPorSerMuitoGambiarrado = func_get_args();
		call_user_func_array(array($novo, '__construct'), array_slice($obrigadoPHPPorSerMuitoGambiarrado, 2));
		return $novo->get($primeiro, $coluna);
	}
	
	// Verifica se a query retorna ao menos um resultado
	// Simplificação sintática para (bool)(new Query()->result->num_rows)
	// Uso: if (!Query::existe('SELECT 1 FROM usuarios WHERE nome=? AND senha=?', $nome, $senha)) exit;
	public static function existe($base /*, $args... */) {
		$novo = new Query;
		$obrigadoPHPPorSerMuitoGambiarrado = func_get_args();
		call_user_func_array(array($novo, '__construct'), $obrigadoPHPPorSerMuitoGambiarrado);
		return (bool)($novo->result->num_rows);
	}
	
	// Pega o primeiro valor retornado pela query (ou NULL caso nenhum tenha sido retornado)
	// Simplificação sintática para try {$valor = Query::query(true, 0, '...');} catch (Exception $e) {$valor = NULL;}
	// Uso: $id = Query::getValor('SELECT id FROM usuarios WHERE nome=?', $nome);
	public static function getValor($base /*, $args... */) {
		try {
			$novo = new Query;
			$obrigadoPHPPorSerMuitoGambiarrado = func_get_args();
			call_user_func_array(array($novo, '__construct'), $obrigadoPHPPorSerMuitoGambiarrado);
			return $novo->get(true, 0);
		} catch (Exception $e) {
			return NULL;
		}
	}
	
	// Retorna o resultado na forma de uma array bidimensional em que cada elemento da array 
	//		é uma array indexada pelo nome da coluna que representa uma linha do resultado
	// Útil para extrair todos os resultados de uma query (elimina o uso de MySQLi::query e MySQLi_Result::fetch_*)
	// Caso alguma coluna seja do tipo inteiro, o valor retornado será um inteiro
	// Caso alguma coluna seja do tipo real, o valor retornado será um float
	// $primeiro é um booleano que indica se somente a primeira linha deve ser retornada (padrão: false)
	// Caso $primeiro seja true, o resultado será uma array unidimensional
	// $coluna indica qual coluna (nome ou índice) retornar (padrão: NULL = todas)
	// Caso $coluna não seja NULL, cada linha do resultado não será uma array e sim um valor escalar (string, int, float ou null)
	// Se $primeiro for true e $coluna não for NULL, o retorno será um valor escalar
	// Lança uma exceção ErrorException em caso de erro
	public function get($primeiro=false, $coluna=NULL) {
		// Valida o resultado
		if (!is_object($this->result))
			throw new ErrorException('A query não é SELECT, SHOW, DESCRIBE ou EXPLAIN');
		
		// Pega o nome e o tipo dos campos
		$num_cols = $this->result->field_count;
		$cols = array();
		for ($i=0; $i<$num_cols; $i++) {
			$cols[$i] = $this->result->fetch_field();
			if (is_string($coluna) && $cols[$i]->name == $coluna)
				$coluna = $i;
			// Decide o tipo da coluna
			if (in_array($cols[$i]->type, Query::$tipos['int']))
				$cols[$i]->tipo = 'int';
			else if (in_array($cols[$i]->type, Query::$tipos['float']))
				$cols[$i]->tipo = 'float';
			else
				$cols[$i]->tipo = 'string';
		}
		
		// Trata os parâmetros
		$num_rows = $this->result->num_rows;
		if ($primeiro)
			if ($num_rows<1)
				throw new ErrorException('Nenhum resultado retornado');
			else
				$num_rows = 1;
		if ($coluna !== NULL && (!is_numeric($coluna) || !isset($cols[$coluna])))
			throw new ErrorException("Coluna $coluna não encontrada");
		
		// Pega os resultados
		$retorno = array();
		if ($num_rows)
			$this->result->data_seek(0);
		for ($i=0; $i<$num_rows; $i++) {
			$row = $this->result->fetch_row();
			$row2 = array();
			
			// Modifica os tipos necessários
			for ($j=0; $j<$num_cols; $j++) {
				if ($coluna && $coluna != $j) continue;
				if ($cols[$j]->tipo == 'int' && $row[$j] !== NULL)
					$row2[$cols[$j]->name] = (int)$row[$j];
				else if ($cols[$j]->tipo == 'float' && $row[$j] !== NULL)
					$row2[$cols[$j]->name] = (float)$row[$j];
				else
					$row2[$cols[$j]->name] = $row[$j];
			}
			
			// Salva a resposta
			$retorno[] = $coluna===NULL ? $row2 : $row2[$cols[$coluna]->name];
		}
		
		// Diminui em 1 grau a dimensão da array caso só se queira uma linha
		if ($primeiro)
			$retorno = $retorno[0];
		
		return $retorno;
	}
	
	/* Função auxiliar de montagem da query. Veja o comportamento pela tabela:
	+--------------------------+--------------------+------------------+
	|Argumento                 |Com delimitadores   |Sem delimitadores |
	+--------------------------+--------------------+------------------+
	|array(1, 2, 3)            |"(1,2,3)"           |"1,2,3"           |
	|array('hello', 'world')   |"('hello','world')" |"hello,world"     |
	|array('a'=>'b+1', 'c'=>2) |"a='b+1', c=2"      |"a=b+1, c=2"      |
	|17                        |"17"                |"17"              |
	|"com ' aspas"             |"'com \\' aspas'"   |"com \\' aspas"   |
	|true                      |"1"                 |"1"               |
	|false                     |"0"                 |"0"               |
	|NULL                      |"NULL"              |"NULL"            |
	|new ClasseComToString()   |"'retornoToString'" |"retornoToString" |
	+--------------------------+--------------------+------------------+
	*/
	private function prepararArg($valor) {
		if (is_array($valor)) {
			if (isset($valor[0])) {
				$retorno = Query::$delimitadores ? '(' : '';
				for ($i=0, $l=count($valor); $i<$l; $i++)
					$retorno .= ($i ? ',' : '') . $this->prepararArg($valor[$i]);
				return $retorno . (Query::$delimitadores ? ')' : '');
			} else {
				$retorno = '';
				$primeiro = true;
				foreach ($valor as $nome=>$valor2) {
					$retorno .= ($primeiro ? '' : ', ') . $nome . '=' . $this->prepararArg($valor2);
					$primeiro = false;
				}
				return $retorno;
			}
		} else if (is_string($valor))
			return (Query::$delimitadores ? '\'' : '') . Query::$conexao->real_escape_string($valor) . (Query::$delimitadores ? '\'' : '');
		else if (is_bool($valor))
			return $valor ? '1' : '0';
		else if (is_null($valor))
			return 'NULL';
		else if (is_object($valor))
			return $this->prepararArg((string)$valor);
		else
			return (string)$valor;
	}
}
