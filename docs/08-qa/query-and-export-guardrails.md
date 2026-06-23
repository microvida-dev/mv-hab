# Query and Export Guardrails

## Objetivo

Definir regras de performance para dashboards, relatorios, listas, rankings, exports, documentos, portal publico e area do inquilino.

## Areas criticas

| Area | Risco principal | Guardrail |
| --- | --- | --- |
| Dashboards | N+1, agregacoes pesadas, queries repetidas. | Usar eager loading, agregacoes SQL e cache curta quando apropriado. |
| Relatorios | Filtros complexos e memoria elevada. | Usar paginacao, chunking e jobs para volumes grandes. |
| Listas e rankings | Ordenacao instavel e joins pesados. | Indexar chaves de concurso, estado, score e timestamps. |
| Exports | Memory spikes e timeouts. | Usar `chunkById`, filas e ficheiros temporarios privados. |
| Documentos | Downloads pesados e acesso repetido. | Storage privado, streaming controlado e autorizacao antes de IO. |
| Portal publico | Alto volume de leitura. | Apenas dados publicaveis, cache segura e paginacao. |
| Area do inquilino | IDOR e queries por ownership. | Scope por utilizador/inquilino e eager loading limitado. |

## Regras obrigatorias

- Listagens devem ser paginadas.
- Dashboards devem evitar loops que executam queries por linha.
- Relacoes usadas em views devem ser carregadas antes da renderizacao quando previsiveis.
- Exports grandes devem usar chunking ou jobs.
- Relatorios devem limitar colunas selecionadas quando lidam com dados extensos.
- Filtros por municipio, concurso, estado e periodo devem ter indice quando usados frequentemente.
- Queries de documentos privados devem autorizar antes de ler ficheiro do disco.

## Checklist para novas listagens

- [ ] A query tem paginacao.
- [ ] As relacoes usadas na view estao em `with()` ou foram agregadas previamente.
- [ ] O filtro principal usa coluna indexavel.
- [ ] Nao ha `all()` em tabelas operacionais.
- [ ] Nao ha loops com queries internas evitaveis.
- [ ] A ordenacao e deterministica.
- [ ] Dados pessoais sao minimizados.

## Checklist para exports

- [ ] Export autorizado por policy.
- [ ] Export auditado quando contem dados pessoais ou administrativos.
- [ ] Usa chunking para volume elevado.
- [ ] Nao expoe paths internos.
- [ ] Ficheiros temporarios ficam em storage privado.
- [ ] O resultado tem periodo de retencao definido quando aplicavel.

## Indices recomendados por dominio

| Dominio | Colunas candidatas |
| --- | --- |
| Candidaturas | `contest_id`, `user_id`, `status`, `submitted_at`, `application_number` |
| Elegibilidade | `application_id`, `status`, `checked_at` |
| Scoring/ranking | `contest_id`, `application_id`, `total_score`, `rank_position`, `snapshot_id` |
| Listas | `contest_id`, `status`, `published_at` |
| Documentos | `application_id`, `document_type_id`, `status`, `submitted_by`, `created_at` |
| Contratos | `application_id`, `tenant_profile_id`, `status`, `starts_at`, `ends_at` |
| Rendas/pagamentos | `tenant_profile_id`, `due_date`, `status`, `paid_at` |
| Manutencao | `tenant_profile_id`, `housing_unit_id`, `status`, `priority`, `created_at` |
| Auditoria | `auditable_type`, `auditable_id`, `user_id`, `event`, `created_at` |
| RGPD | `user_id`, `status`, `request_type`, `created_at` |

## Validacao recomendada

Antes de pre-release:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Performance
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v
```

Para futuras sprints, adicionar testes de regressao para listagens com multiplas relacoes e exports com volume simulado.
