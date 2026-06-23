# Relatório PHPSTAN-04 — Domain Logic Validation & Hidden Bug Discovery

Data de execução: 2026-06-23  
Objetivo: validar erros PHPStan com maior probabilidade de bug real, corrigindo apenas casos provados e cobertos por teste dirigido.

## 1. Resumo Executivo

O sprint foi executado de forma conservadora. Foram analisados os grupos de maior risco: constantes inexistentes, downloads com path nulo, null-safety em Document Intelligence, enum/string mismatch, scopes Eloquent e casts de datas.

Foram corrigidos 3 bugs reais isolados:

1. `DocumentDossierFactory` usava `DocumentDossierStatus::Generated`, constante inexistente.
2. Downloads de recibos financeiros podiam chamar `Storage::download()` com `storage_path` nulo.
3. Painel de validação IA podia falhar ao ordenar validações com `severity` nula.

Não foram alteradas regras de elegibilidade, pontuação, classificação, listas, atribuição, contratos, rendas, policies, RGPD ou permissões.

## 2. PHPStan Antes/Depois

| Momento | Total de erros | Ficheiros afetados |
| --- | ---: | ---: |
| Antes | 2755 | 493 |
| Depois | 2751 | 489 |

Redução líquida: 4 erros.  
Novos erros introduzidos: 0.

## 3. Distribuição Final por Identificador

| Identificador | Final |
| --- | ---: |
| `missingType.generics` | 1093 |
| `missingType.iterableValue` | 314 |
| `property.notFound` | 284 |
| `argument.type` | 247 |
| `property.nonObject` | 122 |
| `method.nonObject` | 93 |
| `nullsafe.neverNull` | 80 |
| `return.type` | 64 |
| `deadCode.unreachable` | 51 |
| `notIdentical.alwaysTrue` | 46 |
| `match.alwaysFalse` | 42 |
| `function.impossibleType` | 38 |
| `identical.alwaysFalse` | 37 |
| `method.notFound` | 34 |

## 4. Distribuição Final por Domínio

| Domínio | Erros |
| --- | ---: |
| Models | 1082 |
| Services — Outros | 780 |
| Contracts/Finance | 177 |
| Reporting | 133 |
| Scoring | 104 |
| Allocation | 94 |
| Documents | 85 |
| Eligibility | 62 |
| Policies | 51 |
| Document Intelligence | 51 |
| Controllers | 43 |
| RGPD | 34 |
| Seeders | 25 |
| Security | 19 |
| Factories | 10 |
| Config | 1 |

## 5. Erros Analisados

| Ficheiro | Linha | Identificador | Domínio | Severidade | Classificação | Decisão |
| --- | ---: | --- | --- | --- | --- | --- |
| `database/factories/DocumentDossierFactory.php` | 21 | `classConstant.notFound` | Documentos | P2 | BR | Corrigido |
| `app/Http/Controllers/Backoffice/Finance/PaymentReceiptController.php` | 48 | `argument.type` | Finance/Rents | P1 | BR | Corrigido |
| `app/Http/Controllers/Candidate/Finance/PaymentReceiptController.php` | 34 | `argument.type` | Finance/Rents | P1 | BR | Corrigido |
| `app/Http/Controllers/Backoffice/DocumentAiValidationController.php` | 61 | `property.nonObject` | Document Intelligence | P2 | BR | Corrigido |
| `app/Services/DocumentStandardization/DocumentStandardizationService.php` | 42-45 | `match.alwaysFalse` | Documents | P1 | FP/DT | Adiado |
| `app/Models/Application.php` | 418/430 | `method.notFound` | Allocation | P1 | FP/DT | Adiado |
| `app/Models/HousingUnit.php` | 121 | `method.notFound` | Public Portal | P2 | FP | Adiado |
| `app/Models/CorrectionRequest.php` | 91 | `method.nonObject` | Administrative Workflow | P1 | FP/DT | Adiado |
| `app/Models/ProvisionalList.php` | 122-124 | enum/date mismatch | Lists | P1 | FP/DT | Adiado |
| `app/Policies/*` | vários | enum/null relation | Security | P0/P1 | SR/RF | Adiado para PHPSTAN-05 |

