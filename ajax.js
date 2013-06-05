/*

Camada de abstração do ajax
Versão 2.1 - 19/11/2012
Guilherme de Oliveira Souza
http://sitegui.com.br

Possui a função Ajax, que recebe como parâmetro um objeto com as seguintes propriedades (todas são opcionais):
- url: o endereço alvo. Pode conter informações GET
- funcao: a função que será executada após uma resposta do servidor.
  Ela irá receber como parâmetro o retorno do servidor
- dados: os dados a serem enviados via GET ou POST.
  Pode ser uma string na forma "var=valor&var2=valor2" ou um objeto com propriedades
  Caso alguma propriedade seja um objeto, ele será transformado em string JSON por JSON.stringify
- metodo: define o método de comunicação ("GET" ou "POST")
- timeout: define o tempo máximo em segundos de espera até retornar um erro (0 significa sem limite)
- retorno: define o tipo de retorno esperado ("text", "xml" ou "json")
- cache: define se permite um retorno direto do cache do browser
- funcaoErro: função que será chamada em caso de erro. Recebe como parâmetro uma exceção caso tenha ocorrido alguma
- funcaoTimeout: funcao que será executada quando a conexão extrapolar o tempo limite

Retorna o objeto XMLHttpRequest
Esse objeto têm um método a mais, "abortar", que permite abortar segurante a requisição

Nas funções, this se refere ao objeto XMLHttpRequest

Os valores padrões são:
{url : "",
funcao : function () {},
dados : "",
metodo : "GET",
timeout : 30,
retorno : "text",
cache : false,
funcaoErro : function (e) {alert("Erro na conexão");console.log(this);console.log(e)},
funcaoTimeout : this.funcaoErro}

Esse valores podem ser modificados nas propriedades Ajax.nomeDeCadaPropriedade

Além disso, tem o construtor CanalAjax, que representa um canal que:
- só executa uma requisição por vez
- enfileira as requisições para serem executadas em ordem

=== Changelog ===
	== 2.1 ==
		- Valores padrões personalizáveis (Ajax.NomeDaPropriedade)
		- Suporte a callbacks na fila do CanalAjax
		- Bug com funções padrão no canal corrigido

	== 2.0 ==
		- CanalAjax adicionado
	
	== 1.3 ==
		- Timeout voltou a funcionar
		- O objeto ajax retornado não é mais poluído com referências aos argumentos
		- O objeto ajax agora possui um método "abortar" que funciona de modo semelhante ao "abort", porém evita timeouts errôneos

	== 1.2 ==
		- Se alguma propriedade do parâmetro dados for um objeto, ele será codificado em JSON com JSON.stringify
		- O objeto this dentro das funções funcao, funcaoErro, funcaoTimeout agora se referem ao objeto XMLHttpRequest (antes era window)
		- A funcaoErro recebe como parâmetro uma exceção caso tenha ocorrido uma
		- O padrão da funcaoErro agora usa console.log para mostrar ao programador o que deu de errado
		- Caso a opção retorno não seja nenhum dentre "text", "xml", "json", será assumido "text" (antes era "json")
*/

function Ajax(opcoes) {
	// Recebe os parâmetros
	var temp, i, valor, ajax, intervalo = null
	opcoes = opcoes || {}
	opcoes.url = opcoes.url || Ajax.url
	opcoes.funcao = opcoes.funcao || Ajax.funcao
	opcoes.dados = opcoes.dados || Ajax.dados
	opcoes.metodo = (opcoes.metodo || Ajax.metodo).toUpperCase()
	opcoes.timeout = opcoes.timeout===undefined ? Ajax.timeout : opcoes.timeout
	opcoes.retorno = (opcoes.retorno || Ajax.retorno).toLowerCase()
	opcoes.cache = opcoes.cache===undefined ? Ajax.cache : opcoes.cache
	opcoes.funcaoErro = opcoes.funcaoErro || Ajax.funcaoErro
	opcoes.funcaoTimeout = opcoes.funcaoTimeout || opcoes.funcaoErro
	
	// Prepara os dados
	if (typeof(opcoes.dados) == "object") {
		temp = []
		for (i in opcoes.dados)
			if (opcoes.dados.hasOwnProperty(i)) {
				valor = typeof(opcoes.dados[i])=="object" ? JSON.stringify(opcoes.dados[i]) : String(opcoes.dados[i])
				temp.push(encodeURIComponent(i)+"="+encodeURIComponent(valor))
			}
		opcoes.dados = temp.join("&")
	}
	
	// Prepara a url
	if (!opcoes.cache)
		opcoes.url = opcoes.url+(opcoes.url.indexOf("?")==-1 ? "?" : "&")+"noCache="+(new Date).getTime()
	if (opcoes.metodo == "GET" && opcoes.dados != "")
		opcoes.url = opcoes.url+(opcoes.url.indexOf("?")==-1 ? "?" : "&")+opcoes.dados
	
	// Cria o objeto
	ajax = new XMLHttpRequest()
	ajax.open(opcoes.metodo, opcoes.url, true)
	ajax.onreadystatechange = function () {
		var resposta, erro = false
		if (this.readyState == 4) {
			clearInterval(intervalo)
			if (this.status == 200) {
				try {
					resposta = opcoes.retorno=="json" ? JSON.parse(this.responseText) : (opcoes.retorno=="xml" ? this.responseXML : this.responseText)
				} catch (e) {
					// Erro no XML ou JSON
					opcoes.funcaoErro.call(this, e)
					erro = true
				}
				if (!erro)
					opcoes.funcao.call(this, resposta)
			} else
				opcoes.funcaoErro.call(this)
			ajax.onreadystatechange = null
		}
	}
	
	// Permite abortar a requisição
	ajax.abortar = function () {
		clearInterval(intervalo)
		ajax.onreadystatechange = null
		ajax.abort()
	}
	
	// Cria o timeout
	if (opcoes.timeout)
		intervalo = setTimeout(function () {
			opcoes.funcaoTimeout.call(ajax)
			ajax.abortar()
		}, opcoes.timeout*1000)
	
	// Envia o pedido
	if (opcoes.metodo == "POST") {
		ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
		ajax.send(opcoes.dados)
	} else
		ajax.send()
	
	// Retorna
	return ajax
}

