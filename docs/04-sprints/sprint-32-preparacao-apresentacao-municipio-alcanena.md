# Sprint 32 — Preparação da Apresentação ao Município de Alcanena

## 1. Objetivo da Sprint

Preparar a plataforma CRM MV HAB para apresentação ao Município de Alcanena, consolidando uma demo operacional alinhada com o regulamento municipal, os requisitos da plataforma e o estado real do produto.

Conclusão principal a transmitir:

```text
A plataforma já está suficientemente forte para uma apresentação ao Município de Alcanena, sobretudo no backoffice, candidaturas, documentos, elegibilidade, pontuação, listas, auditoria e RGPD.
```

Discurso recomendado:

```text
Plataforma operacional com núcleo administrativo maduro, já alinhada com o regulamento de Alcanena, com roadmap claro para portal público avançado, pagamentos e assinatura digital.
```

Esta sprint não deve criar promessas funcionais falsas. Deve preparar uma apresentação honesta, demonstrável e tecnicamente segura.

---

## 2. Âmbito Obrigatório

Executar apenas:

```text
Sprint 32 — Preparação da Apresentação ao Município de Alcanena
```

Não avançar para qualquer sprint seguinte sem validação explícita.

Criar um output final apenas para esta sprint.

Ignorar qualquer verificação de branch Git. Não executar comandos para validar branch atual, mudar branch, criar branch ou bloquear execução por causa da branch.

---

## 3. Princípio de Apresentação

A apresentação deve demonstrar como valor principal:

```text
Backoffice municipal robusto.
Candidaturas completas.
Checklist documental privada.
Elegibilidade, tipologia e pontuação.
Listas provisórias/definitivas.
Auditoria e histórico processual.
RGPD e permissões.
Roadmap claro para módulos ainda não fechados.
```

Evitar apresentar como pronto:

```text
Assinatura digital.
Pagamentos digitais com gateway real.
Portal público avançado completo, se ainda estiver parcial.
Mapa/filtros/brochuras se não estiverem implementados e testados.
```

Usar linguagem:

```text
Operacional
Maduro no núcleo administrativo
Alinhado com regulamento
Evolutivo
Seguro
Auditável
Preparado para pilotos e afinação municipal
```

Evitar linguagem:

```text
Produto final fechado
Pagamentos prontos
Assinatura digital integrada
Portal público completo se não estiver
Automação integral de decisões
```

---

## 4. Match com Requisitos do Município

Validar e preparar demonstração por área:

| Área | Estado | Prioridade para apresentação |
|---|---:|---|
| Registo de adesão antes da candidatura | Forte | Demonstrar |
| Candidatura com agregado, rendimentos e documentos | Forte | Demonstrar |
| Checklist documental privada e validável | Forte | Demonstrar |
| Elegibilidade, tipologia e pontuação | Forte | Demonstrar com caso Alcanena |
| Backoffice técnico, validação e auditoria | Muito forte | Demonstrar como principal valor |
| Listas provisórias/definitivas e audiência prévia | Forte, com afinação UX | Demonstrar |
| Visitas e apoio/tickets | Já existe no zip recente, confirmar estabilidade | Demonstrar se está estável |
| Portal público com concursos | Parcial | Mostrar como módulo em evolução |
| Mapa, filtros e brochuras por empreendimento | Lacuna prioritária | Corrigir antes da apresentação, se possível |
| Área do inquilino/senhorio | Base existe | Demonstrar como fase pós-atribuição |
| Pagamentos digitais | Sem gateway real | Apresentar como roadmap |
| Assinatura digital | Não integrada | Roadmap, não prometer como pronto |

---

## 5. Requisitos Funcionais a Cobrir no Discurso

Os requisitos da plataforma pedem, para o candidato:

```text
Oferta pública com mapa.
Filtros.
Brochura.
Registo.
Simulador.
Candidatura.
Visitas.
Acompanhamento.
Notificações.
Assinatura.
Área do inquilino.
Pagamentos.
```

Os requisitos da plataforma pedem, para a Câmara:

```text
Concursos.
Minutas.
Notificações.
Análise técnica.
Pontuação.
Listas.
Sorteios.
Relatório final.
Gestão senhorio/pós-atribuição.
```

