# Lacunas de scope municipal nas rotas backoffice

## Resultado estático

| Classificação | Rotas | Significado |
| --- | ---: | --- |
| Confirmado | 16 | O controller, Policy, Form Request ou Service referenciado contém evidência explícita de scope municipal |
| Candidato | 75 | Rota sem Model route-bound ou de contexto que exige revisão manual |
| Não confirmado | 615 | Existe registo operacional, mas a análise estática não encontrou evidência suficiente |
| **Total** | **706** |  |

`Não confirmado` não prova acesso cross-tenant. Pode existir scope indireto em
query objects, relações ou Services chamados em profundidade. A classificação
obriga a comprovação na sprint do domínio.

## Contexto da rota

| Contexto | Rotas | Tratamento |
| --- | ---: | --- |
| Municipal | 673 | Confirmar Município operacional e ownership do registo |
| Mixed-context | 33 | Resolver o domínio em runtime antes do entitlement e da Policy |
| Plataforma no backlog fixo | 0 | Rever separadamente as rotas de plataforma já migradas |
| Feature por decidir | 32 | Não aplicar middleware global nem criar FeatureKey |

Estas classificações são candidatas de inventário. Não substituem a verificação
do `MunicipalRecordScopeService`, da Policy e da query real em cada Sprint 47.

## Não confirmadas por bounded context

| Contexto | Rotas |
| --- | ---: |
| Finanças | 67 |
| Contratos | 52 |
| Classificação | 49 |
| Documentos | 47 |
| Manutenção | 46 |
| Notificações | 40 |
| Comunicações | 36 |
| Pagamentos | 29 |
| Administração e segurança | 28 |
| Atribuições | 27 |
| Candidaturas | 25 |
| Vistorias | 25 |
| Listas | 23 |
| Processos administrativos | 19 |
| Visitas | 18 |
| Elegibilidade | 16 |
| Utilizadores e equipas | 15 |
| Reclamações | 14 |
| Audiência | 12 |
| Relatórios | 11 |
| Decisões | 8 |
| Configuração | 8 |

## Regra de migração

Para cada rota municipal:

1. resolver o Município operacional;
2. aplicar permission efetiva através de role ativa;
3. autorizar a ability específica;
4. validar ownership do registo com Policy e/ou
   `MunicipalRecordScopeService`;
5. falhar fechado sem Município;
6. testar Município A contra registo do Município B;
7. confirmar que a recusa não altera estado.

Rotas de plataforma devem usar scope estrutural explícito, nunca inferido
apenas por `municipality_id = null`. Rotas mixed-context devem resolver o
domínio antes de aplicar feature ou scope.

O detalhe por rota está no inventário JSON/CSV.
