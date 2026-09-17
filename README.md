# Eventra

Plataforma de venda e gestão de ingressos para eventos. O organizador cadastra o
evento e os lotes de ingresso, o cliente compra, recebe um ingresso com QR Code e
apresenta na entrada, onde o organizador faz o check-in pelo código.

Escrito em PHP 8 puro: sem framework, sem Composer, sem nenhuma dependência
externa. Roda num XAMPP local ou em qualquer hospedagem compartilhada com PHP e
MySQL.

No ar em **https://eventra.freedev.app**

## Rodando localmente

É preciso PHP 8, MySQL e as extensões `pdo_mysql` e `curl` — um XAMPP padrão já
traz tudo isso.

**1.** Clone o projeto dentro do webroot (`C:\xampp\htdocs`, por exemplo) e suba
o Apache e o MySQL.

**2.** No phpMyAdmin, importe `database/schema.sql`. Ele cria o banco
`ingressos_app`, as tabelas e alguns eventos de exemplo, para a listagem não
ficar vazia logo de cara.

**3.** Copie `config/config.example.php` para `config/config.php` e ajuste as
credenciais do banco. Num XAMPP recém-instalado, `root` sem senha costuma
funcionar. O `config.php` está no `.gitignore` e não deve ser versionado.

**4.** Abra `http://localhost/ingressos-app/public`.

## O que dá para fazer

Como cliente: navegar e buscar eventos, montar carrinho respeitando o limite por
lote, finalizar a compra, ver o histórico em Minhas Compras e abrir cada ingresso
com seu QR Code.

Como organizador: um dashboard com vendas e receita, cadastro de eventos com
capa (upload ou URL) e seleção encadeada de estado e cidade cobrindo os 5.570
municípios do IBGE, criação de lotes com preço, quantidade e janela de venda,
acompanhamento dos pedidos recebidos e check-in por código na portaria. A conta
de organizador também compra normalmente.

## Estrutura

```
config/     configuração (o config.php real fica fora do git)
database/   schema.sql com estrutura e dados de exemplo
src/
  bootstrap.php   carrega config, sessão, banco e todas as classes
  Database.php    conexão PDO compartilhada
  Auth.php        login, registro e os guards de acesso
  Helpers.php     escape, CSRF, flash, slug, formatação
  Models/         EventModel, TicketType, OrderModel, Ticket, User
  Services/       CartService, TicketService, MercadoPagoService, LocationService
templates/  header, nav e footer compartilhados
public/     webroot: páginas, painel admin e endpoints
storage/    cache de municípios (gerado em runtime, fora do git)
```

Não há roteador: cada arquivo em `public/` é uma página, e todos começam
incluindo o `bootstrap.php`. As páginas fazem o papel de controller, os models
cuidam do SQL e os services concentram as regras que não cabem em nenhum dos
dois.

## Decisões que valem comentar

**Estoque é transacional.** `TicketType::reserveStock()` incrementa
`quantity_sold` de forma atômica e a compra inteira roda dentro de uma transação.
Duas pessoas comprando o último ingresso ao mesmo tempo não conseguem furar o
limite — a segunda recebe erro e o estoque já reservado é devolvido.

**Upload de capa valida conteúdo, não extensão.** O tipo vem do `finfo` e do
`getimagesize`, o arquivo é gravado com nome aleatório e há limite de 4 MB.
Renomear um `.php` para `.jpg` não passa.

**A lista de municípios tem plano B.** A API do IBGE cai com alguma frequência,
então a consulta tenta o IBGE, cai para a BrasilAPI e guarda o resultado em disco
por 30 dias. Se as duas fontes estiverem fora do ar, um cache vencido ainda é
melhor que um combo vazio.

**Confirmação de pagamento é idempotente.** `TicketService::confirmPayment()`
pode ser chamada tanto pelo webhook quanto pela página de retorno, e as duas
podem chegar. Ela verifica o status do pedido e a existência de ingressos antes
de gerar qualquer coisa, então ninguém recebe ingresso duplicado.

Além disso: senhas com `password_hash()`, prepared statements em todas as
queries, token CSRF nos formulários que alteram estado, escape de saída pelo
helper `e()` e um check-in que confere se o ingresso pertence mesmo a um evento
do organizador logado.

## Pagamento

A cobrança é feita pelo **Mercado Pago (Checkout Pro)**. O fluxo é:

1. O cliente finaliza o carrinho. O pedido nasce como `pending` e o estoque já
   fica reservado, para ninguém comprar o mesmo lugar duas vezes.
2. O sistema cria uma preferência de pagamento e redireciona o cliente para o
   Checkout Pro.
3. Pagamento aprovado, o cliente volta ao site automaticamente (`auto_return`) e
   os ingressos são emitidos.
4. Pagamento recusado ou cancelado, o estoque reservado volta para o evento.

Ingressos com preço zero não passam pelo Mercado Pago, que recusa itens sem
valor. Eles aparecem como **Grátis** e a reserva é confirmada na hora.

### Pedido que ficou pendente

Se o cliente fechar a tela de pagamento antes de pagar, o pedido continua
acessível: em **Minhas Compras** aparece o botão *Pagar pedido #N*, que gera uma
nova cobrança para o mesmo pedido. Antes de cobrar, o sistema consulta o Mercado
Pago para garantir que aquele pedido não foi pago enquanto isso — assim ninguém
paga duas vezes.

Abrir **Minhas Compras** também reconcilia pedidos pendentes: o sistema pergunta
ao Mercado Pago o estado real de cada um e emite os ingressos se já houver
pagamento aprovado. Isso cobre o caso de a notificação automática não chegar, o
que acontece em hospedagens que exigem JavaScript para responder — o servidor do
Mercado Pago não executa JS e nunca alcança o webhook.

### Configuração

Preencha `mercadopago.access_token` e `mercadopago.public_key` em
`config/config.php` com as credenciais da sua aplicação em
[mercadopago.com.br/developers](https://www.mercadopago.com.br/developers/panel).

| Credencial | Quando usar |
|---|---|
| Teste | Validar a integração. Nenhum dinheiro real é movimentado e só cartões de teste funcionam. |
| Produção | Vender de verdade. Obrigatória para receber de compradores reais. |

O `base_url` precisa ser um endereço público em `https://` para o retorno
automático funcionar. Em `localhost` o Mercado Pago recusa as URLs de retorno,
então o cliente não volta sozinho — a compra é confirmada ao abrir Minhas
Compras. Para testar o retorno localmente, exponha a porta com
[ngrok](https://ngrok.com/) e aponte o `base_url` para a URL gerada.

Boleto e pagamento em lotérica ficam desabilitados de propósito: são confirmados
dias depois, quando o comprador já saiu do site, e dependeriam do webhook.

## Limitações

Não envia e-mail, então não existe confirmação de compra na caixa de entrada. O
QR Code é gerado por um serviço externo (`api.qrserver.com`), o que exige
internet na hora de exibir o ingresso. E não há testes automatizados.

## Licença

Livre para fins educacionais.