Classificar cada requisito como:

```text
Pronto para demonstrar.
Parcial, demonstrar com ressalva.
Roadmap.
Não demonstrar nesta apresentação.
```

---

## 6. Regulamento de Alcanena — Pontos Obrigatórios

Preparar demo alinhada com o regulamento de Alcanena.

O regulamento exige:

```text
Concurso mediante candidatura.
Avaliação por júri.
Publicitação por Aviso/Edital.
Identificação das habitações.
Rendas mínimas/máximas.
Valor máximo de rendimento.
Registo de adesão antes da candidatura.
Anexação obrigatória de documentos.
Validação documental.
Número de registo.
Audiência prévia em caso de indeferimento.
Pedidos de informação adicional no prazo de 5 dias úteis.
Classificação pelo Anexo I.
Critérios de qualificação.
Critério de idade média.
Critério de dependentes.
Critério de deficiência/multideficiência.
Desempate.
Eventual sorteio público.
```

Não alterar regras legais sem validação.

Em caso de conflito, prevalece o Regulamento Municipal aplicável.

---

## 7. Prioridade Máxima Antes da Apresentação

Executar, por esta ordem:

```text
1. Preparar demo Alcanena com concurso, fogos, rendas, documentos, candidatura, pontuação e lista provisória.
2. Melhorar página pública dos fogos com filtros por freguesia/tipologia/renda, ficha do imóvel e brochura simples.
3. Confirmar que visitas, tickets e inconsistências estão acessíveis na UI e não apenas no código.
4. Evitar apresentar assinatura digital e pagamentos digitais como prontos.
5. Destacar segurança: documentos privados, auditoria, RGPD, histórico processual e permissões.
```

---

## 8. Entregáveis Obrigatórios

Criar ou atualizar:

```text
Demo Alcanena operacional.
Dados fictícios coerentes.
Concurso Alcanena demonstrável.
Habitações/fogos demonstráveis.
Checklist documental demonstrável.
Candidatura demonstrável.
Pontuação demonstrável.
Lista provisória demonstrável.
Página pública de fogos minimamente apresentável.
Filtros públicos por freguesia/tipologia/renda.
Ficha pública do imóvel.
Brochura simples por fogo/empreendimento.
Confirmação UI de visitas/tickets/inconsistências.
Documento de roteiro de apresentação.
Documento de matriz requisitos vs plataforma.
Documento de roadmap honesto.
Relatório de qualidade da Sprint 32.
```

---

## 9. Demo Alcanena — Dados Obrigatórios

Criar dados fictícios, sem dados pessoais reais.

### 9.1 Município

Usar:

```text
Município de Alcanena
```

### 9.2 Programa/Concurso

Criar ou preparar:

```text
Programa Municipal de Arrendamento Acessível de Alcanena
Concurso demonstrativo Alcanena 2026
Aviso/Edital fictício
Prazo de abertura
Prazo de encerramento
Renda mínima
Renda máxima
Valor máximo de rendimento
Critérios de pontuação do Anexo I
Estado publicável/demonstrável
```

### 9.3 Fogos/Habitações

Criar pelo menos:

```text
T1 Alcanena Centro
T2 Alcanena
T3 Minde
T2 Monsanto
```

Cada fogo deve ter:

```text
Referência pública.
Título público.
Tipologia.
Freguesia.
Localidade.
Área bruta.
Área útil.
Renda.
Eficiência energética, se aplicável.
Estado público.
Visibilidade.
Descrição pública.
Resumo.
Latitude/longitude pública aproximada, se usar mapa.
Precisão da localização.
SEO title.
SEO description.
```

Não usar moradas privadas reais se a política pública exigir ocultação.

### 9.4 Candidato Fictício

Criar pelo menos:

```text
Candidato fictício principal.
Agregado fictício.
Rendimentos fictícios.
Documentos fictícios.
Candidatura submetida.
Candidatura em análise.
Candidatura elegível com pontuação.
Candidatura com pedido de aperfeiçoamento.
```

Não usar dados pessoais reais.

### 9.5 Documentos Fictícios

Preparar checklist documental:

