# Reporting and Export Guardrails

## Objetivo

Definir regras para relatorios municipais, dashboards e exports no piloto com utilizadores reais controlados.

## Autorizacao

- `reports.view` permite acesso base a relatorios internos autorizados.
- `reports.view_sensitive` e necessario para relatorios sensiveis.
- `reports.view_financial` e necessario para dados financeiros.
- `reports.export` e necessario para qualquer export.
- `reports.export_sensitive` e necessario para export sensivel.
- `reports.export_financial` e necessario para export financeiro.
- `reports.export_nominal` e necessario para ambito nominal/pessoal.
- `reports.audit` permite consultar logs de acesso/export.

## Auditoria

Cada export deve registar:

- utilizador;
- relatorio;
- ambito;
- formato pedido;
- formato efetivo;
- filtros;
- data/hora;
- download posterior.

Downloads devem criar log proprio. Ficheiros ficam em storage privado e expiram.

## Minimizacao

Exports municipais por defeito devem ser agregados. Campos proibidos em exports gerais:

- passwords;
- tokens;
- APP_KEY;
- documentos privados;
- paths internos;
- NIF completo;
- dados de saude/incapacidade sem permissao propria;
- rendimentos individuais sem base e permissao.

## CSV/Excel

Campos iniciados por `=`, `+`, `-` ou `@` devem ser neutralizados para evitar formula injection. XLSX/PDF podem usar fallback seguro documentado quando a stack local nao suportar geracao nativa.

## Performance

- relatorios com muitos registos devem usar filtros obrigatorios ou chunking;
- dashboards devem usar agregacoes SQL;
- listagens devem ser paginadas;
- evitar joins que revelem dados desnecessarios;
- rever N+1 antes de piloto real.

## Evidencia

Guardar evidencia sanitizada em `storage/qa`, sem dados pessoais reais e sem ficheiros exportados com dados reais.
