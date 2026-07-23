# Lacunas de permissions nas rotas backoffice

## Resumo

As 706 rotas deste inventário continuam protegidas por middleware fixo
`role:` e, por definição, ainda não possuem middleware permission-first
adequado.

| Situação | Rotas |
| --- | ---: |
| Existe permission semântica candidata no catálogo | 572 |
| Não existe equivalente semântico seguro no catálogo atual | 134 |
| Total a migrar | 706 |

Foram também detetados 160 Form Requests com autorização incondicional. Esta
evidência deve ser corrigida apenas na sprint do respetivo domínio, alinhando o
request com a ability específica sem substituir a Policy.

O inventário não converte ações sensíveis para `update` por conveniência.
Quando a ação exige semântica própria e a permission não existe, o campo
`permission_recommendation` fica vazio e a rota é marcada para decisão.

## Lacunas semânticas por bounded context

| Contexto | Rotas |
| --- | ---: |
| Administração e segurança | 35 |
| Classificação | 11 |
| Documentos | 10 |
| Contratos | 9 |
| Vistorias | 8 |
| Atribuições | 7 |
| Pagamentos | 7 |
| Comunicações | 7 |
| Finanças | 6 |
| Visitas | 6 |
| Manutenção | 5 |
| Listas | 4 |
| Notificações | 4 |
| Processos administrativos | 3 |
| Elegibilidade | 3 |
| Utilizadores e equipas | 2 |
| Decisões | 2 |
| Configuração | 2 |
| RGPD | 1 |
| Reclamações | 1 |
| Audiência | 1 |

## Regras para resolução

- reutilizar nomes reais quando já existe equivalente exato;
- criar nova permission estrutural apenas quando não existe equivalente e a
  separação é necessária ao menor privilégio;
- nunca usar `documents.update` para aprovar ou rejeitar;
- nunca usar `contracts.update` para assinar, ativar ou rescindir;
- nunca usar `finance.update` para aprovar, rejeitar, importar ou reverter;
- separar geração, aprovação, publicação e lock de listas;
- manter auditor read-only;
- nenhuma permission direta por utilizador.

O detalhe integral, incluindo middleware atual e recomendação por rota, está em
`docs/access/backoffice-route-inventory.json` e `.csv`.