```text
Documento de identificação.
Comprovativo de NIF.
IRS ou nota de liquidação.
Recibo de vencimento.
Comprovativo de morada.
Declaração Segurança Social, se aplicável.
Atestado Multiusos, se aplicável.
Comprovativo de agregado, se aplicável.
```

Documentos devem permanecer em storage privado.

---

## 10. Fluxo de Demonstração Obrigatório

Preparar um roteiro demonstrável:

### 10.1 Backoffice

Demonstrar:

```text
Criação/gestão de programa.
Criação/gestão de concurso.
Aviso/Edital.
Habitações associadas.
Critérios.
Prazos.
Checklist documental.
Minutas/notificações se existirem.
```

### 10.2 Candidato

Demonstrar:

```text
Registo/adesão antes da candidatura.
Simulador, se estiver estável.
Criação de candidatura.
Agregado.
Rendimentos.
Upload documental.
Submissão.
Acompanhamento do estado.
Pedido de aperfeiçoamento, se aplicável.
```

### 10.3 Técnico Municipal

Demonstrar:

```text
Dashboard técnico.
Análise da candidatura.
Checklist documental privada.
Validação documental.
Elegibilidade.
Tipologia.
Pontuação.
Histórico processual.
Auditoria.
Permissões.
```

### 10.4 Júri/Listas

Demonstrar:

```text
Lista provisória.
Audiência prévia.
Reclamações, se existirem.
Lista definitiva.
Critérios de desempate.
Sorteio público, se já existir e estiver estável.
```

### 10.5 Segurança/RGPD

Demonstrar:

```text
Documentos privados.
Downloads autorizados.
Auditoria de acesso.
Histórico de ações.
Permissões por perfil.
Minimização de dados.
```

### 10.6 Roadmap

Apresentar como roadmap:

```text
Portal público avançado com mapa completo.
Brochuras avançadas por empreendimento.
Pagamentos digitais com gateway.
Assinatura digital.
Integração CMD/autenticacao.gov.
Área do inquilino/senhorio completa, se ainda parcial.
```

---

## 11. Portal Público — Melhorias Prioritárias

Antes da apresentação, se possível, implementar ou afinar:

```text
Página pública de concursos.
Página pública de fogos.
Filtros por freguesia.
Filtros por tipologia.
Filtros por intervalo de renda.
Ficha pública do imóvel.
Brochura simples em PDF/HTML.
Estado público do fogo.
CTA para simular/candidatar.
SEO básico.
```

### 11.1 Filtros mínimos

Filtros obrigatórios:

```text
Freguesia.
Tipologia.
Renda mínima/máxima.
Estado.
```

Filtros opcionais:

```text
Área.
Eficiência energética.
Disponibilidade.
```

### 11.2 Ficha do imóvel

Mostrar:

```text
Título.
Tipologia.
Freguesia.
Localidade.
Área útil.
Área bruta.
Renda.
Estado.
Descrição.
Concurso associado.
Requisitos principais.
Botão para candidatura/simulação.
Brochura.
```

Não mostrar dados privados ou moradas completas sem autorização.

### 11.3 Brochura simples

Criar brochura em:

```text
HTML imprimível ou PDF simples, conforme stack existente.
```

Conteúdo:

```text
Município.
Concurso.
Fogo.
Tipologia.
Freguesia/localidade.
Área.
Renda.
Estado.
Resumo.
Instruções de candidatura.
Nota de localização aproximada, se aplicável.
```

---

## 12. Visitas, Tickets e Inconsistências

Confirmar se estão acessíveis pela UI:

```text
Visitas a imóveis.
Agendamento/reagendamento/cancelamento.
Tickets ou apoio ao candidato.
Inconsistências/documentos em falta.
Pedidos de informação adicional.
Prazo de 5 dias úteis.
Histórico.
Notificações internas.
```

Se existirem apenas no código:

```text
Criar acesso básico no backoffice/candidato se seguro.
Ou documentar como módulo técnico existente ainda em afinação UI.
```

Não demonstrar módulos instáveis.

---

## 13. Pagamentos e Assinatura Digital

Regras para apresentação:

```text
Não apresentar pagamentos digitais como prontos se não existir gateway real.
Não apresentar assinatura digital como integrada se não existir integração real.
Apresentar ambos como roadmap.
Explicar que a arquitetura suporta evolução modular.
```

