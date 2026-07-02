# Municipal State of Art Seeder

Este seeder prepara dados fictícios completos para validar a plataforma MV HAB em ambiente local, demonstração controlada ou staging descartável.

Não deve ser usado para introduzir dados reais.

## Comando

```bash
php artisan db:seed --class=Database\\Seeders\\MunicipalStateOfArtSeeder
```

Quando for necessário autenticar utilizadores E2E, definir uma password apenas no ambiente local:

```bash
MVHAB_E2E_USER_PASSWORD="SUBSTITUIR_LOCALMENTE" php artisan db:seed --class=Database\\Seeders\\MunicipalStateOfArtSeeder
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
