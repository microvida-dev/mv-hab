# QA-43 Alcanena Legal Parameterization Report

## Sumario executivo

QA-43 validou a parametrizacao municipal de Alcanena ja existente no seeder demo, cobrindo elegibilidade, impedimentos, documentacao, scoring, desempates, prazos de reclamacao/audiencia, renda e contrato demo.

## Ficheiros analisados

- `database/seeders/DemoAlcanenaAffordableRentSeeder.php`
- `tests/Feature/DemoAlcanenaAffordableRentSeederTest.php`
- `docs/11-operacoes/out-of-scope-integrations.md`
- `docs/11-operacoes/pilot-scope-alcanena.md`
- `docs/11-operacoes/alcanena-legal-parameterization.md`
- `docs/11-operacoes/alcanena-regulatory-mapping.md`

## Alteracoes implementadas

- Documentacao operacional de parametrizacao legal Alcanena.
- Mapeamento regulamentar entre artigos e entidades tecnicas.
- Testes QA-43 para congelar conformidade esperada.

## Testes criados

- `tests/Feature/QA43AlcanenaLegalParameterizationTest.php`
- `tests/Feature/Regulatory/AlcanenaRequiredDocumentsTest.php`
- `tests/Feature/Regulatory/AlcanenaEligibilityRulesTest.php`
- `tests/Feature/Regulatory/AlcanenaScoringTieBreakTest.php`
- `tests/Feature/Regulatory/AlcanenaListsHearingComplaintsTest.php`
- `tests/Unit/Regulatory/AlcanenaRegulatoryMappingTest.php`

## Validacoes

- Artigos 8 e 9 mapeados para criterios estruturados e manuais.
- Artigo 12 coberto por 11 tipos documentais, incluindo declaracao medica de gravidez.
- Prazos de reclamacao e audiencia existem no concurso demo.
- Scoring tem 4 criterios e 4 desempates determinísticos.
- Taxa de esforco maxima e 35%.
- Sorteio fica apenas para empate remanescente quando aplicavel.

## Riscos residuais

- Datas, fogos, rendas e textos juridicos continuam ficticios ate validacao municipal do edital.
- Integracoes externas permanecem fora de ambito por decisao municipal.

## Resultado

Bloco QA-43 validado sem alterar regras regulamentares.
