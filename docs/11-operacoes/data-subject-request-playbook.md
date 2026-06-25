# Data Subject Request Playbook

## Objetivo

Orientar tratamento de pedidos RGPD no piloto municipal controlado.

## Tipos de pedido

- acesso;
- retificacao;
- apagamento quando aplicavel;
- limitacao;
- oposicao;
- portabilidade quando aplicavel.

## Fluxo

1. Registar pedido.
2. Verificar identidade.
3. Classificar tipo de pedido.
4. Atribuir responsavel.
5. Validar base legal e limitacoes.
6. Gerar exportacao ou acao necessaria.
7. Auditar acesso/exportacao.
8. Responder ao titular por canal aprovado.
9. Fechar pedido com resumo minimizado.

## Prazos

Usar `due_at` do pedido. Pedidos vencidos devem gerar Work Task ou alerta operacional.

## Exportacao

- exige autorizacao;
- usa storage privado;
- cria auditoria;
- expira;
- nao expor path interno;
- nao enviar por canal externo sem decisao municipal.

## Apagamento/anonimizacao

Exige validacao municipal/juridica e DPO quando aplicavel. Se houver obrigacao legal de conservacao, registar indeferimento ou limitacao.
