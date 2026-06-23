# Sprint 1 — Fundacao tecnica Laravel

Estado: executada parcialmente como fundacao tecnica inicial.

## Objetivo

Criar a fundacao tecnica Laravel necessaria para evoluir o CRM para plataforma processual, sem implementar ainda funcionalidades de candidatura, elegibilidade, classificacao, atribuicao, contratos avancados, pagamentos avancados ou manutencao avancada.

## Pre-condicoes obrigatorias

- Confirmar repositorio Git e branch segura. Dispensado explicitamente nesta execucao pelo utilizador: nao sera usado Git neste contexto.
- Definir estrategia de ambientes: local, teste, staging e producao.
- Confirmar politica de secrets e `.env`.
- Aprovar matriz inicial de roles/permissions.
- Aprovar estrategia de auditoria minima.
- Validar dados de demonstracao permitidos.
- Definir se a plataforma sera single-municipality inicial ou multi-municipality desde Sprint 1.

## Escopo candidato

- Rever estrutura Laravel atual.
- Definir convencoes de modulos.
- Preparar roles e permissions.
- Preparar policies base.
- Preparar audit logs base.
- Preparar separacao candidato/backoffice.
- Rever seeders para evitar dados perigosos.
- Preparar testes de autorizacao.
- Documentar comandos operacionais seguros.

## Fora de escopo

- Implementar processo completo de candidatura.
- Implementar motor de elegibilidade.
- Implementar matriz de classificacao.
- Implementar atribuicao.
- Implementar contratos avancados.
- Implementar pagamentos avancados.
- Implementar manutencao avancada.
- Executar `migrate:fresh` sem aprovacao explicita.

## Riscos a tratar

- Branch/repo nao confirmados na Sprint 0.
- Ausencia de policies.
- Form Requests de dominio com autorizacao generica.
- Falta de auditoria.
- Seeders de demonstracao.
- Documentos sem controlo de acesso granular.

## Criterios de saida propostos

- Branch confirmada. Nao aplicavel por decisao do utilizador.
- Plano de migrations aprovado antes de criacao. Cumprido por validacao explicita para avancar.
- Modelo de roles/permissions aprovado. Implementada primeira versao em `config/mvhab.php`.
- Primeiras policies planeadas ou implementadas conforme validacao. Implementadas policies base.
- Auditoria minima planeada ou implementada conforme validacao. Implementada tabela e servico `AuditLogger`.
- Testes de autorizacao definidos. Implementados testes de fundacao.
- Nenhum dado sensivel real introduzido. Cumprido.

## Implementado nesta execucao

- Configuracao tecnica de modulos, roles e permissions em `config/mvhab.php`.
- Migrations de fundacao para:
  - `municipalities`;
  - `roles`;
  - `permissions`;
  - `permission_role`;
  - `role_user`;
  - novos campos fundacionais em `users`;
  - `audit_logs`.
- Models:
  - `Municipality`;
  - `Role`;
  - `Permission`;
  - `AuditLog`.
- Relacoes e helpers em `User`:
  - municipio;
  - roles;
  - `hasRole`;
  - `hasPermission`;
  - `hasPermissionTo`;
  - `assignRole`.
- Seeder `SystemAccessSeeder` para materializar roles e permissions a partir da configuracao.
- Policies base:
  - `CitizenPolicy`;
  - `HouseholdPolicy`;
  - `HousingUnitPolicy`;
  - `HousingApplicationPolicy`;
  - `ContractPolicy`;
  - `PaymentPolicy`;
  - `MaintenanceRequestPolicy`;
  - `DocumentPolicy`;
  - `UserPolicy`;
  - `MunicipalityPolicy`;
  - `AuditLogPolicy`.
- Servico de auditoria `AuditLogger`.
- Constantes de eventos em `AuditEvents`.
- Testes:
  - `FoundationAccessControlTest`;
  - `AuditLoggerTest`.

## Nao implementado nesta execucao

- Nenhum workflow novo de candidatura.
- Nenhum portal publico.
- Nenhuma elegibilidade.
- Nenhuma classificacao.
- Nenhuma atribuicao.
- Nenhum contrato avancado.
- Nenhum pagamento avancado.
- Nenhuma manutencao avancada.
- Nenhuma alteracao de rotas aplicacionais.
- Nenhuma execucao de `migrate`, `migrate:fresh` ou `db:seed`.
- Nenhuma instalacao de pacotes Composer ou npm.

## Validacao executada

- `php -l` em ficheiros novos e alterados relevantes: sem erros de sintaxe.
- `php artisan test --filter=FoundationAccessControlTest`: passou, 3 testes, 10 assertions.
- `php artisan test --filter=AuditLoggerTest`: passou, 1 teste, 3 assertions.
- `php artisan test`: passou, 29 testes, 75 assertions.

## Pendencias antes da Sprint 2

- Decidir quando aplicar `php artisan migrate` num ambiente controlado.
- Rever a decisao de manter `DatabaseSeeder` a executar seeders de demonstracao.
- Atribuir roles aos utilizadores existentes apos migracao.
- Decidir quando ativar policies nos controllers/Form Requests sem bloquear operadores existentes.
- Validar matriz de permissoes com responsaveis municipais.
- Definir estrategia de environments e secrets.
