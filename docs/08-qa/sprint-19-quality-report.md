# Relatorio de Qualidade Sprint 19

Data: 16/06/2026.

Estado final da Sprint 19: `ready_for_staging_with_minor_risks`.

## Resumo executivo

A Sprint 19 consolidou uma camada de testes integrados e de regressao sobre a plataforma MV HAB, sem criar novos modulos de negocio. Foram adicionados testes determinísticos para elegibilidade, pontuação, renda e auditoria, testes de permissões, segurança documental, cenário integrado ponta a ponta possível e smoke básico de queries.

## Estado geral da plataforma

- Laravel: 13.12.0.
- PHP: 8.5.6.
- Framework de testes: PHPUnit via `php artisan test`.
- Base de dados de teste: SQLite em memória conforme `phpunit.xml`.
- Mail, cache, session e queue em modo seguro de teste (`array`/`sync`).
- Pest, PHPStan, Psalm, Rector e CI: não configurados.
- Pint: instalado em `vendor/bin/pint`, sem `pint.json` próprio.

## Módulos testados

Autenticação, roles/permissões, adesão, agregado, rendimentos, situação habitacional, documentos, elegibilidade, candidaturas, workflow administrativo, classificação, ranking, listas, reclamações, atribuição, contratos, renda, pagamentos, incumprimentos, manutenção, vistorias, notificações, comunicações, relatórios, RGPD, auditoria e segurança.

## Módulos não testados por dependência

- Integrações externas de email/SMS, AT, Segurança Social, Autenticação.GOV, assinatura digital, gateway de pagamento e OCR não existem e continuam fora de âmbito.
- Testes de carga avançados dependem de infraestrutura própria.
- PDF real não está instalado; os módulos usam HTML privado/fallback documentado.

## Métricas de testes

- Testes existentes antes da Sprint 19: 157.
- Asserções existentes antes da Sprint 19: 995.
- Testes criados na Sprint 19: 17.
- Asserções dos testes Sprint 19: 169.
- Testes totais confirmados após Sprint 19: 174.
- Asserções totais confirmadas após Sprint 19: 1164.
- Resultado confirmado da fatia Sprint 19: `17` testes, `169` asserções, sem falhas.
- Resultado confirmado da suite completa: `174` testes, `1164` asserções, sem falhas.

## Validação final executada

| Comando | Resultado |
| --- | --- |
| `php artisan route:list` | Passou; 830 rotas listadas. |
| `php artisan test` | Passou; 174 testes e 1164 asserções. |
| `npm run build` | Passou; bundle Vite gerado. |
| `./vendor/bin/pint --test` | Falhou inicialmente por formatação pendente; passou após `./vendor/bin/pint`. |
| `composer validate --no-check-publish` | Passou; `composer.json` válido. |
| `php artisan view:cache` | Passou; templates Blade compilados. |
| `php artisan view:clear` | Passou; cache de views limpa após validação. |

## Bugs encontrados

Não foram encontrados bugs críticos de produção durante a criação da camada Sprint 19. Foram corrigidos problemas de implementação dos próprios artefactos de QA antes de consolidar a suite:

- uso de factory em teste unitário que tentava persistir relações sem migrations carregadas;
- campo inexistente `decided_at` no seeder integrado de reclamações;
- expectativa de teste desalinhada com regra real de renda mínima quando rendimento mensal é zero;
- validação de auditoria documental atualizada para `audit_events`, que é o mecanismo avançado implementado na Sprint 18.

## Riscos de produção

- Regras legais demo ainda precisam de substituição por critérios aprovados.
- Sem CI, a validação depende de execução manual/local.
- Sem PHPStan/Psalm, faltam checks estáticos automáticos.
- Smoke tests de queries não equivalem a prova de carga.
- Integrações externas permanecem simuladas ou fora de âmbito.

## Riscos RGPD/segurança

- Necessária validação final por responsável RGPD/DPO antes de produção.
- Necessário pentest externo e revisão de configuração de infraestrutura.
- Garantir política operacional de retenção, backups e logs em ambiente real.
- Confirmar que exports e downloads oficiais têm trilho de auditoria em produção.

## Riscos de performance

- Listagens de candidaturas, ranking, listas, relatórios e auditoria devem manter paginação obrigatória.
- Exportações pesadas devem ir para queue antes de produção real.
- Query budgets da Sprint 19 são básicos e devem evoluir para métricas por volume.

## Go/no-go técnico final

Recomendação final da Sprint 19: avançar para validação de staging, não para produção direta.

Condição para Sprint 20: validar pendências jurídicas, operacionais, RGPD, infraestrutura, backups, formação e plano de migração.
