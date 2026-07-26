# TECH-TEST-001 — Mapa de cobertura crítica

Base auditada: Sprint 45D (`d00406d3b22fee5dabfd326495eac752d9276312`).

Escala:

- **Completa**: boundary positivo e negativo exercitado com middleware/Policy real e efeitos verificados.
- **Parcial**: existe cobertura relevante, mas falta uma dimensão ou combinação.
- **Caracterização**: documenta o comportamento atual sem cobrir toda a matriz.

| Fluxo crítico | Teste(s) existente(s) | Tipo | Cobertura | Lacuna | Prioridade | Sprint recomendada |
|---|---|---|---|---|---:|---|
| Autenticação | `Auth/AuthenticationTest`, `CandidateBackofficeBoundaryTest` | Feature | Completa | Sem lacuna no boundary base | baixa | manutenção contínua |
| Utilizador ativo | `CandidateBackofficeBoundaryTest`, `ApplicationFeatureEntitlementAccessTest` | Feature | Parcial | Falta matriz única para todos os estados inativos e rotas backoffice sensíveis | média | TECH-ACCESS-UX-001 |
| MFA | `MfaEnforcementBackofficeTest`, `PermissionSensitiveMfaTest`, `ApplicationFeatureEntitlementAccessTest` | Feature | Completa | Revalidar challenge no tratamento central 403 | alta | TECH-ACCESS-UX-001 |
| Permission middleware | `RequirePermissionMiddlewareTest`, testes `*PermissionAccessTest` | Feature | Completa | Sem lacuna estrutural; manter auditoria de rotas | alta | contínua |
| Role inativa | `CustomRoleEffectivePermissionTest`, `ApplicationFeatureEntitlementAccessTest` | Feature | Completa | Alargar apenas quando surgirem novos perfis | média | contínua |
| Entitlement ativo/inativo | `MunicipalityFeatureMiddlewareTest`, `ApplicationFeatureEntitlementAccessTest` | Feature | Completa | Sem lacuna nas três features atuais | alta | contínua |
| Ausência de Município | `MunicipalityFeatureMiddlewareTest` | Feature | Completa | Acrescentar resposta amigável sem mudar o fail-closed | alta | TECH-ACCESS-UX-001 |
| Policy por registo | `ApplicationBackofficePolicyTest`, `AdministrativeProcessBackofficePolicyTest`, `ApplicationArtifactPolicyTest` | Feature | Completa | Alguns domínios não municipais continuam cobertos por testes históricos dispersos | média | futura auditoria por domínio |
| Isolamento entre dois Municípios | `ApplicationFeatureEntitlementAccessTest`, `MunicipalityFeatureNavigationTest`, `MunicipalExportsSecurityTest` | Feature | Completa em candidaturas/exportações | Alargar a documentos e elegibilidade com fixtures A/B explícitas | alta | TECH-SECURITY-MULTITENANCY |
| Candidate | `CandidateBackofficeBoundaryTest`, testes de Policy/rota de candidatura | Feature | Completa | Revalidar HTML/JSON centralizados | alta | TECH-ACCESS-UX-001 |
| Auditor | `AuditorReadOnlyAccessTest`, `InitialMunicipalRoleProfilesTest` | Feature | Parcial | Cobertura read-only não está agregada para todos os novos endpoints municipais | alta | TECH-ACCESS-UX-001 |
| Administração de plataforma | `MunicipalityFeatureManagementTest`, `SystemRoleProtectionTest` | Feature | Completa | Falta matriz dedicada de landing page autorizada | média | TECH-ACCESS-UX-001 |
| Candidaturas | `ApplicationIntakePermissionAccessTest`, `ApplicationBackoffice*`, `ApplicationReview*` | Feature | Completa | Sem lacuna nas rotas convertidas 45B–45D | alta | contínua |
| Documentos | `DocumentReviewPermissionAccessTest`, `DocumentSecurityFlowTest`, `DocumentConfigurationPermissionAccessTest` | Feature | Completa | Reforçar isolamento A/B do download privado | alta | TECH-SECURITY-MULTITENANCY |
| Elegibilidade | `Sprint7EligibilityEngineTest`, `AlcanenaEligibilityRulesTest` | Feature/Unit | Parcial no entitlement | Entitlement atual é herdado de review; falta teste A/B dedicado ao detalhe de elegibilidade | alta | TECH-SECURITY-MULTITENANCY |
| Relatórios | `MunicipalReportsAuthorizationTest`, `Sprint17ReportingDashboardTest` | Feature | Completa | Revalidar respostas JSON no feedback central | média | TECH-ACCESS-UX-001 |
| Exportações | `MunicipalExportsSecurityTest`, `ApplicationFeatureEntitlementAccessTest` | Feature | Completa | Sem lacuna nas features atuais | alta | contínua |
| Dashboard | `ProfileDashboardAuthorizationTest`, `MunicipalityFeatureNavigationTest` | Feature | Completa para contagens de candidaturas | Cobertura de outros widgets multi-Município é parcial | média | futura matriz analytics |
| Navegação | `MunicipalityFeatureNavigationTest`, `CandidateNavigationEngineTest` | Feature | Completa para features atuais | Revalidar ocultação após resposta centralizada | média | TECH-ACCESS-UX-001 |
| Pesquisa universal | `UniversalSearchAuthorizationTest`, `MunicipalityFeatureNavigationTest` | Feature | Completa para candidatura | Alargar A/B a cada source municipal | alta | TECH-SECURITY-MULTITENANCY |
| URL direta | `ApplicationBackofficeRouteAccessTest`, `CandidateBackofficeBoundaryTest`, testes `*RouteAccessTest` | Feature | Completa | Validar página 403 integrada sem converter o status | alta | TECH-ACCESS-UX-001 |
| Mutações recusadas sem efeitos | `ApplicationFeatureEntitlementAccessTest`, `AdministrativeProcessMutationPermissionAccessTest`, `InitialMunicipalRoleProfilesTest` | Feature | Parcial | Uniformizar `assertDatabaseMissing`/`assertNotDispatched` nos restantes módulos | alta | TECH-TEST-SIDE-EFFECTS |
| Auditoria | `CustomRoleAuditTest`, `MunicipalityFeatureAuditTest`, `SensitiveAccessAuditTest`, `SensitiveExportAuditTest` | Feature | Completa para ações críticas atuais | Adicionar recusas deduplicadas e request ID | alta | TECH-ACCESS-UX-001 |
| Respostas JSON/API | testes de autorização por domínio | Feature | Parcial | Falta contrato central `{message, code, request_id}` para 403 JSON | alta | TECH-ACCESS-UX-001 |

## Conclusões

1. A matriz `municipalityHasFeature && userHasPermission && policyAllowsRecordScope` está coberta no fluxo de candidaturas e exportações.
2. As fixtures que ativavam todas as features foram tornadas explícitas na 46A.
3. As lacunas prioritárias não justificam enfraquecer os testes existentes: concentram-se no feedback central de recusas (46D), isolamento multi-Município de fontes adicionais e asserções uniformes de ausência de efeitos.
4. Candidate, MFA e entitlements permanecem fail-closed.
