# Auditoria Exaustiva MV HAB v2.0

Data: 18/06/2026.

Âmbito: auditoria técnica, funcional, arquitetural, segurança, RGPD, performance, testes, PHPStan e comparação com requisitos da plataforma e benchmark HABITAR Lisboa.

## Resumo Executivo

Classificação final: **Necessita Ajustes Relevantes**.

A plataforma apresenta uma evolução funcional muito ampla e coerente com o roadmap: portal público, área candidata, adesão, agregado, rendimentos, situação habitacional, documentos, candidatura, elegibilidade, workflow administrativo, classificação, listas, reclamações, atribuição, contratos, renda, pagamentos, manutenção, notificações, relatórios, RGPD e auditoria estão materializados em código, rotas, migrations, policies, services e testes.

Os testes funcionais passam integralmente, o build frontend passa, as migrations não têm pendências e a estrutura de autorização/documentos privados está bem coberta por testes. Porém, **não está pronta para produção** porque:

- `vendor/bin/pint --test` falha em 3 controllers.
- `vendor/bin/phpstan analyse` falha com **2453 erros em 450 ficheiros**.
- Existem erros PHPStan que podem representar bugs reais, sobretudo comparações enum/string sempre falsas/verdadeiras, `method.notFound`, `match.unhandled`, `arguments.count` e null-safety.
- O scheduler não tem tarefas definidas.
- A validação legal/RGPD/DPO, pentest, backups/restore, monitorização e evidência operacional continuam pendentes.

Estado recomendado: **apta para testes locais/beta controlado depois de corrigir Pint**, mas **não avançar para produção** até a dívida PHPStan crítica, segurança operacional e validação jurídica/RGPD estarem resolvidas.

## Ambiente e Inventário

| Item | Resultado |
| --- | --- |
| Laravel | 13.12.0 |
| PHP CLI | 8.5.6 |
| Composer PHP declarado | `^8.3` |
| Autenticação | Laravel Breeze/session, email verification, password reset |
| Frontend | Blade, Tailwind, Alpine, Vite |
| Rotas | 830 |
| Models | 178 |
| Controllers | 185 |
| Form Requests | 259 |
| Services | 205 |
| Policies | 156 |
| Jobs | 4 |
| Console commands próprios | 0 |
| Scheduled tasks | 0 |
| Migrations | 33 |
| Seeders | 37 |
| Factories | 158 |
| Views | 462 |
| Tests | 37 ficheiros, 174 testes |

Nota: a prompt assumia Laravel 13.8 + PHP 8.4. A execução local auditada está em Laravel 13.12.0 + PHP 8.5.6.

## Inventário Funcional

| Domínio | Estado | Evidência |
| --- | --- | --- |
| Portal público | Bom | Rotas `public.*`, controllers públicos e views para portal, programas, concursos, FAQ e resultados. |
| Registo de adesão | Bom | Área candidata, `AdhesionRegistration`, progress service e testes Sprint 4/5/19. |
| Agregado/rendimentos/habitação | Bom | Models, services, requests e testes específicos. |
| Simulador/elegibilidade | Parcial/Bom | Existe pré-verificação e motor de elegibilidade versionado; decisão externa/automática final continua fora de âmbito. |
| Candidaturas | Bom | Wizard, validação documental, snapshots, declarações, submissão, desistência e comprovativo. |
| Documentos | Bom | Storage privado, versões, revisão, logs e teste de bloqueio cross-candidate. |
| Workflow administrativo | Bom | Processos, aperfeiçoamento, decisões e timeline. |
| Classificação/ranking | Bom com risco estático | Pontuação e rankings internos implementados; PHPStan aponta comparações enum/string críticas. |
| Listas/reclamações/audiência | Bom com validação legal pendente | Publicação anonimizada, reclamações e audiências implementadas. |
| Atribuição/sorteio | Bom com risco estático | Atribuições, preferências, sorteio auditável e suplentes implementados; PHPStan aponta scopes/métodos não inferidos e erros de `refresh()`. |
| Contratos/renda/caução | Bom com risco estático | Cálculo, minutas, contrato, caução e documento HTML privado implementados. |
| Pagamentos/incumprimentos/revisão | Aceitável/Bom | Registo manual/import CSV interno, planos, avisos, acordos e revisões. Sem gateway/ERP, conforme fora de âmbito. |
| Manutenção/vistorias | Bom | Pedidos, anexos privados, vistorias, autos e histórico técnico. |
| Notificações/comunicações | Aceitável/Bom | In-app e registo interno implementados; email/SMS real fora de âmbito. |
| Relatórios/dashboards | Aceitável/Bom | Dashboards e exportações privadas com masking e logs; performance de relatórios ainda requer volume real. |
| RGPD/auditoria | Bom com validação DPO pendente | Audit trail avançado, logs sensíveis, consentimentos, pedidos de titular, export JSON, retenção e anonimização controlada. |
| Scheduler | Fraco | `php artisan schedule:list` indica ausência total de tarefas agendadas. |

