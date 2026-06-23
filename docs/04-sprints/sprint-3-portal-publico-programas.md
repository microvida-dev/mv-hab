# Sprint 3 — Portal Público e Programas

## Prioridade de desenvolvimento

Esta sprint pertence à prioridade funcional:

```text
1. Candidatura
```

A Sprint 3 cria a base pública necessária para que, nas sprints seguintes, o cidadão possa consultar programas, concursos e iniciar o caminho para o registo de adesão e candidatura.

Esta sprint não implementa ainda candidatura formal.
Cria apenas a camada pública de consulta, informação e orientação.

---

# Objetivo da Sprint

Implementar o Portal Público da plataforma municipal de Arrendamento Acessível, permitindo que qualquer cidadão, sem autenticação, possa:

- Consultar a página inicial da plataforma;
- Consultar programas municipais publicados;
- Consultar detalhe de programas;
- Consultar concursos publicados ou abertos;
- Consultar detalhe de concursos;
- Ver prazos relevantes;
- Ver FAQ institucional;
- Aceder a links de login/registo;
- Compreender o processo antes de iniciar candidatura.

Esta sprint deve aproveitar a fundação técnica e UX/UI criada nas Sprints 0, 1 e 2.

---

# Instrução operacional para Codex

Executa apenas esta Sprint 3.

Não avances para Sprint 4, Sprint 5, Sprint 6, Sprint 8 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Antes de alterar código, lê primeiro a documentação existente:

```text
docs/architecture/technical-architecture.md
docs/architecture/data-model-overview.md
docs/product/product-vision.md
docs/product/functional-requirements.md
docs/product/user-roles.md
docs/product/process-workflows.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/backlog/roadmap.md
docs/backlog/sprint-0-foundation.md
docs/backlog/sprint-1-foundation.md
docs/backlog/sprint-2-foundation.md
docs/backlog/sprint-3-portal-publico-programas.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Se algum ficheiro de documentação não existir, continua a execução se for possível, mas documenta a ausência na resposta final.

Antes de implementar, confirma que existem ou identifica alternativas para:

```text
Modelo Program ou equivalente
Modelo Contest ou equivalente
Estados de programas
Estados de concursos
Layout público criado na Sprint 2
Componentes base criados na Sprint 2
Sistema de autenticação existente
Rotas públicas existentes
```

Não reimplementar estruturas já existentes de forma duplicada.

---

# Âmbito desta Sprint

## Incluído

Implementar:

```text
Portal público
Página inicial pública
Listagem pública de programas
Detalhe público de programa
Listagem pública de concursos
Detalhe público de concurso
Página FAQ
Blocos informativos sobre o processo
CTA para login/registo
Apresentação de estados
Apresentação de prazos
Empty states públicos
SEO técnico básico
Testes públicos mínimos
```

## Fora de âmbito

Não implementar nesta sprint:

```text
Registo de adesão funcional
Área pessoal funcional avançada
Gestão de agregado familiar
Gestão de rendimentos
Submissão de candidatura
Upload documental
Validação documental
Motor de elegibilidade
Classificação
Ranking
Listas provisórias
Reclamações
Audiência de interessados
Lista definitiva
Atribuição
Contratos
Pagamentos
Manutenção
Notificações reais
Relatórios avançados
RGPD operacional completo
```

Apenas criar links, CTAs ou placeholders claros para ações que serão implementadas nas sprints seguintes.

---

# Stack e convenções

Antes de implementar, identificar a stack existente:

```text
Blade
Livewire
Inertia
Vue
React
Tailwind
Bootstrap
Outro
```

Manter a stack existente.

Não misturar stacks sem necessidade.

Se o projeto usa Blade/Tailwind, criar views em:

```text
resources/views/public
```

Se o projeto usa Inertia/Vue/React, criar páginas equivalentes na stack existente.

Controllers públicos devem ficar preferencialmente em:

```text
App\Http\Controllers\Public
```

Se o projeto já tiver outra convenção consistente, manter essa convenção.

---

# Rotas públicas obrigatórias

Criar ou garantir as seguintes rotas públicas:

```text
GET /
GET /programas
GET /programas/{program}
GET /concursos
GET /concursos/{contest}
GET /perguntas-frequentes
```

Se o projeto já usa slugs em inglês, pode usar:

```text
GET /
GET /programs
GET /programs/{program}
GET /contests
GET /contests/{contest}
GET /faq
```

Preferência para português se o produto estiver orientado para municípios portugueses.

## Nomes recomendados das rotas

```text
public.home
public.programs.index
public.programs.show
public.contests.index
public.contests.show
public.faq
```

---

# Controllers públicos

Criar ou atualizar:

```text
Public\HomeController
Public\ProgramController
Public\ContestController
Public\FaqController
```

## Responsabilidades

### HomeController

- Carregar programas publicados;
- Carregar concursos abertos/publicados;
- Mostrar dados de resumo;
- Renderizar a página inicial.

### ProgramController

- Listar programas publicados;
- Mostrar detalhe de programa publicado;
- Impedir acesso público a programas em rascunho ou arquivados, salvo regra contrária já definida.

### ContestController

- Listar concursos publicados ou abertos;
- Mostrar detalhe de concurso publicado ou aberto;
- Impedir acesso público a concursos em rascunho;
- Mostrar prazos e estado.

### FaqController

- Renderizar FAQ estática inicial ou baseada em configuração simples, conforme a arquitetura existente.

---

# Regras de visibilidade pública

## Programas

Na área pública devem aparecer apenas programas com estado:

```text
published
```

Opcionalmente, se já existir regra de programa ativo por datas:

```text
published + dentro do período starts_at/ends_at
```

Não mostrar programas em estado:

```text
draft
archived
deleted
```

## Concursos

Na área pública devem aparecer concursos com estado:

```text
published
open
```

Opcionalmente, podem aparecer concursos encerrados se a plataforma tiver secção de histórico, mas esta sprint deve focar-se em publicados e abertos.

Não mostrar concursos em estado:

```text
draft
archived
deleted
```

## Prazos

Se existirem datas:

```text
application_starts_at
application_ends_at
published_at
```

Mostrar sempre em formato legível para PT-PT.

Exemplo:

```text
Candidaturas de 01/05/2026 a 31/05/2026
```

---

# Página inicial pública

## Objetivo

Criar uma homepage clara, institucional e orientada ao cidadão.

## Conteúdo obrigatório

A página inicial deve conter:

```text
Hero institucional
Explicação simples da plataforma
Bloco "Como funciona"
Bloco "Programas disponíveis"
Bloco "Concursos abertos"
Bloco "Antes de se candidatar"
Bloco "Precisa de ajuda?"
CTA para consultar concursos
CTA para entrar/registar
```

## Copy base sugerido

Pode usar ou adaptar o seguinte conteúdo:

```text
Plataforma Municipal de Arrendamento Acessível