## 6. Bugs Reais Confirmados

### BR-01 — Estado inexistente em factory documental

| Campo | Valor |
| --- | --- |
| Ficheiro | `database/factories/DocumentDossierFactory.php` |
| Identificador PHPStan | `classConstant.notFound` |
| Causa raiz | Factory apontava para `DocumentDossierStatus::Generated`, que não existe no enum. |
| Impacto funcional | Qualquer teste ou seeder que usasse a factory podia falhar em runtime. |
| Correção | Alterado para `DocumentDossierStatus::Standardized`, compatível com `standardized_at` preenchido pela factory. |
| Teste associado | `test_document_dossier_factory_uses_existing_status_enum` |

### BR-02 — Download de recibos financeiros sem path

| Campo | Valor |
| --- | --- |
| Ficheiros | `Backoffice/Finance/PaymentReceiptController.php`, `Candidate/Finance/PaymentReceiptController.php` |
| Identificador PHPStan | `argument.type` |
| Causa raiz | `storage_path` e `storage_disk` são nullable, mas eram passados diretamente para `Storage::download()`. |
| Impacto funcional | 500 em download autorizado de recibo incompleto/cancelado, em vez de 404 controlado. |
| Correção | Guarda explícita para disk/path nulos ou ficheiro inexistente antes do download. |
| Teste associado | `test_financial_manager_generates_schedule_registers_payment_allocates_and_issues_receipt` |

### BR-03 — Validação IA sem severidade

| Campo | Valor |
| --- | --- |
| Ficheiro | `app/Http/Controllers/Backoffice/DocumentAiValidationController.php` |
| Identificador PHPStan | `property.nonObject` |
| Causa raiz | Coluna `severity` é nullable, mas o painel usava `$validation->severity->value`. |
| Impacto funcional | Possível erro 500 ao consultar candidatura com validação IA sem severidade. |
| Correção | Ordenação passa a ler o atributo com guarda `instanceof DocumentAiValidationSeverity`. |
| Teste associado | `test_validation_panel_handles_records_without_severity` |

## 7. Falsos Positivos Prováveis

| Ficheiro | Erro | Evidência | Decisão |
| --- | --- | --- | --- |
| `app/Services/DocumentStandardization/DocumentStandardizationService.php` | `match.alwaysFalse` | `DocumentSubmission::casts()` define `status => DocumentStatus::class`; testes de documentos passam. | Adiar para sprint de tipagem/casts. |
| `app/Models/HousingUnit.php` | `publiclyVisible()` em `HasMany` | Scope existe em `HousingUnitPublicDocument`; chamada é proxy Eloquent comum. | Adiar para relations/generics. |
| `app/Models/Application.php` | `eligibleForAllocation()` / `readyForContract()` | Scopes existem em `DefinitiveListEntry` e `Allocation`; erro decorre de inferência em relações. | Adiar para relations/generics. |
| `app/Models/CorrectionRequest.php` | `response_deadline_at->isFuture()` | Campo está casted como `datetime`. | Adiar para PHPDoc/casts de Models. |
| `app/Models/ProvisionalList.php` | `lte/gte` e enum comparisons | Campos de prazo e status estão casted. | Adiar com testes dirigidos de listas. |

## 8. Estados Impossíveis ou Frágeis

