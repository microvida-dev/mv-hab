# Active Risks

| Area | Risco | Mitigacao |
| --- | --- | --- |
| Documentos | Exposicao indevida de ficheiros privados. | Downloads sempre por controller autorizado, policy e auditoria. |
| Elegibilidade | Decisao automatica sem rastreabilidade. | Services deterministicos, snapshots e testes. |
| Scoring | Ordenacao instavel ou empate nao deterministico. | Regras explicitas, indices e testes de ranking. |
| RGPD | Exportacao ou anonimizacao sem auditoria. | Policies, logs minimizados e testes de fluxo. |
| Deploy | Migrations destrutivas ou cache desatualizada. | Backup, `migrate --force`, `optimize:clear` e rollback documentado. |
| IA documental | Classificacao incorreta interpretada como decisao final. | IA apenas como apoio, revisao humana obrigatoria. |

