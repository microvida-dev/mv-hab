# Readiness Report Pre-Sprint 32 — Alcanena

## Resumo executivo

A MV HAB apresenta uma base forte para demonstracao ao Municipio de Alcanena. O maior valor demonstravel esta no ciclo administrativo completo: candidatura, documentos, elegibilidade, classificacao, listas, workflow, auditoria, RGPD e operacao municipal. Antes da Sprint 32, a prioridade deve ser polir a experiencia publica, preparar dados ficticios coerentes e alinhar a narrativa com o regulamento local.

## Estado recomendado

**Pronta para demonstracao controlada, com ressalvas explicitas no roteiro.**

Nao classificar ainda como pronta para producao municipal sem:

- validacao juridica municipal;
- validacao dos templates/documentos;
- validacao de seguranca em ambiente alvo;
- dados demo/municipais finais;
- plano de operacao, backups e suporte;
- configuracao de integracoes futuras quando aplicavel.

## Pontos fortes para apresentar

- Backoffice processual robusto.
- Concurso, candidatura e documentacao estruturados.
- Regras de elegibilidade e impedimentos documentadas/configuraveis.
- Classificacao e ranking.
- Listas, reclamacoes e audiencia.
- Auditoria e historico.
- RGPD e controlo de acessos.
- Modulos financeiros, contratos, manutencao, vistorias e inquilino como ciclo alargado.
- Document Intelligence local como assistente tecnico, sem APIs pagas.

## Pontos validados/afinados na Sprint 32

- Seeder Alcanena com programa e concurso publicados para demo.
- Quatro fogos ficticios publicos: T1 Alcanena Centro, T2 Alcanena, T3 Minde e T2 Monsanto.
- Filtros visiveis por freguesia, tipologia, renda minima/maxima e estado.
- Ficha publica do imovel com area bruta, CTA de simulacao, area reservada e brochura.
- Brochura simples HTML imprimivel por fogo.
- Testes focados para filtros, brochura e seeder demo.

## Pontos a validar antes da apresentacao presencial

- Mapa publico com dados ficticios e sem moradas sensiveis.
- Candidaturas ficticias em estados representativos, se a apresentacao exigir dados pre-carregados.
- Utilizadores demo e credenciais seguras de teste.
- Roteiro de apresentacao ensaiado.

## Riscos tecnicos

| Risco | Impacto | Mitigacao |
| --- | --- | --- |
| Candidaturas/listas demo nao pre-carregadas | Backoffice pode exigir preparacao manual antes da apresentacao | Usar roteiro existente ou criar dados processuais em sprint propria de demo, se aprovado |
| Mapa dependente de dados/coordenadas | Demo pode falhar visualmente | Usar coordenadas aproximadas e fallback textual |
| Funcionalidades externas confundidas com core | Expectativa incorreta | Roadmap honesto e disclaimer no roteiro |

## Riscos de seguranca e RGPD

| Risco | Impacto | Mitigacao |
| --- | --- | --- |
| Dados pessoais reais em demo | Risco RGPD alto | Usar apenas dados ficticios |
| Moradas completas sensiveis publicadas | Exposicao indevida | Usar localizacao aproximada e flag de visibilidade |
| Documentos privados usados em contexto publico | Exposicao indevida | Separar documentos publicos de documentos de candidatura |
| Credenciais demo inseguras | Acesso indevido | Criar apenas credenciais de ambiente local/controlado |
| IA interpretada como decisor automatico | Risco juridico/processual | Reforcar que IA e apoio tecnico e nao decisor |

## Checklist antes da apresentacao

- [ ] Executar seeder demo de Alcanena em ambiente controlado.
- [ ] Validar portal publico.
- [ ] Validar area reservada de candidato.
- [ ] Validar backoffice tecnico.
- [ ] Validar documentos privados.
- [ ] Validar elegibilidade/classificacao/listas.
- [ ] Validar auditoria/RGPD.
- [ ] Validar utilizadores demo.
- [ ] Ensaiar roteiro completo.
- [ ] Preparar respostas a perguntas sobre integracoes externas.

## Recomendacao objetiva

Avancar para demonstracao interna/local apos executar validacoes finais. Para apresentacao ao Municipio, recomenda-se ensaio completo do roteiro com utilizadores demo e decisao previa sobre criar, ou nao, candidaturas/listas ficticias pre-carregadas.
