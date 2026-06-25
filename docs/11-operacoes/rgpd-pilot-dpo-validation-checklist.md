# RGPD Pilot DPO Validation Checklist

## Objetivo

Checklist para validacao DPO/juridica antes de piloto real controlado.

## Checklist

| Item | Estado esperado |
| --- | --- |
| Finalidades documentadas | Validar |
| Bases legais documentadas | Validacao municipal/juridica pendente |
| Retencao por dominio | Validar |
| Anonimizacao | Validar |
| Exportacoes sensiveis | Autorizadas e auditadas |
| Documentos privados | Storage privado e policies |
| IA documental | Assistiva, sem decisao automatica |
| Access logs | Minimizados e restritos |
| Relatorios | Agregados por defeito |
| Exports nominais | Permissao especifica |
| DPO em incidentes SEV1 | RACI definido |
| CMD/pagamentos externos | Out of scope by municipal decision |

## Evidencia a rever

- `docs/11-operacoes/rgpd-final-policy-alignment.md`;
- `docs/11-operacoes/data-retention-anonymization-policy.md`;
- `docs/11-operacoes/data-subject-request-playbook.md`;
- logs de teste sanitizados em `storage/qa`;
- relatorios QA-45, QA-46 e QA-47.
