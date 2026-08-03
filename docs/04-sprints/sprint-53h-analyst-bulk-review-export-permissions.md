# Sprint 53H - Perfil municipal do analista, matriz final de permissions e segregacao de funcoes

## 1. Resumo executivo

A Sprint 53H fechou a matriz permission-first do Programa 53 com um template municipal versionado para analise, decisao documental, gestao de aperfeicoamentos, lotes e exportacao nao sensivel. A implementacao reutiliza a administracao de acessos existente, mantem permissions, entitlements, Policies, MFA e scope municipal como controlos independentes e adiciona rate limiting e auditoria read-only deterministica.

O resultado de repositorio passou os gates automatizados. A classificacao permanece `REPOSITORY_PASS_DEPLOYMENT_GATED`, porque nao houve deploy nem validacao do worker `reports`, scheduler, storage privado e geracao real no ambiente alvo.

## 2. Commit-base

- SHA: `ef91154c3333ffb78565448671c79862c445609a`.
- Origem: `main`, sem merge de `main` durante a sprint.
- Stack observada: PHP 8.4.21, Laravel 13.12.0, Node 24.11.0 e npm 11.6.1.

## 3. Branch

- `sprint-53h-analyst-bulk-review-export-permissions`.
- A branch foi criada no commit-base obrigatorio.
- Nao foi usado force push nem efetuado merge em `main`.

## 4. Commits

Sequencia coesa adotada:

1. `c3c441b7 docs(sprint-53h): audit program access and role architecture`;
2. `c0c67fb0 feat(access): add program 53 analyst export role template`;
3. `9c08c048 feat(security): enforce program 53 access boundaries and limits`;
4. `test(access): validate program 53 role matrix and workflow` - commit final de testes, integracao e deste relatorio; o SHA e confirmado apos a sua criacao.

## 5. Auditoria inicial

- O backoffice ja usava `active.backoffice`, `mfa.backoffice`, `log.backoffice`, permissions exatas, entitlement municipal e Policies.
- Nao existia middleware de role fixa nas rotas backoffice do Programa 53.
- O modelo `User` recebe permissions por roles; nao existe atribuicao direta normalizada ao utilizador.
- O catalogo continha as permissions necessarias, mas faltavam proveniencia/versionamento das roles municipais, rate limiters dedicados e auditoria automatica do programa.
- O MFA ja era orientado por risco/permission atraves do catalogo; a sprint preservou esse mecanismo.

## 6. Templates anteriores

Os templates existentes foram preservados sem alteracao semantica:

| Template | Versao | Permissions | Fingerprint preservado |
|---|---:|---:|---|
| `operador-recolha` | 1.0.0 | 11 | `3f601bc74585c6dc99249be19cb4db5fd596c768b0ac2a21cbe43d984abd6fb5` |
| `analista-candidaturas` | 1.0.0 | 30 | `fa8bdef37843b732c93b154939f93d578201f57b6d125116d021a9f90a3a59bf` |
| `exportador-candidaturas` | 1.0.0 | 6 | `9df64e0b1fa119a33bc7da0a68496752520db1db690cdcef231fa6547494dc0b` |

Mantem-se: o analista nao exporta, o exportador nao aprova/rejeita documentos e nenhum destes templates inclui `reports.export_sensitive`.

## 7. Template novo

- Chave: `analista-candidaturas-exportacao`.
- Label: `Analista de candidaturas e exportacao`.
- Versao: `1.0.0`.
- Scope: municipal.
- Permissions exatas: 36.
- Fingerprint: `4208acb7b770b5095d63f377a2a974cf457ddcfe8bfda36fa74a089972df6c79`.
- Classe de segregacao: `program53_mutable`.
- A definicao global nao cria role, nao atribui utilizadores e nao ativa funcionalidades municipais.

## 8. Matriz de permissions

A matriz efetiva combina apenas as capacidades explicitamente requeridas:

