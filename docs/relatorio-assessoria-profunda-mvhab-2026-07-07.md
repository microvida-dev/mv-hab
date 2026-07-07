# Relatório de Assessoria Profunda - MV HAB

Data: 2026-07-07  
Objeto: análise da plataforma anexada `MV-HAB 7.zip` e fontes do projeto  
Contexto: CRM municipal para Habitação / Arrendamento Acessível, com foco no piloto de Alcanena

## 1. Conclusão Executiva

A plataforma MV HAB encontra-se num estado técnico muito avançado. A versão analisada deixou claramente de ser um protótipo: tem portal público, área do candidato, backoffice municipal, área do inquilino, elegibilidade, scoring, listas, audiência/reclamações, atribuição, contratos, rendas, manutenção, vistorias, comunicações, RGPD, auditoria, Work Tasks, dashboards, pesquisa, agenda, analytics e IA documental assistiva.

O principal desafio já não é criar o ciclo funcional base. O desafio real é transformar uma plataforma muito completa numa entrega municipal segura, compreensível, demonstrável e juridicamente controlada.

Em termos práticos:

| Dimensão | Estado atual | Leitura |
| --- | --- | --- |
| Cobertura funcional | Muito alta | O ciclo do concurso e pós-atribuição está amplamente modelado. |
| Maturidade técnica | Alta | Evidência histórica de PHPStan 0 erros, PHPUnit 628 testes / 3722 asserções e build Vite OK. |
| Segurança/RGPD | Forte no desenho | Há policies, auditoria, MFA, logs sensíveis, storage privado, DPO/retention/anonymization. |
| Packaging/release | Inconsistente | Existem scanners e QA, mas o ZIP analisado ainda inclui `.env` local com `APP_KEY` e `APP_DEBUG=true`. |
| Alcanena/regulamento | Parcialmente parametrizado | Seeder e docs mapeiam artigos centrais, mas edital, fogos, rendas, datas e textos finais continuam demo. |
| UX municipal | Em forte evolução | Workspaces, cards, dashboards e case workspaces reduzem a complexidade da antiga side nav. |
| Integrações externas | Fora de âmbito | CMD, Autenticação.gov, pagamentos digitais e assinatura qualificada estão documentados como fora de âmbito municipal. |
| Prontidão para piloto real | Condicionada | Adequada para demonstração/staging controlado; antes de dados reais precisa de pacote limpo, smoke E2E, DPO/jurídico e validação operacional. |

Recomendação central: avançar em sprints de consolidação e prontidão municipal, não em expansão funcional pesada. A plataforma já tem mais módulos do que o município precisa para o primeiro contacto. O valor agora está em reduzir risco, simplificar fluxos, provar evidência e preparar uma narrativa operacional clara.

## 2. Fontes Analisadas

Foram analisadas as seguintes fontes:

| Fonte | Utilização |
| --- | --- |
| `/workspace/.cache/01-MV-HAB-7.zip` | Fonte técnica principal da plataforma atual. |
| `Regime de Arrendamento Acessível ALCANENA.pdf` | Base regulamentar municipal. |
| `Requisitos plataforma.pdf` | Mapa funcional esperado: candidato, Câmara/backoffice, inquilino/senhorio. |
| `Manual_Concursos_Habitação_Acessível_compressed.pdf` | Procedimento público e documentos exigidos ao candidato. |
| `deep-research-report-v2.md` | Diagnóstico profundo anterior da staging. |
| `MV-HAB 6.zip` e fontes históricas em `.cache/project_sources` | Contexto evolutivo e comparação indireta. |
| Docs internas do repositório | Produto, arquitetura, sprints, QA, operações, RGPD, pilotos. |
| Evidências `storage/qa/*` | Resultados de testes, PHPStan, build e gates UX/QA. |
| Relatório de handoff Sprint 40/41 | Estado recente: icon library, portal público, galeria e navegação. |

Limitação: neste runtime não existe `php` no PATH, por isso não foi possível reexecutar `artisan`, PHPUnit ou PHPStan. A análise técnica baseou-se em leitura estrutural, evidência QA presente no pacote e inspeção direta dos ficheiros. Antes de fechar qualquer sprint deve ser feita nova validação no Mac/servidor do projeto.