// Valores padrão das propriedades
Ajax.url = ""
Ajax.funcao = function () {}
Ajax.dados = ""
Ajax.metodo = "GET"
Ajax.timeout = 30
Ajax.retorno = "text"
Ajax.cache = false
Ajax.funcaoErro = function (e) {
	alert("Erro na conexão")
	console.log(this)
	console.log(e)
}
// Ajax.funcaoTimeout = this.funcaoErro (o padrão é em relação ao funçãoErro escolhida)

// Construtor de um canal Ajax
// A propriedade fila armazena todos as requisições na fila,
// sendo fila[0] (se existir) a requisição em andamento (XMLHttpRequest)
// Chama oncarregar(bool carregando) quando o valor de carregando mudar
function CanalAjax() {
	this.fila = []
	this.oncarregar = null
	Object.defineProperty(this, "carregando", {get: function () {
		return Boolean(this.fila.length)
	}, enumerable: true})
}

// Cancela todas as requições em andamento e envia essa
CanalAjax.prototype.enviarDireto = function (opcoes) {
	this.abortar()
	this.enviar(opcoes)
}

// Coloca a requisição atual na fila de envio
// opcoes é o mesmo argumento que seria passado para a função Ajax ou
//   uma função que será executada quando chegar a hora (recebe a instância do CanalAjax como argumento)
CanalAjax.prototype.enviar = function (opcoes) {
	this.fila.push(opcoes)
	this.enviarProximo()
	if (this.oncarregar)
		this.oncarregar(true)
}

// Envia o próximo (uso interno)
CanalAjax.prototype.enviarProximo = function () {
	var capturar, that = this
	
	if (this.fila.length == 0 || this.fila[0] instanceof XMLHttpRequest)
		// Não há nada na fila ou já há uma requisição em andamento
		return;
	
	if (typeof this.fila[0] == "function") {
		// É uma função, simplesmente executa
		this.fila.shift()(this)
		if (this.fila.length == 0 && this.oncarregar)
			this.oncarregar(false)
		this.enviarProximo()
		return
	}
	
	// Altera a função com nome func no objeto obj para que, ao ser executada, executa o espião depois
	capturar = function (obj, func) {
		var antes = obj[func]
		obj[func] = function () {
			that.fila.shift()
			if (that.fila.length == 0 && that.oncarregar)
				that.oncarregar(false)
			if (typeof antes == "function")
				antes.apply(this, arguments)
			that.enviarProximo()
		}
	}
	
	// Coloca um observador nas funções de sucesso e retorno
	this.fila[0].funcao = this.fila[0].funcao || Ajax.funcao
	capturar(this.fila[0], "funcao")
	this.fila[0].funcaoErro = this.fila[0].funcaoErro || Ajax.funcaoErro
	capturar(this.fila[0], "funcaoErro")
	if (this.fila[0].funcaoTimeout)
		capturar(this.fila[0], "funcaoTimeout")
	
	// Envia
	this.fila[0] = Ajax(this.fila[0])
}

// Aborta todas as requisições
CanalAjax.prototype.abortar = function () {
	if (this.fila[0])
		this.fila[0].abortar()
	this.fila = []
	if (this.oncarregar)
		this.oncarregar(false)
}