| Ficheiro | Sinal | Risco |
| --- | --- | --- |
| `app/Models/AdhesionRegistration.php` | enum/string mismatch em estados de registo | Pode mascarar bloqueios/edição de registos se casts não forem aplicados. |
| `app/Models/Application.php` | enum/string mismatch em `isEditable()` | Pode afetar edição pós-submissão se o cast falhar. |
| `app/Services/Allocation/*` | chamadas a relações nullable e scopes não inferidos | Risco em atribuição/sorteio se relações obrigatórias estiverem ausentes. |
| `app/Policies/*` | relações inferidas como `Model|null` | Risco de autorização incorreta ou falso bloqueio; não corrigido neste sprint por exigir testes de segurança. |
| `app/Services/Contracts/*` | estados financeiros/contratuais comparados como string vs enum | Risco P1; requer suíte dirigida de contratos/rendas. |

## 9. Código Morto Provável

| Ficheiro/Área | Evidência | Decisão |
| --- | --- | --- |
| Factories documentais e relatórios | Erros só aparecem em factories raramente usadas. | Corrigir quando houver teste de factory ou uso real. |
| Branches `assert()` sempre verdadeiras | `function.alreadyNarrowedType` em controllers. | Não remover sem teste de rota/policy. |
| Métodos assinalados como `method.unused` | PHPStan não prova uso indireto via rotas/services. | Não remover. |

## 10. Riscos P0/P1

| Área | Risco | Prioridade |
| --- | --- | --- |
| Policies | enum/string mismatch e relações nullable em decisions, documents, complaints, maintenance | P0/P1 |
| Documentos privados | checklist/upload/review com enum mismatch e relações nullable | P0/P1 |
| RGPD/Security | Data subject requests, exports, retention e MFA/recovery parsing | P0 |
| Allocation/Lottery | scopes e relações não inferidas em listas definitivas, entries e offers | P1 |
| Contracts/Finance | estados de contrato/renda/pagamento e relações nullable | P1 |
| Lists/Complaints/Hearings | prazos e estados comparados com enums | P1 |

## 11. Correções Executadas

| Tipo | Ficheiros |
| --- | --- |
| Enum inexistente | `database/factories/DocumentDossierFactory.php` |
| Guardas de download | `app/Http/Controllers/Backoffice/Finance/PaymentReceiptController.php`, `app/Http/Controllers/Candidate/Finance/PaymentReceiptController.php` |
| Null-safety IA | `app/Http/Controllers/Backoffice/DocumentAiValidationController.php` |
| Testes | `tests/Feature/Sprint14FinanceTest.php`, `tests/Feature/Sprint24BackofficeOperationalTest.php`, `tests/Feature/Backoffice/DocumentAiValidationPanelTest.php` |

## 12. Correções Recusadas ou Adiadas

- Não foram corrigidos `match.alwaysFalse` em documentos porque os casts parecem corretos e os testes existentes passam.
- Não foram corrigidos scopes Eloquent em allocation/listas porque exigem sprint de relations/generics e testes de fluxo completo.
- Não foram corrigidas policies porque o próximo sprint recomendado é especificamente Security/RGPD/Policies/Audit.
- Não foram alterados casts em massa em Models porque isso poderia mudar serialização e comportamento de views/services.
- Não foram removidos branches considerados “sempre verdadeiros/falsos” sem prova funcional.

## 13. Testes Criados ou Atualizados

- `tests/Feature/Sprint24BackofficeOperationalTest.php`
  - `test_document_dossier_factory_uses_existing_status_enum`
- `tests/Feature/Sprint14FinanceTest.php`
  - adicionado cenário de download de recibo com `storage_path` nulo.
- `tests/Feature/Backoffice/DocumentAiValidationPanelTest.php`
  - `test_validation_panel_handles_records_without_severity`