- dashboard: `dashboard.view`;
- candidaturas: `applications.view`, `applications.update`, `applications.audit`, `applications.export`;
- documentos: `documents.view`, `documents.update`, `documents.replace`, `documents.download`, `documents.analyze`, `documents.review_ai`, `documents.approve`, `documents.reject`, `documents.audit`;
- elegibilidade: `eligibility.view`, `eligibility.run`;
- processos: `administrative_processes.view`, `create`, `update`, `assign`, `decide`, `complete`, `cancel`, `issue`, `mark_overdue`, `publish`, `audit`;
- decisoes: `administrative_decisions.view`, `administrative_decisions.create`;
- tarefas: `work_tasks.view`, `claim`, `update_status`, `complete`;
- reporting: `reports.view`, `reports.export`, `reports.audit`.

A matriz completa e a comparacao por perfil estao em `docs/access/program-53-permission-matrix.md`.

## 9. Permissions efetivas

- Nao foi criado qualquer wildcard.
- `reports.export_sensitive` esta explicitamente excluida.
- Nao foram adicionadas permissions de administracao de roles, utilizadores, equipas ou operadores.
- Nao foram adicionadas permissions de financas, pagamentos, contratos, RGPD, scoring, listas, sorteios, manutencao ou vistorias.
- A aplicacao do template resolve todos os IDs a partir do catalogo e falha se alguma permission obrigatoria nao existir.

## 10. Divergencias de nomenclatura

Nao foram criados aliases `application_review_batches.*` ou `correction_cycles.*`, porque nao existem consumidores reais com esses nomes. Foram reutilizadas as permissions efetivas `administrative_processes.*`, `applications.*`, `documents.*` e `reports.*`. Isto evita permissions orfas e mantem a fronteira alinhada com as rotas existentes.

## 11. Dependencias tecnicas

- Revisao, aperfeicoamentos, lotes e publicacao: entitlement `applications.review`.
- Exportacao temporal e download: entitlement `applications.export`.
- Exportacao: worker da queue `reports`, scheduler e storage privado no ambiente alvo.
- MFA: middleware existente e sensibilidade definida no catalogo de permissions.
- Rate limiting: cache Laravel operacional e configuracao valida em `config/mvhab.php`.

## 12. Segregacao de funcoes

A combinacao entre analise, decisao documental, lotes e exportacao normal e deliberada para este perfil. Permanecem fail-closed:

- candidate com role municipal interna;
- auditor com template mutavel do Programa 53, em ambas as ordens de atribuicao;
- conta inativa;
- role inativa;
- role de outro Municipio;
- ator sem todas as permissions da role;
- self-promotion e atribuicao que exceda o scope do ator.

A matriz de conflitos esta documentada em `docs/access/program-53-segregation-of-duties.md`.

## 13. Exportacao sensivel

`reports.export_sensitive` nao integra o novo template nem qualquer template preservado. O grant sensivel continua separado, sujeito a Policy, entitlement, MFA, scope e perfil de rate limit mais restritivo. Conceder ou revogar esse grant nao altera o template base.

## 14. Entitlements

Permissions e entitlements permanecem independentes. O preview informa dependencias ausentes, mas o template pode apenas criar/reconciliar a role; nunca ativa `applications.review` ou `applications.export`. Os testes cobrem permission sem entitlement e entitlement sem permission, ambos sem efeitos laterais.

## 15. MFA

O preview calcula a necessidade de MFA a partir de permissions sensiveis do catalogo, nao do nome da role. Selagem, publicacao, exportacao e download continuam protegidos por `mfa.backoffice`. Ausencia de MFA e testada como recusa, sem criacao de export, job, selagem ou publicacao.

## 16. Rate limiting

Foram registados seis limiters nomeados:

- `program53.export-preview`;
- `program53.export-request`;
- `program53.export-download`;
- `program53.batch-seal`;
- `program53.batch-publish`;
- `program53.revalidation-seal`.

As chaves sao hashes SHA-256 de identificadores tecnicos versionados, distinguem utilizador e Municipio e nao incluem email, nome, NIF ou outro PII. Existem limites por utilizador e agregados por Municipio. Preview/download normal usam 20/100 por minuto; sensivel 5/20 por minuto. Pedido de export normal usa 5/25 por cinco minutos; sensivel 2/8. Selagem usa 5/20, publicacao 3/10 e selagem de revalidacao 5/20 por cinco minutos. Configuracao ausente ou invalida falha de forma explicita.

