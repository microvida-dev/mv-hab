# Sprint 52D — Retirada das visitas legacy da Área do Candidato

## Objetivo

Remover definitivamente da Área do Candidato o fluxo antigo baseado em
`HousingVisit`, sem eliminar dados históricos e sem afetar as marcações públicas
criadas na Sprint 52C.

## Alterações

- removidas as sete rotas `candidate.visits.*`;
- removidos o controller, Form Requests e views exclusivos desse fluxo;
- removida a flag `legacy_visits`, impedindo reativação por configuração;
- retiradas do perfil `candidate` as permissions `visits.view`,
  `visits.create` e `visits.update`;
- adicionada migration reversível para revogar essas associações em bases já
  instaladas;
- Policies e cancelamento legacy passam a falhar fechado para candidatos;
- notificações relativas a registos históricos deixam de gerar links para
  rotas inexistentes;
- removida a consulta de calendário legacy do candidato.

## Preservação histórica

Mantêm-se:

- tabelas `housing_visits` e `housing_visit_status_histories`;
- modelo, factory, relações e histórico de auditoria;
- consulta e encerramento administrativo de registos legacy pelo backoffice;
- disponibilidades e slots municipais partilhados com as marcações públicas;
- `PublicVisitBooking`, respetivas rotas públicas e backoffice;
- seeders de demonstração e relatórios históricos.

Não é executada eliminação ou anonimização adicional nesta sprint. Qualquer
retenção dos registos históricos deve seguir a política RGPD municipal
aprovada.

## Contrato de rotas

```text
Antes: 1180
Removidas: 7 candidate.visits.*
Depois: 1173
```

A remoção reduz as rotas com role fixa de candidato de 220 para 213. As rotas
permission-first permanecem em 911 e o backoffice continua com zero rotas
protegidas por roles fixas.

## Deployment

1. executar a migration `2026_07_30_000046`;
2. limpar caches de configuração e rotas;
3. confirmar ausência de `candidate.visits.*`;
4. confirmar preservação de `public.visit-bookings.*` e
   `backoffice.housing-visits.*`;
5. validar que o perfil `candidate` não possui as três permissions retiradas.

## Rollback

O rollback da migration restaura apenas as associações de permissions. O código
removido e as rotas não são reativados automaticamente, evitando reabertura
acidental do fluxo legacy durante rollback parcial.