## 14. Testes Executados

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-04-before.txt` | Falhou com 2755 erros esperados |
| `./vendor/bin/pint --test` | OK no baseline |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK no baseline: 280 testes, 1758 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter document_dossier_factory_uses_existing_status_enum` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter financial_manager_generates_schedule_registers_payment_allocates_and_issues_receipt` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter validation_panel_handles_records_without_severity` | OK |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-04-final.txt` | Falhou com 2751 erros remanescentes |
| `./vendor/bin/pint --test` | OK final |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK final: 282 testes, 1763 asserções |

## 15. Impacto por Workflow

| Workflow | Impacto |
| --- | --- |
| Registo → Candidatura | Sem alterações. Erros enum/string continuam classificados para futura tipagem. |
| Documentos → Validação → Aperfeiçoamento | Corrigida factory de dossier documental. Falsos positivos/DT em checklist e standardização adiados. |
| Pontuação → Listas | Sem alterações. Riscos em listas/prazos classificados como P1. |
| Sorteio → Atribuição → Contrato | Sem alterações. Scopes/relações em allocation classificados como P1 e adiados. |
| Contrato → Renda → Pagamento → Inquilino | Corrigido download de recibos financeiros sem path. |
| RGPD → Auditoria → Segurança | Sem alterações funcionais. Policies/RGPD adiados para PHPSTAN-05. |
| Document Intelligence | Corrigida consulta de validações IA com severidade nula. |

## 16. Impacto em Segurança/RGPD

- Nenhuma permissão foi alargada.
- Downloads continuam sujeitos a `Gate::authorize('view', $paymentReceipt)`.
- A correção dos recibos reduz risco de erro interno e não expõe paths.
- Nenhuma lógica RGPD foi alterada.
- Policies com risco P0/P1 foram analisadas, mas adiadas para sprint próprio com testes específicos.

## 17. Impacto em Performance

Não houve refactor de performance. Top melhorias futuras:

| Ficheiro/Área | Problema | Impacto | Recomendação | Sprint futura |
| --- | --- | --- | --- | --- |
| Reporting dashboards | queries agregadas repetidas | Médio | cache/materialização por período | PHPSTAN-06 |
| Exports | arrays completos em memória | Alto | chunked exports | PHPSTAN-06 |
| Audit logs | filtros por módulo/ação/data | Médio | confirmar índices compostos | PHPSTAN-05/06 |
| Document Intelligence | loads de fields/flags/logs | Médio | eager loading dirigido/paginação | PHPSTAN-07 |
| Allocation | loops sobre entries | Alto | transações e chunking | Sprint Allocation hardening |
| Scoring | recalculações sucessivas | Alto | snapshots imutáveis/cache | Sprint Scoring hardening |
| Lists | publicações e entries | Alto | bloquear recálculo após publicação | Sprint Lists hardening |
| Candidate dashboard | relações agregadas | Médio | eager loading e counters | PHPSTAN-06 |
| Finance | pagamentos/rendas por contrato | Médio | índices por contrato/período/status | PHPSTAN-06 |
| Documents | checklist por candidatura | Médio | cache por estado documental | Sprint Documents hardening |

## 18. Artefactos Criados

- `storage/phpstan/phpstan-04-before.txt`
- `storage/phpstan/phpstan-04-after-lote1.txt`
- `storage/phpstan/phpstan-04-after-lote2-v2.txt`
- `storage/phpstan/phpstan-04-final.txt`
- `docs/qa/phpstan-04-domain-logic-validation-report.md`

## 19. Riscos Residuais

- PHPStan continua a falhar por 2751 erros.
- A maior dívida permanece em Models e Services.
- Há risco real em Policies, RGPD, documentos privados e workflows administrativos, mas a correção exige testes específicos.
- Muitos erros parecem falsos positivos de casts/relations, mas devem ser resolvidos com tipagem explícita e testes, não com suppressions.

## 20. Plano Recomendado para PHPSTAN-05

Executar `SPRINT PHPSTAN-05 — SECURITY, RGPD, POLICIES & AUDIT HARDENING` com foco em:

1. Policies de documentos, candidaturas, complaints, maintenance e finance.
2. Relações nullable que podem afetar autorização.
3. RGPD: subject correto, exportação, retenção, anonimização.
4. Auditoria: ações críticas, downloads, decisões administrativas.
5. Testes de autorização e isolamento de dados entre candidatos.

Não recomenda avançar para correções massivas de generics antes de resolver os riscos P0/P1 em segurança e RGPD.
