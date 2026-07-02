# Municipal State of Art Seeder

Este seeder prepara dados fictícios completos para validar a plataforma MV HAB em ambiente local, demonstração controlada ou staging descartável.

Não deve ser usado para introduzir dados reais.

## Comando

Modo explícito, recomendado quando a base já tem estrutura carregada:

```bash
php artisan db:seed --class=Database\\Seeders\\MunicipalStateOfArtSeeder
```

Modo completo para recriar a base e carregar a demo no mesmo comando:

```bash
MVHAB_E2E_USER_PASSWORD="PASSWORD_LOCAL_DE_TESTE" MVHAB_SEED_STATE_OF_ART_DEMO=true php artisan migrate:fresh --seed
```

Sem a flag `MVHAB_SEED_STATE_OF_ART_DEMO=true`, o comando `php artisan migrate:fresh --seed` executa apenas seeders estruturais. Nesse caso, as tabelas de agenda/timeline ficam sem dados demo por desenho.

Quando `MVHAB_E2E_USER_PASSWORD` está definido, essa password é aplicada aos utilizadores fictícios dos domínios reservados `@example.test` e `@exemplo.pt`.

Quando for necessário autenticar utilizadores E2E, definir uma password apenas no ambiente local:

```bash
MVHAB_E2E_USER_PASSWORD="PASSWORD_LOCAL_DE_TESTE" php artisan db:seed --class=Database\\Seeders\\MunicipalStateOfArtSeeder
```

Nunca versionar passwords, `.env`, dumps, documentos reais ou screenshots com dados pessoais.

## Objetivo

O `DatabaseSeeder` fica reservado para dados estruturais mínimos.

O `MunicipalStateOfArtSeeder` é explícito e cobre o percurso funcional completo:

- portal público;
- registo de adesão;
- agregado;
- rendimentos;
- habitação atual;
- candidaturas em rascunho, submetidas, em aperfeiçoamento, elegíveis e atribuídas;
- validação documental;
- IA documental local fictícia;
- decisões processuais;
- elegibilidade;
- pontuação;
- ranking;
- listas provisórias e definitivas;
- audiência prévia;
- reclamações;
- atribuição;
- contrato;
- área do inquilino;
- rendas e registos financeiros manuais;
- entrega de chaves;
- manutenção;
- vistorias;
- open house e visitas a imóveis;
- tickets de apoio;
- notificações;
- Work Tasks;
- cronologia operacional;
- auditoria;
- RGPD.

## Agenda municipal

Os dados temporais críticos usam a data-base fixa:

```php
Carbon::parse('2026-07-02 09:00:00')
```

O seeder cria eventos futuros, posteriores a 02/07/2026, para alimentar Agenda, Timeline e providers operacionais:

- Work Tasks;
- visitas/open house;
- vistorias;
- audiências;
- reclamações;
- manutenção;
- intervenções de manutenção;
- candidaturas submetidas;
- entrega de chaves;
- pedidos RGPD;
- alertas internos;
- ofertas de atribuição;
- convocatórias de sorteio;
- rendas/prestações;
- pedidos documentais adicionais;
- pedidos de informação adicional;
- ações processuais;
- pedidos de aperfeiçoamento.

A distribuição temporal cobre 03/07/2026, 04/07/2026, 07/07/2026, 10/07/2026, 15/07/2026, 22/07/2026, 05/08/2026 e 12/08/2026.

## Estrutura

O orquestrador está em:

```text
database/seeders/MunicipalStateOfArtSeeder.php
```

Os blocos modulares estão em:

```text
database/seeders/Pilot/
```

Principais seeders:

- `PilotCoreSeeder`
- `PilotUsersAndTeamsSeeder`
- `PilotProgramsContestsSeeder`
- `PilotHousingUnitsSeeder`
- `PilotCandidateJourneySeeder`
- `PilotApplicationStatesSeeder`
- `PilotDocumentWorkflowSeeder`
- `PilotRankingAndAllocationSeeder`
- `PilotHearingComplaintSeeder`
- `PilotContractsTenantSeeder`
- `PilotMaintenanceInspectionSeeder`
- `PilotVisitsSupportSeeder`
- `PilotOperationsAgendaSeeder`
- `PilotRgpdAuditSeeder`

## Utilizadores fictícios

Todos os emails usam domínios reservados:

- `@example.test`
- `@exemplo.pt`

Perfis principais:

- `e2e.admin@example.test`
- `e2e.candidato.draft@example.test`
- `e2e.candidato.submitted@example.test`
- `e2e.candidato.correction@example.test`
- `e2e.candidato.eligible@example.test`
- `e2e.candidato.contract@example.test`
- `e2e.tecnico@example.test`
- `e2e.juri@example.test`
- `e2e.juridico@example.test`
- `e2e.habitacao@example.test`
- `e2e.financeiro@example.test`
- `e2e.manutencao@example.test`
- `e2e.vistorias@example.test`
- `e2e.atendimento@example.test`
- `e2e.auditor@example.test`

## Regras de segurança

- Dados fictícios apenas.
- Sem NIF real.
- Sem IBAN real.
- Sem documentos reais.
- Sem ficheiros privados reais em `storage`.
- Sem paths pessoais.
- Sem passwords em documentação.
- Documentos simulados usam referências privadas fictícias.
- O seeder é idempotente e pode ser executado mais do que uma vez.

## Validação automatizada

Executar:

```bash
MVHAB_E2E_USER_PASSWORD="PASSWORD_LOCAL_DE_TESTE" MVHAB_SEED_STATE_OF_ART_DEMO=true php artisan migrate:fresh --seed
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter MunicipalStateOfArtSeederTest
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Seeder
```

O teste dedicado confirma:

- idempotência;
- cobertura dos principais domínios operacionais;
- uso de domínios de email reservados;
- ausência de artefactos reais em storage.

## Próximos passos operacionais

Depois de executar o seeder, usar o guia:

```text
docs/11-operacoes/mv-hab-end-to-end-test-guide.md
```

Esse guia descreve os caminhos de portal público, candidato, backoffice, pontuação, listas, contrato, inquilino, manutenção, vistorias, RGPD e auditoria.