## Estado Arquitetural

| Critério | Classificação | Observações |
| --- | --- | --- |
| Separação de responsabilidades | Bom | Controllers usam services, requests e policies de forma ampla. |
| SOLID | Aceitável/Bom | Domínios estão separados, mas há services grandes e dependências diretas de Eloquent sem interfaces. |
| DRY | Aceitável | Há padrões repetidos em policies/controllers; aceitável para ritmo incremental, mas há espaço para abstrações. |
| Services | Bom | 205 services distribuídos por domínio. |
| Form Requests | Bom | 259 requests, validação deslocada dos controllers. |
| Policies/autorização | Bom | 156 policies, `Gate::authorize` e middleware `role`. |
| Events/listeners/jobs | Aceitável | Há 4 jobs e listener crítico; uso ainda limitado. |
| Transactions | Aceitável | Presentes em fluxos críticos, mas não auditado linha a linha em todos os services. |
| Cache | Fraco/Aceitável | Uso funcional limitado; dashboards/relatórios beneficiariam de cache em staging. |
| Eager loading | Aceitável | Muitos controllers usam `with`; risco residual documentado para relatórios/listagens. |
| Índices | Aceitável/Bom | Migrations têm índices relevantes; falta validação com EXPLAIN em volume real. |
| Auditoria | Bom | `audit_logs` legado e `audit_events` avançado coexistem. |
| RGPD | Bom | Estrutura forte, validação jurídica ainda obrigatória. |
| Logging/monitorização | Aceitável | Logs internos existem; SIEM/observabilidade externa pendente. |
| Tratamento de exceções | Aceitável | Fluxos usam aborts/runtime exceptions; falta política central de erros operacionais. |
| Segurança | Bom com pendências | Bom isolamento e storage privado; pentest, backups e hardening infra pendentes. |

## Comparação com Auditoria Anterior

Relatórios anteriores encontrados: `docs/qa/sprint-19-quality-report.md`, `docs/qa/quality-gates.md` e `docs/qa/phpstan-remediation-report.md`. Não foi encontrado relatório standalone de auditoria anterior além destes artefactos QA.

| Conclusão anterior | Estado atual | Observação |
| --- | --- | --- |
| `php artisan test` verde com 174 testes/1164 asserções | Resolvido/continua válido | Suite completa passou novamente. |
| `npm run build` verde | Resolvido/continua válido | Build Vite passou. |
| `route:list` com 830 rotas | Resolvido/continua válido | 830 rotas confirmadas. |
| Pint passava após formatação | Regressão | `pint --test` falha em 3 controllers. |
| PHPStan 20A: 2827 erros | Parcialmente resolvido | Reduziu para 2453, mas continua a falhar. |
| `User` passou a implementar `MustVerifyEmail` | Continua presente/parcial | O código atual mantém `use Illuminate\Contracts\Auth\MustVerifyEmail` comentado e a classe não implementa o contrato. Runtime passa porque o evento usa docblock, mas PHPStan acusa. |
| `LotteryService::lock` como `method.notFound` | Resolvido ou substituído | Esse erro específico não aparece na amostra completa atual, mas existem 29 `method.notFound` noutros pontos. |
| Sem CI | Continua presente | Não foi identificada configuração CI. |
| Validação RGPD/DPO e pentest pendentes | Continua presente | Mantém bloqueio para produção. |