A resposta de bloqueio e HTTP 429 com `Retry-After`; a auditoria do bloqueio e minimizada e deduplicada por 60 segundos. A indisponibilidade da cache de auditoria nao converte a recusa segura em erro 500.

## 17. Policies

As rotas do manifesto mantem Policy e ability publicas verificadas pelo comando de auditoria. O template nao substitui Policies nem autoriza pelo nome da role. Os testes de matriz independente cobrem a ausencia de Policy como fronteira negativa.

## 18. Scope municipal

- A role exige `municipality_id` autoritativo.
- A unicidade e por `municipality_id + template_key`.
- Preview, criacao, reconciliacao e atribuicao usam o Municipio autenticado.
- Recursos sem origem municipal e recursos de outro Municipio sao recusados.
- A matriz independente cobre o caso de scope ausente mesmo quando permission, entitlement e Policy existem.

## 19. Operadores de plataforma

O template municipal nao cria `PlatformOperatorAssignment`, nao confere scope global e nao usa a role como substituto dessa atribuicao. O cenário demo verifica explicitamente `global_scope = false`. Operador global continua a exigir assignment independente e explicito.

## 20. Role lifecycle

O fluxo administrativo existente foi reforcado com:

- preview read-only;
- criacao idempotente por Municipio e template;
- confirmacao explicita da matriz;
- identificador tecnico reservado e estavel;
- reconciliacao transacional;
- protecao de role e conta inativas;
- auditoria de preview, criacao, drift e reconciliacao.

Roles legacy continuam validas e nao sao associadas retroativamente por nome ou label.

## 21. Versionamento e fingerprint

Cada role baseada em template guarda `template_key`, `template_version` e `template_fingerprint`. O fingerprint e calculado sobre a versao e a lista ordenada de permissions, garantindo determinismo. Label e descricao sao tratados separadamente como drift de apresentacao.

## 22. Drift

O preview apresenta permissions a adicionar, manter e remover, bem como drift de metadata e apresentacao. Se existir divergencia, a aplicacao sem `confirm_reconcile` e bloqueada e auditada. A reconciliacao exige confirmacao administrativa explicita e sincroniza apenas dentro de uma transacao; nao existe remocao silenciosa.

## 23. Migrations

Foi criada `database/migrations/2026_08_01_000055_add_template_metadata_to_roles_table.php`:

- adiciona os tres campos nullable;
- cria unique `roles_municipality_template_unique`;
- preserva o indice municipal autonomo `roles_municipality_lookup_index`, necessario para a foreign key em MySQL;
- nao efetua backfill ambiguo;
- recusa rollback se existir metadata de template persistida.

Resultados observados: SQLite up/down/up passou; MySQL up/down/up passou, incluindo foreign key e indices. A base temporaria MySQL foi removida apos os testes.

## 24. Administracao existente reutilizada

Foram reutilizados `MunicipalRoleTemplateController`, `RoleManagementController`, `StoreRoleRequest`, `RoleManagementService`, `RoleAssignmentService`, `RolePolicy` e as views atuais de templates/roles. Nao foi criada administracao paralela nem rota administrativa nova.

## 25. UX

O backoffice apresenta:

- label, versao, fingerprint, capacidades e exclusoes do template;
- dependencias de entitlement e respetivo estado;
- preview das diferencas;
- aviso separado para exportacao sensivel;
- confirmacao explicita da aplicacao e da reconciliacao;
- identificacao de roles municipais baseadas em template.

O fallback vazio dos lotes de revisao foi corrigido para HTML local porque `x-mv.empty-state` nao existe no conjunto real de componentes.

## 26. Acessibilidade

Foram preservados headings, labels, controlos de formulario, foco do Design System MV, mensagens de erro e confirmacoes por checkbox. O estado vazio de lotes usa `role="status"`. A suite UX passou; nao foi executada auditoria visual manual com leitor de ecra nesta sprint.

## 27. Auditoria