Roadmap sugerido:

```text
Pagamentos: integração futura com gateway autorizado e reconciliação.
Assinatura digital: integração futura com CMD/autenticacao.gov ou fornecedor qualificado.
```

Não usar credenciais reais.

Não simular pagamentos reais.

---

## 14. Segurança, Auditoria e RGPD

Destacar como principal valor:

```text
Documentos privados por defeito.
Downloads autorizados.
Policies/perfis.
Auditoria de ações críticas.
Histórico processual.
Registo de acessos.
Pedidos RGPD, se existirem.
Minimização de dados.
Separação entre área pública e backoffice.
```

Preparar exemplos demonstráveis:

```text
Técnico vê candidatura.
Auditor consulta sem alterar, se perfil existir.
Candidato só vê os seus dados.
Documento privado não é acessível por URL público.
Ação crítica fica auditada.
```

---

## 15. Arquitetura e Código

Preservar arquitetura existente.

Antes de implementar, analisar:

```bash
rg "Contest|Program|HousingUnit|Public|Portal|Visit|Ticket|Inconsist" app database routes resources tests
rg "Application|Household|Income|Document|Checklist|Eligibility|Score|Ranking|List" app database routes resources tests
rg "Audit|RGPD|Gdpr|Policy|Gate::authorize" app database routes resources tests
rg "public.*housing|housing.*public|brochure|pdf" app database routes resources tests
```

Adaptar nomes reais do projeto.

Não criar duplicação de models se já existirem:

```text
Program
Contest
HousingUnit
Application
Household
Income
DocumentSubmission
RequiredDocument
ProvisionalList
DefinitiveList
Visit
Ticket
```

---

## 16. Models, Services e Controllers

Criar apenas se necessário.

Preferir completar módulos existentes.

Possíveis services:

```text
App\Services\Demo\AlcanenaDemoDataService
App\Services\PublicPortal\PublicHousingSearchService
App\Services\PublicPortal\HousingBrochureService
App\Services\Presentation\AlcanenaReadinessReportService
App\Services\Presentation\RequirementMatchMatrixService
```

Possíveis controllers:

```text
App\Http\Controllers\Public\PublicContestController
App\Http\Controllers\Public\PublicHousingUnitController
App\Http\Controllers\Public\HousingBrochureController
App\Http\Controllers\Backoffice\DemoReadinessController
```

Usar nomes reais do projeto.

Não misturar stack frontend.

Se o projeto é Blade/Vite/Tailwind/Alpine, manter Blade.

---

## 17. Seeders / Dados Demo

Criar seeder opcional:

```text
Database\Seeders\AlcanenaPresentationDemoSeeder
```

Requisitos:

```text
Idempotente.
Sem dados reais.
Seguro para ambiente de demo/staging.
Não destruir dados existentes.
Não usar migrate:fresh.
Não criar credenciais sensíveis.
Permitir reexecução sem duplicados.
```

Comando recomendado, se existir padrão no projeto:

```bash
php artisan db:seed --class=AlcanenaPresentationDemoSeeder
```

Se criar comando próprio:

```bash
php artisan demo:prepare-alcanena
```

O comando deve pedir confirmação em produção, ou bloquear por ambiente se for inseguro.

---

## 18. Rotas e UI

Criar ou completar rotas públicas:

```text
/concursos
/concursos/{slug}
/fogos
/fogos/{slug}
/fogos/{slug}/brochura
```

Adaptar aos nomes reais.

Criar ou completar views:

```text
resources/views/public/contests/index.blade.php
resources/views/public/contests/show.blade.php
resources/views/public/housing/index.blade.php
resources/views/public/housing/show.blade.php
resources/views/public/housing/brochure.blade.php
```

Se já existirem, melhorar sem duplicar.

UI deve ser:

```text
Clara.
Municipal.
Sóbria.
Demonstrável.
Responsiva.
Sem promessas de módulos não prontos.
```

---

## 19. Documentação Obrigatória

Criar ou atualizar:

```text
docs/backlog/sprint-32-preparacao-apresentacao-municipio-alcanena.md
docs/presentation/alcanena-demo-script.md
docs/presentation/alcanena-requirements-match.md
docs/presentation/alcanena-roadmap.md
docs/presentation/alcanena-demo-data.md
docs/presentation/alcanena-readiness-report.md
docs/qa/sprint-32-quality-report.md
docs/qa/test-coverage-matrix.md
docs/backlog/roadmap.md
```

### 19.1 docs/presentation/alcanena-demo-script.md

Incluir:

```text
Objetivo da apresentação.
Mensagem principal.
Fluxo da demo.
Dados demo.
O que demonstrar.
O que não demonstrar.
Frases recomendadas.
Perguntas difíceis e respostas.
```

### 19.2 docs/presentation/alcanena-requirements-match.md

Incluir tabela:

```text
Requisito.
Estado.
Onde demonstrar.
Risco.
Mensagem recomendada.
Prioridade.
```

### 19.3 docs/presentation/alcanena-roadmap.md

Separar:

```text
Pronto agora.
Afinação antes da apresentação.
Roadmap curto prazo.
Roadmap médio prazo.
Fora do âmbito atual.
```

Obrigatório incluir:

```text
Portal público avançado.
Mapa/filtros/brochuras.
Pagamentos digitais.
Assinatura digital.
Área inquilino/senhorio.
```

### 19.4 docs/presentation/alcanena-readiness-report.md

Incluir:

```text
Resumo executivo.
Estado global.
Pontos fortes.
Lacunas.
Riscos de apresentação.
Checklist pré-demo.
Recomendação objetiva.
```

### 19.5 docs/qa/sprint-32-quality-report.md

Incluir:

```text
Comandos executados.
Resultado das migrations, se aplicável.
Resultado dos seeders demo, se aplicável.
Resultado dos testes.
Resultado do PHPStan antes de publicar.
Confirmação de tentativa única com phpstan.neon.
Erros legados identificados.
Erros novos introduzidos: sim/não.
Estado da demo Alcanena.
Estado do portal público.
Estado dos filtros.
Estado das brochuras.
Estado visitas/tickets/inconsistências.
Riscos funcionais.
Riscos de apresentação.
Recomendação de publicação/demo.
```

---

## 20. Testes Obrigatórios

Criar ou completar testes conforme alterações.

### 20.1 Feature — Portal público

Criar ou completar:

```text
tests/Feature/PublicPortal/PublicContestPresentationTest.php
tests/Feature/PublicPortal/PublicHousingSearchTest.php
tests/Feature/PublicPortal/PublicHousingBrochureTest.php
```

Cobrir:

```text
Página pública de concursos carrega.
Concurso Alcanena aparece quando publicável.
Página pública de fogos carrega.
Filtro por freguesia funciona.
Filtro por tipologia funciona.
Filtro por renda funciona.
Ficha do imóvel carrega.
Brochura simples carrega.
Dados privados não aparecem.
Morada completa não aparece quando não autorizada.
```

### 20.2 Feature — Demo Alcanena

Criar:

```text
tests/Feature/Demo/AlcanenaPresentationDemoTest.php
```

Cobrir:

```text
Seeder demo é idempotente.
Concurso demo existe.
Fogos demo existem.
Checklist documental existe.
Candidatura demo existe.
Pontuação demo existe.
Lista provisória demo existe.
Não há dados pessoais reais conhecidos.
```

### 20.3 Feature — Backoffice demonstrável

Criar ou completar:

```text
tests/Feature/Backoffice/AlcanenaBackofficeReadinessTest.php
```

Cobrir:

```text
Técnico autorizado acede ao concurso.
Técnico autorizado acede à candidatura.
Técnico autorizado vê documentos privados.
Técnico autorizado vê elegibilidade/pontuação.
Técnico autorizado vê lista provisória.
Ações críticas usam policy.
Consulta sensível é auditada quando aplicável.
```

### 20.4 Feature — Visitas/tickets/inconsistências

Criar ou completar:

```text
tests/Feature/Backoffice/PresentationSupportModulesTest.php
```

Cobrir:

```text
Rotas de visitas existem ou pendência documentada.
Rotas de tickets existem ou pendência documentada.
Rotas de inconsistências/pedidos de informação existem ou pendência documentada.
UI acessível para técnico/candidato quando aplicável.
```

