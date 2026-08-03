# Matriz de permissões do Programa 53

## Princípio

As roles agregam permissões, mas não constituem a fronteira de acesso. Cada operação continua a exigir a permissão exata, a Policy aplicável, o Município, o entitlement, MFA e o estado válido do recurso.

## Matriz funcional

| Capacidade | Operador recolha | Analista | Exportador | Analista + exportação | Auditor |
|---|---:|---:|---:|---:|---:|
| Consultar candidaturas | Sim | Sim | Sim | Sim | Leitura autorizada |
| Atualizar intake | Sim | Não por defeito | Não | Não por defeito | Não |
| Rever documentos | Não | Sim | Não | Sim | Não |
| Aprovar/rejeitar documento | Não | Sim | Não | Sim | Não |
| Gerir aperfeiçoamentos | Criação inicial limitada | Sim, matriz atual | Não | Sim | Não |
| Criar análise/lote | Criação individual atual | Não por defeito | Não | Sim | Não |
| Selar lote | Não | Sim, matriz atual | Não | Sim | Não |
| Publicar lote | Não | Não | Não | Sim | Não |
| Exportar resultado normal | Não | Não | Sim | Sim | Não |
| Exportar dados sensíveis | Não | Não | Não | Não | Não |
| Consultar auditoria de exportação | Não | Não | Sim | Sim | Sim |
| Administrar acessos | Não | Não | Não | Não | Não |
| Scope global | Não | Não | Não | Não | Não |

## Permissões efetivas do novo template

O template `analista-candidaturas-exportacao` parte das capacidades reais consumidas pelas rotas do Programa 53:

```text
dashboard.view
applications.view
applications.update
applications.audit
applications.export
documents.view
documents.update
documents.replace
documents.download
documents.analyze
documents.review_ai
documents.approve
documents.reject
documents.audit
eligibility.view
eligibility.run
administrative_processes.view
administrative_processes.create
administrative_processes.update
administrative_processes.assign
administrative_processes.decide
administrative_processes.complete
administrative_processes.cancel
administrative_processes.issue
administrative_processes.mark_overdue
administrative_processes.publish
administrative_processes.audit
administrative_decisions.view
administrative_decisions.create
work_tasks.view
work_tasks.claim
work_tasks.update_status
work_tasks.complete
reports.view
reports.export
reports.audit
```

## Reconciliação da nomenclatura

Não existem módulos de permissões `application_review_batches` ou `correction_cycles`. As rotas já usam permissões granulares de `administrative_processes`; criar aliases sem consumidores produziria permissões órfãs e não reforçaria as rotas. A Sprint reutiliza os nomes reais.

`administrative_processes.publish` é necessário apenas para publicação. `applications.export` e `reports.export` são cumulativas nas exportações temporais. `reports.export_sensitive` fica explicitamente excluída.

## Dependências de entitlement

| Operação | Entitlement |
|---|---|
| Revisão, lotes, publicação e aperfeiçoamentos | `applications.review` |
| Exportação temporal e download | `applications.export` |

O template não cria nem ativa qualquer entitlement.

## Exclusões explícitas

O template não contém wildcard nem permissões dos módulos `users`, `roles`, `teams`, `platform_operators`, `finance`, `payments`, `contracts`, `privacy`, `rgpd`, `scoring`, `public_lists`, `lottery`, `maintenance_requests` ou `inspections`. Também não contém `reports.export_sensitive`.
