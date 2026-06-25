# QA-39 — Pilot Scope & External Dossier Sanitization

## Sumario executivo

Foi criada documentacao dedicada ao ambito do piloto municipal de Alcanena e checklist de sanitizacao do dossier externo.

## Decisao municipal

Ficam fora do piloto por decisao municipal:

- CMD;
- Autenticacao.gov;
- pagamentos via plataforma;
- MB WAY;
- Multibanco;
- cartao;
- gateway de pagamentos;
- reconciliacao bancaria automatica;
- importacao SEPA automatica;
- assinatura digital qualificada.

Todos devem ser apresentados como `Out of scope by municipal decision`.

## Alteracoes

- Atualizacao de `docs/11-operacoes/out-of-scope-integrations.md`.
- Atualizacao de `docs/11-operacoes/municipal-admin-guide.md`.
- Criacao de `docs/11-operacoes/pilot-scope-alcanena.md`.
- Criacao de `docs/11-operacoes/external-dossier-sanitization-checklist.md`.
- Testes QA39 e operations para escopo municipal aceite.

## Riscos residuais

- Qualquer apresentacao externa deve usar apenas dados ficticios e screenshots sanitizados.
- Restore/rollback real continuam requisito antes de producao plena.

## Evidencia

- `storage/qa/qa-39-tests.txt`: PASS, 2 testes, 35 assercoes.
- `storage/qa/phase-1-operations-tests.txt`: PASS.
- `docs/11-operacoes/pilot-scope-alcanena.md`: criado.
- `docs/11-operacoes/external-dossier-sanitization-checklist.md`: criado.

## Decisao

PASS.