---

## 21. PHPStan e Tipagem

Todos os ficheiros novos devem ser preparados para PHPStan.

Corrigir especialmente:

```text
missingType.generics
missingType.iterableValue
argument.type
return.type
property.notFound
method.notFound
enum/value type mismatch
invalid relation generics
array shape incompleto
mixed desnecessário
```

Em relações Eloquent, usar PHPDoc generics.

Em arrays estruturados, usar PHPDoc:

```php
/**
 * @return array{
 *   requirement: string,
 *   status: string,
 *   priority: string,
 *   demo_path: string|null,
 *   risk: string|null,
 *   message: string
 * }
 */
```

Não adicionar `mixed` sem necessidade.

Não silenciar erros com ignores genéricos.

---

## 22. Verificação PHPStan Antes de Publicar

Antes de considerar a sprint pronta para publicação/demo, tentar executar PHPStan uma única vez usando `phpstan.neon`, se o ficheiro existir.

Executar apenas uma tentativa:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint32-before-publish.json
```

Se o comando falhar, não repetir automaticamente com outras configurações.

Depois gerar versão texto apenas se for possível sem nova análise pesada. Se não for possível, documentar que só foi gerado JSON.

Se `phpstan.neon` não existir, executar no máximo uma tentativa com a configuração padrão:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint32-before-publish.json
```

Se `vendor/bin/phpstan` não existir, documentar:

```text
PHPStan não executado porque vendor/bin/phpstan não existe.
Bloqueia apresentação/publicação: depende da política do projeto.
```

Não afirmar que PHPStan passou se não foi executado.

Não ocultar erros.

No relatório final, distinguir:

```text
Erros legados já existentes
Erros introduzidos em ficheiros novos/alterados pela Sprint 32
Erros bloqueantes para apresentação
Erros não bloqueantes
```

---

## 23. Comandos Finais Obrigatórios

Executar, adaptando à stack real:

```bash
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash
php artisan migrate
```

Se existirem seeders demo:

```bash
php artisan db:seed --class=AlcanenaPresentationDemoSeeder
```

Se o projeto usar Pint:

```bash
./vendor/bin/pint
```

Se o projeto usar frontend build:

```bash
npm run build
```

PHPStan:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint32-before-publish.json
```

Executar PHPStan com `phpstan.neon` apenas uma vez.

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
Bloqueia apresentação: sim/não
```

Não afirmar que comandos passaram se não foram executados.

---

## 24. Critérios de Aceitação

A Sprint 32 está concluída quando:

```text
Existe demo Alcanena preparada.
Existe concurso demonstrável.
Existem fogos demonstráveis.
Existem rendas e requisitos demonstráveis.
Existe checklist documental demonstrável.
Existe candidatura demonstrável.
Existe pontuação demonstrável.
Existe lista provisória demonstrável.
Backoffice técnico está pronto para demonstração.
Auditoria/RGPD/permissões são demonstráveis.
Página pública de concursos está demonstrável ou limitação documentada.
Página pública de fogos está demonstrável ou limitação documentada.
Filtros por freguesia/tipologia/renda existem ou pendência documentada.
Ficha do imóvel existe ou pendência documentada.
Brochura simples existe ou pendência documentada.
Visitas/tickets/inconsistências foram confirmados na UI ou pendência documentada.
Pagamentos digitais estão marcados como roadmap.
Assinatura digital está marcada como roadmap.
Matriz requisitos vs plataforma foi criada.
Roteiro de apresentação foi criado.
Roadmap honesto foi criado.
Relatório de prontidão foi criado.
Não foram usados dados pessoais reais.
Não foram usadas credenciais.
Não foram prometidas funcionalidades fora do estado real.
PHPStan foi tentado antes de publicar/demo, usando phpstan.neon uma única vez quando disponível.
php artisan route:list executa sem erro ou falha é documentada.
php artisan test executa sem erro ou falha é documentada.
php artisan migrate executa sem erro se houver migrations novas ou falha é documentada.
Seeder demo executa sem erro se existir ou falha é documentada.
./vendor/bin/pint executa sem erro se existir ou alterações são documentadas.
npm run build executa sem erro se aplicável ou falha é documentada.
```

