# Incident Drill Checklist

## Objetivo

Checklist de drill para validar readiness operacional antes de piloto real controlado.

## Preparacao

- confirmar branch/tag;
- confirmar ambiente sem dados reais desnecessarios;
- confirmar responsaveis RACI;
- confirmar canal de comunicacao;
- confirmar backups e rollback documentados.

## Drill minimo

| Passo | Resultado esperado |
| --- | --- |
| Executar `mvhab:operations:health` | PASS ou WARN documentado |
| Executar `mvhab:operations:queue-health` | PASS ou WARN documentado |
| Simular job falhado | Playbook aplicado sem apagar evidencia |
| Simular upload documental indisponivel | Fluxo manual documentado |
| Simular documento privado exposto | SEV1, DPO e mitigacao documentados |
| Simular backoffice indisponivel | Runbook e rollback avaliados |
| Simular export sensivel indevido | Auditoria e revogacao avaliadas |
| Simular Work Task vencida | Reatribuicao com justificação |

## Evidencia

- hora de inicio/fim;
- comandos executados;
- decisao PASS/WARN/FAIL;
- riscos residuais;
- responsavel pela aceitacao.

## Criterio de aceitacao

O drill e aceite quando nao ha segredos expostos, incidentes SEV1 sem mitigacao, falhas de acesso a documentos privados ou incapacidade de rollback operacional.
