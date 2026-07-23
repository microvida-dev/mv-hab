# ADR — Entitlement de audiências, listas e sorteios

## Estado

Aceite para a Sprint 47D.

## Contexto

A Sprint 47D migra 78 rotas backoffice de audiência prévia, reclamações,
listas, atribuições residuais e sorteios para autorização permission-first.
O catálogo comercial atual expõe apenas:

- `applications.intake`;
- `applications.review`;
- `applications.export`.

Não existe uma `FeatureKey` autónoma para audiências, listas ou sorteios e a
Sprint 47D não pode criar uma. O entitlement não substitui permission, Policy,
scope municipal, MFA, logging ou validação da transição de estado.

## Critérios

Uma operação pode reutilizar `applications.review` quando:

1. trabalha sobre candidaturas ou sobre um procedimento que agrega
   candidaturas;
2. integra a análise, decisão, ordenação ou conclusão dessas candidaturas;
3. não é um catálogo estrutural nem uma configuração técnica global;
4. deve ficar indisponível quando o Município não contratou a análise de
   candidaturas.

Com a feature desligada, o middleware falha antes do controller com feedback
seguro. Os dados existentes não são alterados nem eliminados; ficam
inacessíveis pelo backoffice operacional até reativação autorizada. A feature
continua dependente de `applications.intake`.

## Decisão por bounded context

| Grupo | Bounded context | Relação com candidatura | Decisão |
| --- | --- | --- | --- |
| Audiência prévia | Revisão contraditória de uma candidatura ou lista | A pronúncia pode alterar a decisão administrativa e a posição da candidatura | Reutilizar `applications.review` |
| Reclamações | Contestação apresentada no processo candidatural | A análise, pedido de informação e fecho integram a revisão do processo | Reutilizar `applications.review` |
| Listas provisórias | Resultado intermédio da análise e classificação | Gerar, rever, aprovar, publicar e gerir o período de reclamações concluem uma fase da candidatura | Reutilizar `applications.review` |
| Listas definitivas | Resultado final do procedimento classificativo | A lista consolida decisões e candidaturas após contraditório | Reutilizar `applications.review` |
| Atribuições residuais e desistências | Continuação da ordenação aprovada | O processamento determina qual candidatura prossegue para atribuição | Reutilizar `applications.review` |
| Sorteios | Mecanismo de desempate/ordenação de candidaturas | Participantes e resultados derivam do procedimento e da lista definitiva | Reutilizar `applications.review` |
| Convocatórias e presenças | Execução operacional do sorteio | Existem apenas para candidatos participantes no procedimento | Reutilizar `applications.review` |
| Resultados, vencedor e relatório pós-sorteio | Validação e evidência da ordenação | Concluem o desempate e suportam a atribuição | Reutilizar `applications.review` |
| Ordenação pós-sorteio | Atualização do ranking após resultado validado | Cria snapshot classificativo e permanece mixed-context com scoring | Manter `applications.review`; a rota foi migrada na 47C e não integra o lote funcional da 47D |

## Impacto comercial

`applications.review` representa a capacidade municipal de instruir e concluir
candidaturas. Audiências, reclamações, listas e sorteios não têm utilidade
operacional independente dessa capacidade. A reutilização evita criar uma
feature comercial artificial e mantém um comportamento coerente quando o
Município tem apenas recolha de candidaturas.

Não se aplica entitlement a fluxos candidate próprios. O candidato continua a
usar as rotas da área reservada e as respetivas Policies de ownership. Também
não são abrangidos catálogos, templates ou configurações técnicas globais.

## Segurança

Cada rota backoffice da 47D deve continuar a exigir cumulativamente:

```text
auth
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
&& municipality.feature:applications.review
&& permission:<ação exata>
&& Policy
&& scope municipal fail-closed
&& transição válida
```

O entitlement não concede acesso por si só. `municipality_id = null` não é
scope de plataforma e o operador global depende de assignment estrutural.
`candidate` permanece fora do backoffice e `auditor` conserva apenas leitura.

## Alternativas rejeitadas

### Sem entitlement

Rejeitada porque permitiria operar fases decisórias e de conclusão num
Município com recolha ativa, mas sem análise de candidaturas contratada.

### Nova FeatureKey

Não é necessária para segurança nem para o modelo comercial atual. Criá-la
fragmentaria um único processo administrativo e bloquearia a Sprint 47D sem
benefício demonstrado.

### Entitlement por catálogo ou template

Não se aplica ao manifesto da 47D. Nenhuma das 78 rotas gere um catálogo
estrutural autónomo.

## Consequências

- as 78 rotas reconciliadas usam `applications.review`;
- as cinco rotas `complaint-decisions` e a atualização de ranking
  pós-sorteio mantêm a decisão já aplicada na 47C;
- não é criada migration, enum ou `FeatureKey`;
- fixtures e testes positivos devem ativar explicitamente
  `applications.intake` e `applications.review` no Município do recurso;
- testes negativos devem demonstrar independência entre entitlement,
  permission e Policy.