Consulte programas de habitação, acompanhe concursos disponíveis e prepare a sua candidatura de forma simples, segura e transparente.

Esta plataforma permite aos cidadãos consultar informação sobre programas municipais de habitação, verificar concursos abertos e, nas próximas fases, submeter e acompanhar candidaturas online.
```

## Bloco "Como funciona"

Criar quatro passos visuais:

```text
1. Consulte os programas
Conheça os programas municipais disponíveis e as condições gerais de acesso.

2. Consulte os concursos
Veja os concursos publicados, prazos, habitações e informação relevante.

3. Prepare os seus dados
Antes da candidatura, reúna informação sobre o agregado familiar, rendimentos e situação habitacional.

4. Acompanhe o processo
Após submissão futura da candidatura, poderá acompanhar notificações, pedidos de documentos e decisões.
```

## Bloco "Antes de se candidatar"

Mostrar orientação não vinculativa:

```text
Antes de iniciar uma candidatura, confirme se tem consigo os dados de identificação, informação do agregado familiar, comprovativos de rendimentos e documentos habitacionais que possam ser solicitados no aviso de concurso.
```

Não listar documentos obrigatórios como definitivos nesta sprint se ainda não existir motor documental.

---

# Página de programas

## Rota

```text
/programas
```

## Objetivo

Listar programas municipais publicados.

## Cada card de programa deve mostrar

```text
Nome do programa
Descrição curta
Estado
Município, se aplicável
Datas, se aplicável
Número de concursos publicados associados, se disponível
Botão "Ver programa"
```

## Empty state

Se não existirem programas publicados, mostrar:

```text
De momento não existem programas publicados.
Consulte esta página regularmente para acompanhar novas oportunidades.
```

## Filtros

Nesta sprint, os filtros são opcionais.

Se forem implementados, limitar a:

```text
Estado
Município, se existir mais do que um
Pesquisa por nome
```

Não criar filtros complexos ainda.

---

# Página de detalhe de programa

## Rota

```text
/programas/{program}
```

## Objetivo

Mostrar informação detalhada do programa.

## Conteúdo obrigatório

```text
Nome
Descrição
Descrição curta, se existir
Estado
Município
Data de início
Data de fim
Data de publicação
Concursos associados publicados ou abertos
CTA para consultar concursos
CTA para login/registo
```

## Regras

- Não mostrar programas em rascunho.
- Se o programa não estiver público, responder 404 ou comportamento equivalente.
- Mostrar apenas concursos associados que sejam públicos.

---

# Página de concursos

## Rota

```text
/concursos
```

## Objetivo

Listar concursos públicos.

## Cada card de concurso deve mostrar

```text
Título
Referência
Programa associado
Estado
Período de candidatura
Município
Botão "Ver concurso"
```

## Estados visuais

Usar badge de estado criado na Sprint 2:

```text
Publicado
Aberto
Encerrado, apenas se visível
Em análise, apenas se visível
```

## Empty state

Se não existirem concursos publicados ou abertos:

```text
De momento não existem concursos disponíveis.
Quando forem publicados novos concursos, poderá consultá-los nesta página.
```

## Ordenação recomendada

```text
Concursos abertos primeiro
Depois concursos publicados
Depois por data de início de candidatura
```

---

# Página de detalhe de concurso

## Rota

```text
/concursos/{contest}
```

## Objetivo

Apresentar informação clara sobre um concurso.

## Conteúdo obrigatório

```text
Título
Referência
Programa associado
Município
Estado
Descrição
Data de publicação
Data de início das candidaturas
Data de fim das candidaturas
Prazos relevantes, se existirem
Informação sobre candidatura
CTA para login/registo
CTA para voltar aos concursos
```

## Bloco de aviso

Enquanto a candidatura formal não estiver implementada, mostrar:

```text
A submissão de candidaturas online será disponibilizada numa fase seguinte da plataforma. Consulte o aviso de concurso e prepare a documentação necessária.
```

Quando a Sprint 8 for implementada futuramente, este CTA deverá apontar para a candidatura formal.

## Prazos de concurso

Se existir entidade `ContestDeadline`, mostrar tabela ou timeline com:

```text
Nome do prazo
Descrição
Data de início
Data de fim
```

Se não existirem prazos registados, ocultar a secção ou mostrar empty state discreto.

---

# Página FAQ

## Rota

```text
/perguntas-frequentes
```

## Objetivo

Criar uma página pública de esclarecimento.

## Perguntas obrigatórias

Criar FAQ inicial com estas perguntas:

```text
O que é o Arrendamento Acessível?
Quem pode candidatar-se?
Como sei se existe concurso aberto?
Como posso consultar os programas disponíveis?
O que devo preparar antes de uma candidatura?
Como serei notificado durante o processo?
A candidatura online já está disponível?
Onde posso pedir apoio?
```

## Respostas

As respostas devem ser genéricas, institucionais e não vinculativas.

Não assumir regras específicas de elegibilidade que ainda não estejam configuradas no sistema.

Exemplo:

```text
Os requisitos concretos dependem do regulamento municipal e do aviso de cada concurso. Deve consultar sempre a página do concurso e os documentos oficiais associados.
```

---

# Componentes UX a usar

Reutilizar componentes da Sprint 2 sempre que existam:

```text
public-layout
page-header
section-header
card
info-card
action-card
button
status-badge
empty-state
breadcrumb
timeline
stepper
alert
```

Se algum componente ainda não existir, criar apenas o mínimo necessário, sem duplicar nomes ou padrões.

---

# SEO técnico básico

Implementar, se a stack permitir:

```text
Title por página
Meta description por página
Heading H1 único por página
URLs legíveis
Slugs para programas e concursos
Open Graph básico, se já existir estrutura
```

## Exemplos de titles

```text
Programas de Arrendamento Acessível
Concursos de Habitação
Detalhe do Concurso
Perguntas Frequentes
```

Não instalar pacotes SEO nesta sprint sem necessidade.

---

# Acessibilidade

Garantir:

```text
Estrutura correta de headings
Links com texto claro
Botões com labels compreensíveis
Contraste adequado
Estados não dependentes apenas de cor
Cards navegáveis de forma previsível
Tabelas legíveis em mobile
Foco visível em elementos interativos
```

---

# Responsividade

As páginas públicas devem funcionar em:

```text
Mobile
Tablet
Desktop
```

## Atenção especial

```text
Hero
Cards de programas
Cards de concursos
Timeline de prazos
Tabela de prazos
Menus
CTAs
FAQ
```

Em mobile, listas de cards devem ficar em coluna única.

---

# Segurança e autorização

## Regras

- Rotas públicas não exigem autenticação.
- Rotas públicas não podem expor dados privados.
- Não mostrar dados pessoais.
- Não mostrar logs.
- Não mostrar informação administrativa interna.
- Não mostrar programas em rascunho.
- Não mostrar concursos em rascunho.
- Não expor IDs internos se o projeto usa slugs.
- Não permitir ações de alteração em rotas públicas.

---

# Dados e seeds

Se necessário, ajustar seeders apenas para garantir visualização pública coerente.

Pode criar ou adaptar dados demo fictícios:

```text
1 programa publicado
1 programa em rascunho
1 concurso publicado
1 concurso aberto
1 concurso em rascunho
```

Os testes devem confirmar que rascunhos não aparecem publicamente.

Não usar dados pessoais reais.

Não usar municípios reais se o projeto preferir dados fictícios.

Se usar municípios reais por contexto demo, garantir que são apenas dados institucionais públicos e não pessoais.

---

# Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Homepage

```text
public_homepage_loads_successfully
public_homepage_shows_published_programs
public_homepage_shows_open_contests
```

## Programas

```text
public_programs_index_loads_successfully
published_programs_are_visible_publicly
draft_programs_are_not_visible_publicly
archived_programs_are_not_visible_publicly
public_program_show_loads_for_published_program
public_program_show_returns_404_for_draft_program
```

## Concursos

```text
public_contests_index_loads_successfully
published_contests_are_visible_publicly
open_contests_are_visible_publicly
draft_contests_are_not_visible_publicly
public_contest_show_loads_for_published_contest
public_contest_show_loads_for_open_contest
public_contest_show_returns_404_for_draft_contest
```

## FAQ

```text
public_faq_page_loads_successfully
public_faq_page_contains_basic_questions
```

## Segurança pública

```text
public_pages_do_not_require_authentication
public_pages_do_not_expose_audit_logs
public_pages_do_not_expose_admin_links_to_guests
```

---

# Comandos de validação

No final, executar:

```bash
php artisan route:list
php artisan test
```

Se existir frontend compilável:

```bash
npm run build
```

Se o projeto usar Pint:

```bash
./vendor/bin/pint
```

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
```