## 3. Inventário Técnico da Versão 7

| Métrica | Valor observado |
| --- | ---: |
| Ficheiros em `app/` | 2020 |
| Modelos Eloquent | 255 |
| Migrations | 55 |
| Ficheiros de teste | 360 |
| Vistas Blade | 760 |
| Vistas backoffice | 364 |
| Vistas candidato | 128 |
| Chamadas `Route::` em `web.php` | 1034 |
| Chamadas `Route::` em `auth.php` | 17 |

Stack:

| Área | Estado |
| --- | --- |
| PHP | `^8.3` no Composer; projeto orientado a PHP 8.4 no histórico de QA. |
| Laravel | `^13.8`. |
| Frontend | Blade, Alpine.js, Tailwind, Vite 8. |
| Qualidade | PHPUnit 12, PHPStan 2, Larastan 3, Pint. |
| UI | Design system MV HAB, componentes Blade, workspaces, cards e analytics. |
| Filas | Jobs e comandos existem; `.env` local ainda usa `QUEUE_CONNECTION=sync`. |

Evidência QA mais recente dentro do pacote:

| Evidência | Resultado |
| --- | --- |
| `storage/qa/ux-09-phpunit.txt` | passed, 628 testes, 3722 asserções. |
| `storage/qa/ux-09-phpstan.txt` | passed, 0 erros. |
| `storage/qa/ux-09-build.txt` | Vite build OK. |
| `storage/qa/ux-09-diff-check.txt` | Sem output, indicando sem whitespace/check diff issues. |

## 4. Estado Funcional Atual

### 4.1 Portal Público

Funcionalidades existentes:

| Necessidade | Estado |
| --- | --- |
| Página pública principal | Implementado. |
| Programas publicados | Implementado. |
| Concursos abertos/futuros | Implementado. |
| Oferta habitacional | Implementado. |
| Filtros por tipologia, freguesia, renda e outros | Implementado/reforçado. |
| Mapa público | Implementado com payload seguro e precisão pública. |
| Ficha pública de imóvel | Implementado e recentemente melhorado. |
| Galeria de imagens | Implementado com carousel/lightbox no handoff Sprint 40/41. |
| Documentos públicos | Implementado com download controlado. |
| Brochura | Implementado. |
| Sitemap/robots | Implementado. |
| SEO/Schema.org | Implementado/reforçado na QA-34. |

Riscos:

- A ficha pública cresceu e deve ser componentizada.
- O mapa precisa de validação visual real em desktop/mobile.
- A experiência pública deve ser testada com dados municipais reais ou realistas, não apenas demo.

### 4.2 Experiência do Candidato

Funcionalidades existentes:

| Necessidade | Estado |
| --- | --- |
| Registo antes da candidatura | Implementado. |
| Agregado habitacional | Implementado. |
| Rendimentos | Implementado. |
| Situação habitacional atual | Implementado. |
| Simulador | Implementado. |
| Pré-preenchimento a partir do simulador | Implementado. |
| Candidaturas | Implementado. |
| Upload documental | Implementado. |
| Checklist documental | Implementado. |
| Revisão/submissão formal | Implementado. |
| Comprovativo e impressão | Implementado. |
| Desistência controlada | Implementado. |
| Visitas | Implementado. |
| Tickets de apoio | Implementado. |
| Audiências e reclamações | Implementado. |
| Notificações oficiais | Implementado. |
| Timeline/processos | Implementado. |
| Reutilização de dados | Implementado. |

Riscos:

- A UX do candidato é longa e juridicamente sensível; precisa de ensaio real com utilizadores não técnicos.
- O manual de Alcanena exige documentos muito específicos; a checklist demo cobre a base, mas deve ser congelada contra edital real.
- Consentimento/tratamento de dados tem alternativa presencial no manual; a plataforma deve representar esse caminho sem forçar submissão digital.

### 4.3 Backoffice Municipal

Funcionalidades existentes:

| Necessidade | Estado |
| --- | --- |
| Gestão de programas/concursos | Implementado. |
| Editais, prazos, minutas | Implementado/parcial. |
| Gestão de fogos e oferta pública | Implementado. |
| Análise de candidaturas | Implementado. |
| Revisão documental | Implementado. |
| Pedidos de aperfeiçoamento | Implementado. |
| Elegibilidade | Implementado. |
| Scoring/ranking | Implementado. |
| Listas provisórias/definitivas | Implementado. |
| Audiência e reclamações | Implementado. |
| Sorteios e desempates | Implementado. |
| Atribuição e ofertas | Implementado. |
| Contratos, caução e validação | Implementado com gestão manual. |
| Rendas e finanças | Implementado com gestão administrativa/manual. |
| Relatórios e KPIs | Implementado/reforçado. |
| Work Tasks e SLA | Implementado. |
| Segurança, RBAC, equipas | Implementado/reforçado. |
| RGPD, retenção, anonimização | Implementado/reforçado. |
| Auditoria | Implementado. |

Riscos:

- A existência simultânea de zonas `admin/*` e `backoffice/*` deve ser governada para não confundir utilizadores.
- A matriz funcional é muito vasta; o município deve receber perfis simplificados por equipa.
- Perfis em `config/mvhab.php` ainda têm labels em inglês (`Administrator`, `Municipal technician`, `Jury`, etc.), que devem ser verificados em ecrãs visíveis.

### 4.4 Área do Inquilino

Funcionalidades existentes:

| Necessidade | Estado |
| --- | --- |
| Dashboard inquilino | Implementado. |
| Contratos | Implementado. |
| Faturas/rendas | Implementado. |
| Pagamentos | Implementado administrativamente, sem gateway. |
| Comunicações | Implementado. |
| Manutenção | Implementado. |
| Vistorias | Implementado. |
| Documentação anual/rendimentos | Implementado/parcial. |

Riscos:

- Pagamentos digitais estão fora de âmbito; a comunicação deve ser muito clara para evitar expectativa errada.
- Assinatura digital qualificada está fora de âmbito; contratos exigem circuito manual/administrativo.

### 4.5 IA Documental

Estado:

- OCR/classificação/extração/validação assistiva estão implementados.
- A IA calcula scores, deteta flags, sugere ações e cria Work Tasks.
- A IA não decide elegibilidade, exclusão, deferimento ou classificação final.
- Existem testes dedicados de segurança/RGPD e pipelines.

Leitura:

Esta abordagem é correta para contexto municipal. Deve ser comunicada como assistência técnica à análise documental, nunca como decisão automática.

## 5. Procedimento Real de Alcanena vs Plataforma

O regulamento de Alcanena define condições e trâmites de acesso ao arrendamento municipal acessível, com inscrição/classificação, compatibilidade de rendimentos, taxa de esforço, adequação do agregado e documentação.

O manual do concurso exige, entre outros:

- email válido para comunicações;
- escolha da habitação;
- autorização de tratamento de dados ou alternativa presencial;
- identificação civil;
- NIF e Segurança Social, se não constarem no documento de identificação;
- certidão de domicílio fiscal;
- certidão predial negativa ou certidão predial;
- nota de liquidação IRS ou documentação alternativa de rendimentos;
- certidão de não dívida AT;
- outros passos/documentos complementares.

Mapeamento:

| Procedimento real | Cobertura atual | Gap real |
| --- | --- | --- |
| Publicitação do edital e oferta | Coberto por programas/concursos/fogos públicos | Inserir edital real e validar textos. |
| Escolha da habitação | Coberto por ficha pública e preferências | Confirmar se o concurso real permite uma ou várias preferências. |
| Registo e email oficial | Coberto por autenticação local | Validar texto legal sobre comunicações oficiais por email. |
| Consentimento ou alternativa presencial | Parcial | Representar claramente opção presencial/declarativa. |
| Upload documental | Coberto | Checklist deve ser congelada pelo edital real. |
| Aperfeiçoamento documental | Coberto | Validar prazos e modelos de notificação. |
| Elegibilidade | Coberto com regras e manual review | Rever contra regulamento final e edital. |
| Classificação | Coberto | Confirmar pesos e desempates reais. |
| Lista provisória/audiência/reclamação | Coberto | Validar formatos públicos, anonimização e prazos. |
| Sorteio residual | Coberto | Ensaiar ata, hash/auditoria e publicação. |
| Lista definitiva/atribuição | Coberto | Validar circuito de aprovação municipal. |
| Contrato | Coberto manualmente | Sem assinatura qualificada; circuito manual assumido. |
| Rendas/pagamentos | Coberto manualmente | Sem MBWay/MB/cartão; comunicação deve ser clara. |

