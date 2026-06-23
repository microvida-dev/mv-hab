# Sprint 32 Preflight Report — Alcanena

## Objetivo

Registar a analise preparatoria antes da execucao da Sprint 32, sem criacao de codigo aplicacional.

## Fontes analisadas

- `docs/backlog/sprint-32-preparacao-apresentacao-municipio-alcanena.md`
- `docs/backlog/sprint-20-portal-publico-oferta-habitacional.md`
- `docs/qa/sprint-20-quality-report.md`
- `docs/qa/sprint-31-quality-report.md`
- `docs/product/functional-requirements.md`
- `/Users/brunocorreia/Documents/CRM HAB/Requisitos plataforma.pdf`
- `/Users/brunocorreia/Documents/CRM HAB/DOCUMENTAÇÃO LEGAL/ Regime de Arrendamento Acessível ALCANENA.pdf`
- `/Users/brunocorreia/Documents/CRM HAB/DOCUMENTAÇÃO LEGAL/Manual_Concursos_Habitação_Acessível_compressed.pdf`

## Comandos de inspecao executados

| Comando | Resultado |
| --- | --- |
| `sed -n '1,260p' docs/backlog/sprint-32-preparacao-apresentacao-municipio-alcanena.md` | Lido com sucesso |
| `pdfinfo "/Users/brunocorreia/Documents/CRM HAB/Requisitos plataforma.pdf"` | PDF identificado com 1 pagina |
| `pdftotext -layout "/Users/brunocorreia/Documents/CRM HAB/Requisitos plataforma.pdf" -` | Texto extraido com sucesso |
| `pdfinfo "/Users/brunocorreia/Documents/CRM HAB/DOCUMENTAÇÃO LEGAL/ Regime de Arrendamento Acessível ALCANENA.pdf"` | PDF identificado com 23 paginas |
| `pdftotext -layout "/Users/brunocorreia/Documents/CRM HAB/DOCUMENTAÇÃO LEGAL/ Regime de Arrendamento Acessível ALCANENA.pdf" -` | Texto extraido com sucesso |
| `pdfinfo "/Users/brunocorreia/Documents/CRM HAB/DOCUMENTAÇÃO LEGAL/Manual_Concursos_Habitação_Acessível_compressed.pdf"` | PDF identificado com 13 paginas |
| `pdftotext -layout "/Users/brunocorreia/Documents/CRM HAB/DOCUMENTAÇÃO LEGAL/Manual_Concursos_Habitação_Acessível_compressed.pdf" -` | Texto extraido com sucesso |
| `php artisan route:list \| rg "oferta-habitacional|visitas|ticket|simulador|document-ai|inconsist"` | Rotas relevantes encontradas |
| `rg -n "class DemoAlcanenaAffordableRentSeeder|ALC-DEMO|Programa Municipal de Arrendamento" database docs app resources tests` | Seeder/demo Alcanena encontrado |
| `rg -n "public.housing|HousingUnitPublicDocument|brochure|freguesia|rent_min|rent_max|mapa" app routes resources tests docs` | Portal publico, docs publicos e filtros encontrados |

## Conclusoes da inspecao

- A Sprint 32 esta orientada para preparacao de apresentacao, nao para criacao de grandes modulos.
- A plataforma ja tem base extensa para demonstrar ciclo municipal completo.
- O portal publico existe, incluindo concursos, fogos, detalhe e mapa.
- A pesquisa publica ja suporta filtros tecnicos relevantes, mas a interface deve ser revista para exposicao clara de todos os filtros de demo.
- Existem documentos publicos associados a fogos, incluindo suporte conceptual para brochuras.
- Existe seeder demo de Alcanena, mas deve ser revisto para completar dados publicos e cenarios necessarios para a apresentacao.
- A documentacao legal de Alcanena valida o foco em adesao, candidatura, documentos, elegibilidade, impedimentos, classificacao, audiencia e listas.

## Ficheiros criados nesta preparacao

- `docs/presentation/alcanena-pre-sprint-32-task-plan.md`
- `docs/presentation/alcanena-requirements-match.md`
- `docs/presentation/alcanena-demo-script.md`
- `docs/presentation/alcanena-roadmap.md`
- `docs/presentation/alcanena-demo-data.md`
- `docs/presentation/alcanena-readiness-report.md`
- `docs/qa/sprint-32-preflight-report.md`

## Ficheiros aplicacionais alterados

Nenhum.

## Testes

Nao foram executados testes automatizados nesta etapa porque nao houve alteracao de codigo aplicacional.

## Recomendacao

Avancar para a Sprint 32 com foco em:

1. dados demo Alcanena;
2. acabamento do portal publico;
3. brochuras/fichas publicas;
4. roteiro de demonstracao;
5. quality report final da demo;
6. validacao honesta dos requisitos perante o Municipio.
