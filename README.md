# Central de Conhecimento - melhorBusca

## Descrição
Este branch implementa um novo sistema de busca, melhorando a interface, o algoritmo e a exibição dos resultados.

Abaixo algumas ideias, não necessariamente todas serão implementadas.

## Interface
* Dicas com base em tags ou expressões comuns (análise automática dos textos)
* (feito) Ao clicar no botão buscar numa página de pasta, a busca começa por aquela pasta (mas pode-se buscar no sistema todo, como antes)
* (feito) Melhor explicação da sintaxe de busca (expressões com "" e evitar termos com -)

## Algoritmo
* Leva em conta as tags dos posts
* (feito) Leva em conta todo o caminho do item (exemplo: o post "B" na pasta "A" é um resultado para "A B"). Veja a imagem busca.jpg
* (feito) Pode começar a partir de qualquer pasta, não somente da raiz

## Resultados
* (feito) Agrupar resultados por pasta