## 6. Pontos Críticos e Riscos

### Crítico 1 - ZIP com `.env`

O ZIP analisado inclui `.env` com:

- `APP_ENV=local`;
- `APP_KEY=base64:...`;
- `APP_DEBUG=true`;
- `APP_URL=http://127.0.0.1:8001`;
- `QUEUE_CONNECTION=sync`;
- `MAIL_MAILER=log`;
- paths locais de ferramentas Document AI.

Isto contradiz a intenção da QA-37, que criou scanner e documentação para impedir segredos em artefactos. O problema parece estar no processo de packaging: o scanner existe, mas os alvos default não varrem necessariamente a raiz completa do pacote com `.env`.

Decisão: bloquear qualquer envio externo de ZIP até limpeza e novo gate.

### Crítico 2 - Validação legal ainda demo

O seeder Alcanena é robusto, mas os próprios docs indicam que são fictícios:

- datas;
- fogos;
- rendas;
- limites;
- textos jurídicos;
- minuta contratual;
- membros/responsáveis.

Decisão: criar uma sprint de parametrização legal final com edital real.

### Crítico 3 - Excesso de superfície para primeira demonstração

A plataforma tem 1034 chamadas `Route::` em `web.php`. Isto é excelente como produto, mas perigoso numa primeira demonstração se o roteiro não for controlado.

Decisão: demonstrar apenas o caminho crítico:

1. portal público;
2. simulador;
3. registo/candidatura;
4. documentos;
5. análise municipal;
6. lista/audiência;
7. atribuição;
8. contrato manual;
9. inquilino/manutenção.

### Alto 4 - Linguagem e carga cognitiva

Já houve UX-07 de terminologia, mas ainda aparecem labels técnicas em inglês na configuração e possíveis ecrãs administrativos profundos.

Decisão: sprint curta de auditoria visual pt-PT e microcopy nos fluxos reais.

### Alto 5 - Acessibilidade precisa de validação browser real

QA-42 reforçou skip links, landmarks e testes automáticos. Ainda falta validação manual/axe/leitor de ecrã em fluxos longos de candidatura e upload.

Decisão: incluir auditoria assistida com browser real antes de utilizadores reais.

### Alto 6 - Operação municipal e formação

Runbooks existem, mas o município precisa de RACI, perfis, scripts de demonstração, guias de tarefas e plano de incidentes praticado.

Decisão: sprint de ensaio operacional, não apenas técnica.

## 7. Readiness por Fase

| Fase | Estado |
| --- | --- |
| Desenvolvimento local | Pronto, com ressalva de novo `phpunit/phpstan/build` no ambiente do utilizador. |
| Demonstração interna | Pronto após resolver ZIP `.env` e confirmar dropdown/sidebar. |
| Demonstração municipal controlada | Quase pronto, precisa de pacote sanitizado e roteiro fechado. |
| Staging municipal com dados fictícios | Pronto condicionado a queues, scheduler, health, backup/restore e smoke E2E. |
| Piloto com dados reais | Ainda não recomendado sem validação DPO/jurídica, edital real, WCAG real, pacote limpo e ensaio de incidentes. |
| Produção plena | Roadmap posterior, dependente de infraestrutura, SLA, suporte, backups, monitorização e decisão sobre integrações. |

## 8. Roadmap de Sprints Recomendado

### Sprint 41 - Fecho Técnico da Versão 7 e Correções Imediatas

Objetivo: fechar o estado anexado de forma limpa.

Tarefas:

- Confirmar e comitar a correção do dropdown/sidebar (`overflow-visible`, `align="side"`, `width="56"`).
- Validar que o dropdown não cria overflow lateral problemático em ecrãs menores.
- Correr `php -l`, `npm run build`, `php artisan view:clear`.
- Confirmar `git status` limpo.