Não afirmar que comandos passaram se não foram executados.

---

# Atualização documental obrigatória

Atualizar os seguintes documentos, se existirem:

```text
docs/backlog/sprint-3-portal-publico-programas.md
docs/backlog/roadmap.md
docs/product/functional-requirements.md
docs/product/process-workflows.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Registar:

```text
O que foi implementado
Rotas públicas criadas
Controllers criados
Views/páginas criadas
Componentes reutilizados
Componentes criados
Testes criados
Comandos executados
Pendências para Sprint 4
```

---

# Critérios de aceitação da Sprint 3

A Sprint 3 está concluída quando:

```text
A homepage pública existe e carrega sem autenticação
A página de programas existe
A página de detalhe de programa existe
A página de concursos existe
A página de detalhe de concurso existe
A página FAQ existe
Apenas programas publicados aparecem publicamente
Programas em rascunho não aparecem publicamente
Apenas concursos publicados ou abertos aparecem publicamente
Concursos em rascunho não aparecem publicamente
Prazos de concurso aparecem quando existem
Estados aparecem com badges visuais
Empty states públicos existem
CTAs para login/registo existem
Não existe exposição de dados privados
Não existe exposição de links administrativos a guests
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
```

---

# Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Resumo do que foi implementado na Sprint 3
2. Ficheiros criados
3. Ficheiros alterados
4. Rotas públicas criadas
5. Controllers criados ou alterados
6. Views/páginas criadas ou alteradas
7. Componentes reutilizados
8. Componentes novos, se existirem
9. Alterações em seeders, se existirem
10. Testes criados ou alterados
11. Resultado dos comandos executados
12. Problemas encontrados
13. Pendências
14. Confirmação de que não foram implementadas funcionalidades fora de âmbito
15. Recomendação objetiva para avançar ou não para Sprint 4
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para Sprint 4 sem validação explícita.

---

# Definition of Done

A Sprint 3 só está concluída quando o cidadão consegue consultar publicamente programas e concursos, compreender o processo base e encontrar orientação clara para preparar futura candidatura, sem que exista ainda submissão formal de candidatura.

Fim da Sprint 3.

---

# Relatório de execução — 10/06/2026

## Estado

Sprint 3 implementada. Não foi iniciada qualquer funcionalidade da Sprint 4.

## Implementado

- Homepage pública com concursos, programas, explicação do processo, preparação, ajuda e acesso à conta.
- Listagem e detalhe público de programas publicados.
- Listagem e detalhe público de concursos publicados, incluindo estado e prazos.
- FAQ pública institucional e não vinculativa.
- Slugs públicos, meta description e títulos por página.
- Empty states e comportamento responsivo.
- Backoffice de programas e concursos com Form Requests, Policies e Services.
- Estados formais para programas, concursos e tipos de prazo.
- Regras de programa, prazos de concurso e membros de júri.
- Publicação condicionada a dados mínimos.
- Auditoria de criação, atualização, publicação e eliminação.
- Seed institucional fictício para demonstração.

## Rotas públicas

```text
GET /
GET /programas
GET /programas/{slug}
GET /concursos
GET /concursos/{slug}
GET /perguntas-frequentes
```

## Segurança e RGPD

- Guest não acede ao backoffice.
- Candidato não recebe permissões de gestão de programas ou concursos.
- Rascunhos, arquivos e soft deletes não aparecem publicamente.
- Páginas públicas não apresentam dados de candidatos, júri, logs ou campos internos.
- Não foram introduzidos dados pessoais reais, secrets, tokens ou alterações ao `.env`.

## Testes

Criado `tests/Feature/Sprint3PortalProgramsTest.php` e atualizado o teste da rota raiz.

Cobertura:

- homepage e FAQ;
- programas/concursos publicados, rascunhos e arquivos;
- detalhe por slug;
- CTA de concurso aberto;
- autorização do backoffice;
- validação de datas;
- proteção de estados;
- publicação e auditoria.

## Comandos executados

```text
php artisan migrate --force
php artisan db:seed --class=SystemAccessSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=ProgramSeeder
./vendor/bin/pint
php artisan route:list
php artisan test
npm run build
composer validate --no-check-publish
```

O PHPStan/Psalm não está instalado no projeto e, por isso, não foi executado.

Resultados finais:

- `php artisan route:list`: 100 rotas, sem erro.
- `php artisan test`: 37 testes e 126 asserções, todos aprovados.
- `npm run build`: concluído com sucesso.
- `./vendor/bin/pint --test`: concluído sem alterações pendentes.
- `php artisan migrate:status`: todas as migrations em estado `Ran`.
- Browser: portal, detalhe de concurso e backoffice renderizados sem erros de consola; detalhe validado a 390×844 sem overflow horizontal.
- Captura visual do browser: tentativa falhou por timeout do mecanismo de screenshot; sem impacto nos testes DOM e funcionais.

## Pendências para Sprint 4

- Validar os campos mínimos do perfil de candidato e do Registo de Adesão.
- Validar texto informativo RGPD, bases legais e consentimentos aplicáveis.
- Definir ownership e policies para acesso exclusivo aos próprios dados.
- Decidir se a verificação de email será obrigatória antes da adesão.
- Rever o modelo atual `Citizen` para evitar duplicação com `candidate_profiles`.
- Não reutilizar o CRUD legado de candidaturas como substituto do futuro fluxo formal.
