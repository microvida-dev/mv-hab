# Sprint 50A.1 — Inventário local de contratos legacy

## Âmbito

Este documento regista uma observação do ambiente local de desenvolvimento em
27/07/2026. Não representa staging nem produção e não autoriza qualquer
alteração de contratos.

Comando executado:

```bash
php artisan regulatory:inventory-legacy-contracts \
    --format=json \
    --output=/tmp/legacy-contracts.json
```

## Resultado observado

| Indicador | Resultado local |
| --- | ---: |
| Contratos encontrados | 1 |
| `missing_rent_calculation` | 1 |
| Outras classificações | 0 |

O registo observado não possui cálculo de renda na cadeia autoritativa e,
portanto, não pode ser classificado automaticamente como PAA ou RSAA.

O output contém apenas:

- IDs técnicos;
- IDs da cadeia regulamentar;
- regimes encontrados;
- classificação;
- razões técnicas.

Não contém nomes, NIF, emails, IBAN, documentos ou moradas. O comando não
altera contratos, não cria snapshots e não regista auditoria administrativa.

## Interpretação

Uma contagem local não permite extrapolar volume ou qualidade dos dados de
produção. Antes de qualquer trabalho operacional deve executar-se o comando no
ambiente autorizado, preservar o JSON como evidência e rever manualmente as
categorias ambíguas.

Não existe opção `--apply` nesta sprint.

## Procedimento futuro

1. executar o inventário read-only no ambiente autorizado;
2. validar a cadeia contrato → cálculo → candidatura/atribuição →
   concurso/programa → snapshot/perfil;
3. reconciliar fontes e ambiguidades fora da base de produção;
4. produzir um manifesto aprovado, sem PII;
5. implementar eventual aplicação numa sprint própria, idempotente, auditada
   e com rollback;
6. nunca classificar apenas por `created_at`, `updated_at`, descrição, email,
   role, Município em falta ou data corrente.

## Decisão

`REQUIRES_MANUAL_REVIEW`