Acceptance criteria:

- Menu de perfil funciona com sidebar expandida e recolhida.
- Sem clipping do dropdown.
- Build OK.
- Commit limpo.

### Sprint 42 - Release Packaging Sanitization Real

Objetivo: impedir definitivamente pacotes com `.env`, segredos ou lixo local.

Tarefas:

- Ajustar `scripts/check-release-artifact-safety.php` para modo `--full-package` ou scan da raiz extraída.
- Garantir que `.env`, `.DS_Store`, `.phpunit.result.cache`, `document-ai-test.png`, zips e artefactos locais falham o gate.
- Criar script de build de pacote que só inclui ficheiros permitidos.
- Adicionar teste que simula ZIP real com `.env`.
- Regenerar `MV-HAB 7` sanitizado ou preparar `MV-HAB 8`.

Acceptance criteria:

- O scanner falha contra o ZIP atual.
- O scanner passa contra pacote limpo.
- Nenhum `.env` é distribuído.
- Evidência guardada em `storage/qa/release-package-full-scan.txt`.

### Sprint 43 - Parametrização Real do 1.º Concurso de Alcanena

Objetivo: substituir dados demo por parâmetros reais aprováveis.

Tarefas:

- Criar matriz "Regulamento/Manual/Edital -> Entidade/Regra/Texto".
- Rever Artigos 8, 9, 12, 14, 15 e 17 contra configuração real.
- Fechar checklist documental real.
- Confirmar se há uma ou várias preferências de habitação.
- Inserir fogos reais ou dataset fictício validado para staging.
- Parametrizar prazos reais.
- Validar textos finais de edital, notificações e minutas.

Acceptance criteria:

- Documento de parametrização assinado/revisto.
- Testes regulatórios atualizados.
- Seeder demo separado de seeder staging real.
- Campos fictícios marcados claramente como fictícios.

### Sprint 44 - Roteiro Demonstrável End-to-End

Objetivo: criar um fluxo municipal simples e repetível para apresentação.

Tarefas:

- Criar cenário E2E com candidato fictício completo.
- Criar candidatura com documentos em vários estados.
- Criar processo com pedido de aperfeiçoamento, resposta e decisão.
- Criar lista provisória, reclamação/audiência, lista definitiva.
- Criar atribuição e contrato manual.
- Criar inquilino e pedido de manutenção.
- Criar guião de demo por persona.

Acceptance criteria:

- Roteiro executável em 30-45 minutos.
- Dados 100% fictícios.
- Nenhuma integração fora de âmbito é apresentada como ativa.
- Smoke test municipal passa.

### Sprint 45 - UX Pública e Candidato para Piloto

Objetivo: reduzir fricção no percurso público/candidato.

Tarefas:

- Componentizar ficha pública de habitação.
- Rever hero, CTA, documentos, localização e galeria.
- Criar checklist visual "Preparar candidatura".
- Melhorar copy de consentimento e alternativa presencial.
- Rever upload documental com linguagem do manual de Alcanena.
- Criar estados vazios e erros orientados.

Acceptance criteria:

- Um candidato entende o que fazer sem explicação externa.
- Documentos exigidos aparecem com nomes iguais ao edital/manual.
- CTA de candidatura é claro.
- Fluxo mobile validado.

### Sprint 46 - Auditoria WCAG Real e Correções

Objetivo: passar de acessibilidade pragmática para evidência de conformidade operacional.

Tarefas:

- Executar axe/browser nos fluxos públicos e candidato.
- Testar teclado em candidatura, documentos, reclamações e audiência.
- Rever contraste, foco, labels, mensagens de erro e skip links.
- Criar relatório WCAG por fluxo.

Acceptance criteria:

- Sem bloqueadores WCAG nos fluxos críticos.
- Correções testadas.
- Relatório anexável ao dossier municipal.

### Sprint 47 - Operação Municipal, RACI e Formação

Objetivo: preparar a equipa municipal para operar sem depender do programador.

Tarefas:

- Criar matriz final de perfis por equipa.
- Criar manual rápido por persona: técnico, júri, financeiro, manutenção, auditor.
- Ensaiar incident drill: documento exposto, job falhado, rollback.
- Criar checklist diária/semanal de operação.
- Definir suporte e escalonamento.

Acceptance criteria:

- Município sabe quem faz o quê.
- Perfis mínimos aplicados.
- Incidente simulado com evidência.
- Health command e queues verificados.

### Sprint 48 - Dossier RGPD e Jurídico Final

Objetivo: preparar entrada em dados reais.

Tarefas:

- Finalidades e bases legais finais.
- Prazos de retenção por entidade.
- Política de anonimização.
- Modelo de resposta a pedidos do titular.
- Texto final de privacidade.
- Parecer DPO/jurídico sobre comunicações oficiais por email.

Acceptance criteria:

- Checklist DPO concluída.
- Textos finais aplicados.
- Exportação RGPD testada.
- Acessos sensíveis auditados.

### Sprint 49 - Performance e Carga de Staging

Objetivo: validar que o produto aguenta volume municipal realista.

Tarefas:

- Criar dataset sintético: candidatos, documentos, processos, listas.
- Medir query count nos principais ecrãs.
- Validar exportações grandes.
- Rever índices críticos.
- Testar queues para IA documental, comunicações e relatórios.

Acceptance criteria:

- Sem `N+1` crítico nos fluxos principais.
- Exportações usam storage privado e não rebentam memória.
- Ecrãs críticos dentro de tempos aceitáveis.

### Sprint 50 - Decisão de Integrações Futuras

Objetivo: decidir com clareza o que fica fora do piloto e o que entra no roadmap.

Tarefas:

- Reavaliar Autenticação.gov/CMD.
- Reavaliar assinatura digital qualificada.
- Reavaliar pagamentos MBWay/MB/cartão.
- Reavaliar integração com gestão documental municipal.
- Criar ADR por integração.

Acceptance criteria:

- Cada integração tem decisão: fora de âmbito, piloto futuro ou produção futura.
- Nenhuma promessa comercial fica ambígua.
- Backlog técnico fica estimado.

## 9. Recomendações de Prioridade

Próximas 2 semanas:

1. Fechar dropdown/sidebar e commit.
2. Corrigir packaging real do ZIP.
3. Criar pacote sanitizado.
4. Ensaiar roteiro E2E com dados fictícios.
5. Validar manualmente portal público e candidatura.

Próximas 4-6 semanas:

1. Parametrização real Alcanena.
2. Dossier RGPD/jurídico.
3. WCAG real.
4. Formação/RACI municipal.
5. Performance staging.

Depois do piloto controlado:

1. APIs/interoperabilidade.
2. Integrações externas, se aprovadas.
3. Automação financeira.
4. Assinatura digital.
5. BI avançado/indicadores territoriais.

## 10. Comandos Recomendados de Validação

Executar no ambiente do utilizador:

```bash
git status --short --branch
composer validate --strict
php artisan optimize:clear
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
./vendor/bin/phpstan analyse --memory-limit=1G -v
./vendor/bin/pint --test
npm run build
php artisan route:list --except-vendor
git diff --check
```

Para packaging:

```bash
php scripts/check-release-artifact-safety.php
php scripts/check-release-artifact-safety.php .env
php scripts/check-release-artifact-safety.php /caminho/para/pacote-extraido
```

Nota: o terceiro comando exige que o script aceite scan de pasta/raiz completa de pacote; se ainda não aceitar corretamente, deve ser implementado na Sprint 42.

## 11. Decisão Recomendada

Não adicionar novas macrofuncionalidades antes de fechar o pacote municipal.

A plataforma tem base técnica suficiente para demonstração e staging controlado. O caminho mais seguro é:

1. limpar e proteger o artefacto;
2. ensaiar o procedimento real de Alcanena;
3. validar UX pública/candidato;
4. fechar RGPD/jurídico;
5. preparar equipa municipal;
6. só depois abrir a dados reais.

Esta ordem reduz risco legal, risco reputacional e risco de a complexidade da plataforma esconder o valor real já construído.