---

## 25. Fora de Âmbito

Não implementar nesta sprint:

```text
Gateway real de pagamentos.
Assinatura digital real.
Integração CMD/autenticacao.gov.
Reescrita profunda do portal público.
Alteração dos critérios legais sem validação.
Dados reais de candidatos.
Credenciais reais.
Automação de decisões administrativas.
Alterações destrutivas na base de dados.
migrate:fresh em ambiente com dados.
```

---

## 26. Riscos e Mitigações

### 26.1 Risco de prometer demasiado

Mitigação:

```text
Separar pronto, parcial e roadmap.
Documentar limitações.
Usar roteiro de apresentação.
Não demonstrar módulos instáveis.
```

### 26.2 Risco de demo frágil

Mitigação:

```text
Seeder idempotente.
Dados demo consistentes.
Checklist pré-demo.
Testes focados no percurso.
Ambiente staging/demo validado.
```

### 26.3 Risco RGPD

Mitigação:

```text
Dados fictícios.
Documentos privados.
Mascaramento.
Auditoria.
Sem dados reais.
```

### 26.4 Risco funcional/regulamentar

Mitigação:

```text
Não alterar regras legais.
Alinhar com regulamento de Alcanena.
Destacar júri, audiência prévia, pedidos de informação e listas.
```

---

## 27. Resposta Final Obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Confirmação de que a verificação de branch Git foi ignorada
4. Estado da demo Alcanena
5. Estado do concurso demo
6. Estado dos fogos/habitações demo
7. Estado das rendas/requisitos demo
8. Estado da checklist documental
9. Estado da candidatura demo
10. Estado da pontuação demo
11. Estado da lista provisória demo
12. Estado do backoffice técnico
13. Estado da auditoria/RGPD/permissões
14. Estado da página pública de concursos
15. Estado da página pública de fogos
16. Estado dos filtros por freguesia/tipologia/renda
17. Estado da ficha pública do imóvel
18. Estado da brochura simples
19. Estado de visitas/tickets/inconsistências na UI
20. Confirmação de que pagamentos digitais foram tratados como roadmap
21. Confirmação de que assinatura digital foi tratada como roadmap
22. Models criados ou alterados
23. Migrations criadas
24. Seeders/comandos demo criados ou alterados
25. Services criados ou alterados
26. Controllers criados ou alterados
27. Form Requests criados ou alterados
28. Policies criadas ou alteradas
29. Rotas criadas ou alteradas
30. Views/components criados ou alterados
31. Documentação criada ou atualizada
32. Resultado de php artisan route:list
33. Resultado de php artisan migrate, se aplicável
34. Resultado do seeder demo, se aplicável
35. Resultado de php artisan test
36. Resultado de ./vendor/bin/pint, se aplicável
37. Resultado de npm run build, se aplicável
38. Resultado PHPStan antes de publicar/demo
39. Confirmação de que phpstan.neon foi tentado uma única vez, se existia
40. Erros PHPStan legados considerados
41. Novos erros PHPStan introduzidos pela Sprint 32: sim/não
42. Riscos de apresentação ainda existentes
43. Riscos técnicos ainda existentes
44. Pendências técnicas
45. Confirmação de que não foram usados dados pessoais reais
46. Confirmação de que não foram usadas credenciais
47. Confirmação de que não foram prometidas funcionalidades fora do estado real
48. Recomendação objetiva para apresentar ou não apresentar ao Município
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

## 28. Definition of Done

A Sprint 32 só está concluída quando existir uma demo Alcanena consistente, alinhada com os requisitos e o regulamento municipal, com núcleo administrativo demonstrável, portal público minimamente apresentável ou limitações documentadas, matriz requisitos vs plataforma, roteiro de apresentação, roadmap honesto, testes/validações executados, PHPStan tentado antes da publicação/demo com uma única tentativa usando `phpstan.neon` quando disponível, e confirmação de que pagamentos digitais e assinatura digital são tratados como roadmap e não como funcionalidades prontas.

---

## 29. Execução Imediata

Executa agora apenas:

```text
Sprint 32 — Preparação da Apresentação ao Município de Alcanena
```

Fim da master prompt da Sprint 32.
