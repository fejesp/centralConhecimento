setBotao("criarUsuario", function () {
	editarUsuario(null)
})

// Exibe a senha do usuário criado
window.addEventListener("load", function () {
	if (_novoUsuario) {
		mostrarJanela(true)
		get("janela").innerHTML = "<h2>Novo usuário</h2>"+
		"<p>A senha do novo usuário é: <strong>"+_novoUsuario+"</strong><br>Informe o usuário sobre isso</p>"+
		"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span></p>"
	}
})

// Coloca os menus em cada linha de usuários
window.addEventListener("load", function () {
	var linhas, i
	linhas = get("tabelaUsuarios").rows
	for (i=1; i<linhas.length; i++)
		linhas.item(i).oncontextmenu = function (evento) {
			var botoes = [], ativo, id, linha
			linha = evento.currentTarget
			ativo = !linha.classList.contains("inativo")
			id = Number(linha.dataset.id)
			if (id != _meuId) {
				botoes.push([ativo ? "<img src='/imgs/desativar.png'> Desativar" : "<img src='/imgs/ativar.png'> Ativar", function () {
					Ajax({url: "/ajax.php", dados: {op: ativo ? "desativarUsuario" : "ativarUsuario", id: id}, funcao: function (ativo) {
						linha.classList[ativo ? "remove" : "add"]("inativo")
					}, retorno: "json"})
				}])
				botoes.push(["<img src='/imgs/gerarSenha.png'> Gerar senha", function () {
					if (confirm("Deseja realmente gerar uma nova senha aleatória para esse usuário?"))
						Ajax({url: "/ajax.php", dados: {op: "gerarSenha", id: id}, funcao: function (senha) {
							mostrarJanela(true)
							get("janela").innerHTML = "<h2>Nova senha</h2>"+
								"<p>A nova senha é: <strong>"+senha+"</strong><br>Informe o usuário sobre essa mudança</p>"+
								"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span></p>"
						}, retorno: "json"})
				}])
			}
			botoes.push(["<img src='/imgs/editar.png'> Editar", function () {
				editarUsuario(linha)
			}])
			Menu.abrir(evento, botoes)
		}
})

// Abre a janela de edição de um usuário
// Recebe o elemento TR do usuário na tabela
function editarUsuario(linha) {
	var id, nome, email, usoMax, janela
	
	// Carrega os dados
	if (linha) {
		id = linha.dataset.id
		nome = linha.dataset.nome
		email = linha.dataset.email
		usoMax = linha.dataset.usoMax/1024
	} else {
		id = nome = email = ""
		usoMax = 10
	}
	
	// Monta a janela
	mostrarJanela(true)
	janela = get("janela")
	janela.innerHTML = "<h2>Editar usuário</h2>"+
		"<form method='post' action='/editarUsuarioAdmin.php"+(linha ? "" : "?criar")+"'>"+
		"<div class='rotuloEsquerdo'>Nome: <br>Email: <br>Quota de uso de espaço: </div>"+
		"<div class='opcoesDireita'><input size='30' name='nome' value='"+assegurarHTML(nome)+"' required><br>"+
		"<input size='30' name='email' type='email' value='"+assegurarHTML(email)+"' required><br>"+
		"<input name='usoMax' size='10' value='"+usoMax+"' required type='number' min='0' step='1'> MiB (0 = sem limites)</div>"+
		"<div class='clear'></div>"+
		"<input name='id' type='hidden' value='"+id+"'><input type='submit' style='display:none' id='submit'>"+
		"<p><span class='botao' onclick='mostrarJanela(false)'><img src='/imgs/voltar.png'> Voltar</span> "+
		"<span class='botao' onclick='get(\"submit\").click()'><img src='/imgs/enviar.png'> Salvar</span></p>"+
		"</form>"
}