Foram mantidos/registados eventos para preview, criacao, deteccao de drift, reconciliacao, atribuicao e rate limit excedido. Os payloads usam IDs tecnicos, Municipio, template, versao, fingerprint, contagens e codigos de permission; nao incluem emails, nomes de candidatos, documentos, paths, tokens, codigos MFA ou passwords.

O comando `access:audit-program-53` e read-only, deterministico e suporta `table`, `json`, `markdown`, `--output` e `--fail-on-drift`.

## 28. RGPD

- Nenhum novo dado pessoal e exposto na UI ou no manifesto.
- As chaves de rate limit nao contêm PII.
- A auditoria e minimizada.
- O perfil nao inclui RGPD, privacy, financas ou exportacao sensivel.
- O dossier demo inclui apenas os documentos obrigatorios do cenário e permanece em storage/testes controlados.

## 29. Seeder

O cenário municipal demo passou a incluir:

- role `demo_alcanena_analista_candidaturas_exportacao` com metadata e matriz exatas;
- utilizador `analista.exportacao.demo@mvhab.local` ativo e com MFA obrigatorio;
- cinco roles municipais e seis utilizadores demo no total;
- apenas entitlements `applications.intake`, `applications.review` e `applications.export`;
- nenhuma atribuicao de operador global;
- referencias habitacionais `ALC-DEMO-APP-*`, isoladas de outros cenários demo.

O catalogo demo cria overrides de concurso nao obrigatorios para requisitos globais herdados, sem alterar o catalogo global. `DocumentDossierBuilder` ganhou a opcao retrocompativel `required_only`; o comportamento por defeito nao mudou e o demo usa-a explicitamente.

## 30. Verify-only

O comando demo foi validado num SQLite limpo com catalogo documental global instalado. O resultado observado inclui Municipio `ALCANENA-DEMO`, cinco roles municipais, seis contas, 15 submissoes documentais, 16 versoes, 15 itens de dossier, MFA ativo no novo perfil e ausencia de scope global. O modo `--verify-only` preserva o estado e a segunda execucao produz o mesmo resumo, excluindo o timestamp de verificacao.

## 31. Concorrencia

Foi executado teste real com dois processos e ligacoes independentes em MySQL/MariaDB. Resultado: 1 teste, 10 assercoes, exatamente uma role municipal, pivots sem duplicacoes e fingerprint final unico. A base temporaria foi removida. A transacao bloqueia o Municipio antes de resolver/criar a role.

## 32. Testes

Resultados observados:

- suite dirigida Programa 53: 61 testes, 619 assercoes;
- suite integral: 1.610 testes, 23.503 assercoes, zero falhas;
- suite UX canonica: 135 testes, 664 assercoes;
- fixtures regulamentares deterministicas: 13 testes, 55 assercoes;
- catalogo demo: 8 testes, 190 assercoes;
- comando demo, visitas/export e dossier repetivel: 16 testes, 320 assercoes;
- acesso e comando do seeder: 11 testes, 198 assercoes;
- concorrencia MySQL: 1 teste, 10 assercoes.

Durante o gate integral foram detetados tres testes regulamentares dependentes de `now()` face a uma data de snapshot fixa. Apenas os fixtures foram corrigidos com janelas explicitas; o comportamento regulamentar fail-closed de producao nao foi alterado.

## 33. Gates

Passaram:

- `composer quality:tests:integrity -- ef91154c3333ffb78565448671c79862c445609a`;
- `composer quality:pint:changed -- ef91154c3333ffb78565448671c79862c445609a`;
- `composer validate --strict`;
- `composer audit --locked`, sem advisories;
- `composer check-platform-reqs`;
- PHPStan integral, zero erros;
- Pint integral;
- `php artisan optimize:clear`;
- PHPUnit integral e UX;
- SQLite e MySQL up/down/up;
- concorrencia MySQL;
- `npm run build` com Vite 8.0.16;
- `git diff --check`.

O verificador de integridade emitiu apenas dois avisos sobre fixtures com wildcard. Ambos sao intencionais: os testes constroem matrizes invalidas para provar a respetiva rejeicao.

## 34. Auditoria de rotas

Resultados observados:

- 1.199 rotas sem vendor;
- 1.202 rotas totais auditadas;
- 1.196 rotas nomeadas;
- 937 rotas com permission middleware;
- 0 rotas backoffice com role fixa;
- 216 rotas candidate com role fixa herdada;
- 0 nomes duplicados;
- 0 rotas com guards obrigatorios em falta no auditor global.

A sprint nao alterou a contagem de rotas nem criou uma administracao paralela.

## 35. Manifesto

`docs/access/manifests/sprint-53h-program-53-access-manifest.json` contem 45 rotas ordenadas e unicas: 21 de leitura e 24 mutaveis. Cada entrada declara bounded context, operacao, permission, entitlement, Policy/ability, scope, MFA, limiter quando aplicavel, sensibilidade, evento e templates permitidos.

`php artisan access:audit-program-53 --format=json --fail-on-drift` observou 474 verificacoes aprovadas, zero falhas e `drift=false`.

## 36. Ficheiros alterados

Foram abrangidos 45 ficheiros, agrupados assim:

- acesso e HTTP: `MunicipalRoleTemplateController`, `RoleManagementController`, `StoreRoleRequest`, `Role`, `MunicipalRoleTemplateRegistry`, `RoleManagementService`, `RoleAssignmentService`;
- seguranca e auditoria: `AppServiceProvider`, `Program53RateLimitService`, `Program53RateLimitAuditService`, `AuditProgram53Access`, `Program53AccessAuditService`, `config/mvhab.php`, `routes/web.php`;
- persistencia/demo: migration 000055, tres seeders demo, `MunicipalApplicationDemoSummaryService`, `DocumentDossierBuilder`;
- UI: quatro views de acessos e a listagem de lotes de revisao;
- documentacao: este relatorio, matriz, segregacao e manifesto;
- testes: matriz, workflow, templates, rate limiting, segregacao, comando de auditoria, demo, concorrencia, fixtures regulamentares e respetivo worker.

Nao foram alteradas regras de candidatura, elegibilidade, scoring, listas, contratos ou workflows administrativos.

## 37. Estado Git

No inicio, a working tree estava limpa e a branch apontava para o commit-base exigido. As alteracoes foram divididas nos quatro commits previstos. O SHA do commit final, o push, a igualdade local/remoto e a working tree limpa sao confirmados pela resposta de fecho depois de executar estes passos; nao sao antecipados neste documento.

## 38. Riscos residuais

- O comportamento da queue `reports`, scheduler e storage depende da configuracao do ambiente alvo.
- O rate limiting depende de um cache partilhado e consistente entre instancias.
- A migration recusa rollback depois de roles baseadas em template existirem; a remocao exige procedimento administrativo previo.
- Nao foi feito teste de carga transversal, chaos testing ou validacao manual de acessibilidade.
- O cenário demo e ficticio e nao deve ser executado como dado administrativo real.

## 39. Deployment gates

Antes de deploy devem ser validados no ambiente alvo:

1. backup e janela para migration;
2. migration e indices em MySQL/MariaDB real da instancia;
3. cache partilhado dos rate limiters;
4. worker da queue `reports` e scheduler;
5. storage privado, permissao de escrita, leitura e limpeza;
6. geracao, processamento e download real de export normal;
7. MFA e entitlements por Municipio;
8. monitorizacao inicial de HTTP 429 e falhas de export;
9. rollback operacional documentado.

## 40. Exclusoes

Ficaram fora da sprint: deploy, monitorizacao de producao, arquivo municipal externo, assinatura digital, selo temporal externo, envio de ZIP por email, URLs publicas para exports, 10.000/50.000 candidaturas, chaos testing global e load testing transversal.

## 41. Preparacao da Sprint 53I

Ficam disponiveis para a 53I:

- perfil operacional final e matriz fechada;
- templates anteriores com fingerprints de referencia;
- manifesto de 45 rotas;
- comando de auditoria deterministico;
- MFA por risco e rate limiting por utilizador/Municipio;
- role lifecycle com preview, versionamento, drift e reconciliacao;
- cenário demo e verify-only;
- testes end-to-end, segregacao e concorrencia;
- baseline de 474 checks e metricas para observabilidade.

## Decisao final

`REPOSITORY_PASS_DEPLOYMENT_GATED`