## Comparação com Requisitos

Baseline: `/Users/brunocorreia/Documents/CRM HAB/Requisitos plataforma.pdf`, extraído para `storage/audit/requisitos-plataforma.txt`.

| EXPERIÊNCIA | PONTO | DETALHE | IMPLEMENTAÇÃO MV HAB | CUMPRE |
| --- | --- | --- | --- | --- |
| Candidato | Conhecer oferta | Site público, concursos abertos/futuros | Portal, programas e concursos publicados existem | ✅ Sim |
| Candidato | Conhecer oferta | Mapa dinâmico/fogos com filtros por freguesia, tipologia, rendas | Habitações e filtros básicos existem no domínio, mas mapa dinâmico público não foi evidenciado | ⚠️ Parcial |
| Candidato | Conhecer oferta | Brochura digital por empreendimento | Não evidenciado como módulo próprio | ❌ Não |
| Candidato | Registo | Registo de adesão como gate de candidatura | Implementado | ✅ Sim |
| Candidato | Registo | Simulador/elegibilidade preliminar | Motor e pré-verificação existem | ✅ Sim |
| Candidato | Candidatura | Formulário, documentos, escolha de habitação | Candidatura e documentos existem; preferências/habitação existem após Sprint 12 | ✅ Sim |
| Candidato | Candidatura | Agendamento/reagendamento de visitas | Não evidenciado como fluxo próprio de visitas pré-atribuição | ❌ Não |
| Candidato | Candidatura | Linha de apoio/suporte/ticket | FAQ e comunicação existem; ticket/support line não evidenciado | ⚠️ Parcial |
| Candidato | Acompanhamento | Estados, desistência, audiência, reclamação/recurso | Implementado | ✅ Sim |
| Candidato | Acompanhamento | Notificações automáticas e FAQ | In-app interno e FAQ existem; email/SMS real fora de âmbito | ⚠️ Parcial |
| Candidato | Fecho concurso | Contrato digital e transição para área inquilino | Contrato processual/HTML e área financeira/manutenção existem; assinatura digital externa fora de âmbito | ⚠️ Parcial |
| Candidato | Área inquilino | Contratos, rendas, pagamentos, manutenção, vistorias | Implementado em área candidata/tenant | ✅ Sim |
| Câmara | Início procedimento | Upload edital, abertura concursos, modelos | Programas/concursos/modelos existem; upload edital dedicado não evidenciado | ⚠️ Parcial |
| Câmara | Análise | Dashboard, pré-validação, scoring, gestão documental | Implementado | ✅ Sim |
| Câmara | Audiência prévia | Listas, respostas, minutas/atas | Listas, reclamações, audiências e documentos oficiais existem | ✅ Sim |
| Câmara | Sorteios/ordenação | Sorteio, notificações, ranking pós-sorteio | Sorteio auditável e ranking/alocação implementados | ✅ Sim |
| Câmara | Relatório final | Relatórios, contrato, assinatura | Relatórios e contrato existem; assinatura digital externa fora de âmbito | ⚠️ Parcial |
| Câmara | Área senhorio | Dashboard manutenção/pagamentos, vistorias, comunicações | Implementado parcialmente por backoffice | ✅ Sim |
| Operação | Scheduler | Caducidades, alertas, limpezas, retenção | Sem scheduled tasks definidas | ❌ Não |
| Operação | Produção | Backups, restore, pentest, monitorização | Documentado/checklist, sem evidência operacional real | ⚠️ Parcial |

## Comparação com HABITAR Lisboa

Referências extraídas: `manual_utilizador_plataforma.pdf` e `FAQs_plataforma.pdf` para `storage/audit/manual-utilizador-plataforma.txt` e `storage/audit/faqs-plataforma.txt`.

