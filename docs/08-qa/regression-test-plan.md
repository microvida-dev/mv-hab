# Plano de Regressão

Data: 16/06/2026.

Este plano combina execução automática e checklist manual antes de staging/produção.

| Área | Objetivo | Pré-condições | Passos | Resultado esperado | Teste automático associado | Risco |
| --- | --- | --- | --- | --- | --- | --- |
| Login e permissões | Validar acesso por role | Users/roles seedados | Login por role e acesso a módulos | Guest redirecionado, candidato sem backoffice, roles internas limitadas | `tests/Feature/Security/PermissionMatrixTest.php` | Alto |
| Registo de adesão | Garantir criação/finalização segura | Candidato autenticado | Criar, editar e finalizar adesão | Estado e histórico corretos | `tests/Feature/Sprint4AdhesionRegistrationTest.php` | Médio |
| Agregado familiar | Validar ownership e regras de membros | Adesão finalizada | Criar agregado e membros | Um requerente mínimo e isolamento por candidato | `tests/Feature/Sprint5HouseholdIncomeHousingTest.php` | Médio |
| Documentos | Validar checklist e storage privado | Dados de candidato completos | Submeter, substituir, descarregar e rever documento | Paths privados não expostos; terceiro bloqueado | `tests/Feature/Documents/DocumentSecurityFlowTest.php` | Alto |
| Candidatura | Validar submissão formal | Concurso aberto, documentos completos | Criar rascunho, aceitar declarações, submeter | Número único, snapshots e bloqueio pós-submissão | `tests/Feature/Sprint8ApplicationSubmissionTest.php` | Alto |
| Elegibilidade | Validar critérios e resultados | Rule set ativo | Executar pre-check e check formal | Resultado e snapshots sem dados sensíveis | `tests/Feature/Sprint7EligibilityEngineTest.php` | Alto |
| Workflow administrativo | Validar análise municipal | Candidatura submetida | Criar processo, pedir aperfeiçoamento, decidir | Histórico e decisão fundamentada | `tests/Feature/Sprint9AdministrativeWorkflowTest.php` | Alto |
| Aperfeiçoamento | Validar resposta do candidato | Pedido emitido | Responder com texto/documento | Resposta própria e análise backoffice | `tests/Feature/Sprint9AdministrativeWorkflowTest.php` | Médio |
| Classificação | Validar pontuação | Processo admitido e elegível | Executar matriz e revisão manual | Scores e detalhes coerentes | `tests/Feature/Sprint10ScoringRankingTest.php` | Alto |
| Listas | Validar lista provisória/definitiva | Ranking bloqueado | Gerar, aprovar, publicar | Identificador público sem dados pessoais | `tests/Feature/Sprint11ListsComplaintsHearingTest.php` | Alto |
| Reclamações | Validar submissão e decisão | Lista provisória publicada | Submeter reclamação e decidir | Efeito refletido na lista definitiva | `tests/Feature/Sprint11ListsComplaintsHearingTest.php` | Alto |
| Atribuição | Validar habitação e oferta | Lista definitiva publicada | Executar atribuição e aceitar oferta | Atribuição pronta para contrato | `tests/Feature/Sprint12AllocationTest.php` | Alto |
| Contrato | Validar contrato processual | Atribuição aceite | Calcular renda, criar contrato, validar, ativar | Contrato ativo e habitação ocupada | `tests/Feature/Sprint13ContractsRentDepositTest.php` | Alto |
| Renda | Validar mínimos, máximos, caução | Regra de renda ativa | Calcular com rendimentos definidos | Renda e caução determinísticas | `tests/Unit/Contracts/RentCalculationDeterministicTest.php` | Alto |
| Pagamentos | Validar plano e imputação | Contrato ativo | Gerar plano, emitir prestação, registar pagamento | Conta e extrato coerentes | `tests/Feature/Sprint14FinanceTest.php` | Alto |
| Incumprimentos | Validar mora e avisos | Prestação vencida | Detetar mora, emitir aviso, acordo | Estado financeiro correto | `tests/Feature/Sprint14FinanceTest.php` | Alto |
| Manutenção | Validar pedido e triagem | Contrato ativo | Criar pedido, rever, agendar, concluir | Histórico técnico atualizado | `tests/Feature/Sprint15MaintenanceInspectionTest.php` | Médio |
| Notificações | Validar templates e logs | Eventos configurados | Disparar comunicação interna | Notificação/log criado sem envio externo real | `tests/Feature/Sprint16CommunicationsTest.php` | Médio |
| Relatórios | Validar dashboards/exportações | Dados de domínio existentes | Abrir dashboard e exportar | Sem erro, dados agregados, auditoria | `tests/Feature/Sprint17ReportingDashboardTest.php` | Médio |
| RGPD | Validar pedidos/exportação/retencao | Candidato e backoffice | Criar pedido, exportar, simular retenção | Dados próprios e logs de acesso | `tests/Feature/Sprint18RgpdSecurityAuditTest.php` | Alto |
| Auditoria | Validar trilho imutável/mascarado | Eventos críticos | Executar operações críticas | Eventos auditados, sensíveis mascarados | `tests/Unit/Audit/AuditEventFormatterTest.php` | Alto |
| Exportações | Validar downloads controlados | Export existente | Download por autorizado e bloqueio de terceiro | Sem path interno exposto | `tests/Feature/Sprint17ReportingDashboardTest.php`, `tests/Feature/Sprint18RgpdSecurityAuditTest.php` | Médio |
| Storage privado | Validar ausência de URL pública | Documento submetido | Aceder via controller e `/storage/...` | Controller autorizado; URL direta bloqueada | `tests/Feature/Documents/DocumentSecurityFlowTest.php` | Alto |

## Comandos automáticos recomendados

```bash
php artisan route:list
php artisan test
npm run build
./vendor/bin/pint --test
php artisan view:cache
```

Se PHPStan/Psalm forem instalados no futuro:

```bash
./vendor/bin/phpstan analyse
./vendor/bin/psalm
```