| Área HABITAR Lisboa | MV HAB | Cumpre |
| --- | --- | --- |
| Área pública com requisitos, resultados e concursos | Portal, FAQ, programas, concursos e resultados existem | ✅ Sim |
| Autenticação.gov/CMD | Fora de âmbito por instrução, não contado como falha | N/A |
| Registo de adesão | Implementado com área candidata e progresso | ✅ Sim |
| Simuladores | Pré-verificação/elegibilidade implementada | ✅ Sim |
| Candidatura por programa | Implementada | ✅ Sim |
| Submissão documental após notificação | Documentos e aperfeiçoamento existem; notificações externas não | ⚠️ Parcial |
| Aperfeiçoamento | Implementado | ✅ Sim |
| Audiência de interessados | Implementada | ✅ Sim |
| Afetação/atribuição/sorteio | Implementado com ranking/preferências/sorteio | ✅ Sim |
| Desistência | Implementada em candidaturas/atribuições | ✅ Sim |
| Tipologia documental por agregado | Checklist documental dinâmica existe | ✅ Sim |
| Renovação anual do registo | Documentada como pendência/controlo futuro; scheduler ausente | ⚠️ Parcial |
| Contactos/apoio ao cidadão | FAQ existe; linha/ticket operacional não evidenciado | ⚠️ Parcial |

## Segurança

Pontos fortes:

- Backoffice protegido por `auth` e roles.
- Candidato tem prefixo próprio `area-candidato` e middleware `role:candidate`.
- Rotas sensíveis de segurança exigem MFA de backoffice.
- Documentos usam storage privado `local` e downloads por controller/policy.
- Teste `DocumentSecurityFlowTest` valida bloqueio cross-candidate, MIME/size e ausência de `/storage` público.
- `AuditEventFormatter` mascara chaves sensíveis como password, token, NIF, IBAN, document_number e paths.
- Exportações RGPD e relatórios ficam em storage privado com logs.

Riscos:

- PHPStan aponta várias comparações enum/string que podem alterar decisões de estado se o cast real divergir do esperado.
- `pint --test` falha, indicando regressão de qualidade antes de merge/release.
- MFA está aplicado a rotas sensíveis de segurança, mas não a todo o backoffice por defeito.
- `PasswordPolicyService` é recomendação operacional, não enforcement global de passwords existentes.
- Sem pentest externo, sem evidência de headers/security hardening de infraestrutura, sem SIEM central.
- Retenção/anonimização existem, mas execução destrutiva depende de validação DPO e política aprovada.

## Performance

Pontos positivos:

- Listagens usam paginação em larga escala.
- Muitos controllers usam eager loading.
- Relatórios têm limites em algumas queries.
- Existe revisão documental de performance em `docs/qa/performance-query-review.md`.

Riscos:

- Sem testes de carga reais.
- Sem EXPLAIN/planos de execução em volume municipal.
- Exportações e relatórios sensíveis podem exigir queue/cache/materialização em produção.
- Scheduler vazio impede caducidades, retenção, alertas e limpezas automáticas.
- Cache aplicacional ainda é pouco usado para dashboards/indicadores.

## Qualidade do Código

Pontos fortes:

- Boa modularização por domínio.
- Forte uso de Form Requests, Policies e Services.
- Factories/seeders amplos com dados fictícios.
- Não foram encontrados `TODO`, `FIXME`, `HACK`, `dd()`, `dump()` ou `ray()` com regex corrigida.

Riscos:

- PHPStan indica 1039 erros `missingType.generics` e 337 `missingType.iterableValue`.
- 235 `argument.type`, 210 `property.notFound`, 116 `property.nonObject`.
- 33 `identical.alwaysFalse`, 35 `notIdentical.alwaysTrue`, 29 `method.notFound`.
- Há acoplamento direto a Eloquent e ausência de interfaces/domain contracts na maioria dos services.
- Há ficheiros com muitas relações e propriedades dinâmicas sem PHPDoc/generics suficientes.

## Testes

Resultado atual:

- `php artisan test`: passou.
- 174 testes, 1164 asserções.
- Cobertura funcional cobre autenticação, permissões, documentos, fluxo integrado, elegibilidade, scoring, contratos, renda, manutenção, reporting e RGPD.

Lacunas:

- Não existe CI identificado.
- Testes de carga são smoke, não prova de capacidade.
- Não há teste operacional de scheduler porque não há tarefas agendadas.
- A suite passa apesar de PHPStan apontar possíveis bugs de estado, o que indica necessidade de testes adicionais orientados a enums/casts e transitions.

## PHPStan

Comando: `vendor/bin/phpstan analyse -v --memory-limit=1G --error-format=json`.

Resultado: **falhou**.

- Erros: 2453.
- Ficheiros afetados: 450.
- Versões: Larastan 3.10.0, PHPStan 2.2.2.

Principais categorias:

| Identificador | Contagem |
| --- | ---: |
| `missingType.generics` | 1039 |
| `missingType.iterableValue` | 337 |
| `argument.type` | 235 |
| `property.notFound` | 210 |
| `property.nonObject` | 116 |
| `method.nonObject` | 69 |
| `return.type` | 52 |
| `nullsafe.neverNull` | 52 |
| `deadCode.unreachable` | 46 |
| `notIdentical.alwaysTrue` | 35 |
| `identical.alwaysFalse` | 33 |
| `method.notFound` | 29 |

Top ficheiros afetados:

- `app/Models/Application.php`: 54.
- `app/Models/Contract.php`: 48.
- `app/Models/User.php`: 46.
- `app/Services/Scoring/ScoringDataProvider.php`: 38.
- `app/Models/Contest.php`: 37.
- `app/Services/Documents/DocumentChecklistService.php`: 33.
- `app/Services/Documents/DocumentUploadService.php`: 33.
- `app/Models/Program.php`: 30.
- `app/Models/AdhesionRegistration.php`: 28.
- `app/Services/Administrative/AdministrativeTimelineService.php`: 28.

Prioridade de correção:

1. Corrigir `identical.alwaysFalse`, `notIdentical.alwaysTrue`, `function.impossibleType`, `match.unhandled`, `method.notFound`, `arguments.count`.
2. Corrigir `argument.type`, `property.nonObject`, `method.nonObject` em controllers/services críticos.
3. Tipar relações Eloquent com PHPDoc/generics e casts consistentes.
4. Completar `missingType.iterableValue` e `missingType.generics`.
5. Só depois elevar novamente o rigor para release/staging.

## Comandos Executados

| Comando | Resultado |
| --- | --- |
| `composer validate` | Passou, `composer.json` válido. |
| `composer install` | Passou, nada a instalar/atualizar/remover; autoload otimizado. |
| `php artisan optimize:clear` | Passou. |
| `php artisan migrate --pretend` | Passou, `Nothing to migrate`. |
| `php artisan test` | Passou, 174 testes/1164 asserções. |
| `vendor/bin/pint --test` | Falhou em 3 controllers. |
| `vendor/bin/phpstan analyse` | Falhou com 2453 erros. |
| `npm run build` | Passou. |
| `php artisan route:list --json` | Passou, 830 rotas. |
| `php artisan schedule:list` | Passou, mas sem tarefas definidas. |
| `php -v` | PHP 8.5.6. |
| `php artisan --version` | Laravel 13.12.0. |

## Ficheiros Pint com Falha

- `app/Http/Controllers/Candidate/IncomeRecordController.php`.
- `app/Http/Controllers/Candidate/ComplaintController.php`.
- `app/Http/Controllers/Candidate/CorrectionResponseController.php`.

## Conclusão

A MV HAB está funcionalmente muito avançada e tem uma base de testes sólida para beta/local/staging controlado. No entanto, a classificação correta para produção é **Necessita Ajustes Relevantes**.

Recomendação objetiva:

1. Corrigir Pint imediatamente.
2. Executar sprint curta de PHPStan focada em erros com impacto comportamental real.
3. Criar scheduler operacional para retenção, caducidades, alertas e limpezas.
4. Validar RGPD/DPO, legal, infraestrutura, backups/restore, logs, monitorização e pentest.
5. Só depois reavaliar como candidata a produção.

As ausências de Autenticação.gov, CMD, AT, Segurança Social, IRN, OCR, gateways bancários, SMS, assinatura digital qualificada, ERP municipal e webservices externos **não foram consideradas falhas**, conforme instrução. Foram consideradas apenas como pontos de extensão futura.
