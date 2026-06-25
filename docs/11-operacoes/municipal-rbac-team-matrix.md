# Municipal RBAC and Team Matrix

## Objetivo

Documentar a matriz de perfis, equipas municipais e competencias funcionais usada no staging municipal controlado da MV HAB.

Esta matriz preserva roles existentes e evita criar perfis paralelos para inquilino. A area do inquilino e protegida por ownership, estado contratual e policies.

## Roles preservadas

| Role | Perfil funcional | MFA | Observacoes |
| --- | --- | --- | --- |
| administrator | administracao global | obrigatorio | unico perfil com permissao total |
| municipal_technician | tramitacao tecnica | obrigatorio | candidaturas, documentos, elegibilidade, scoring e listas |
| jury | juri de concurso | obrigatorio | aprovacao/rejeicao e consulta do procedimento |
| legal_manager | juridico | obrigatorio | contratos, reclamacoes, audiencia e validacao juridica |
| financial_manager | financeiro | obrigatorio | rendas, pagamentos administrativos e reporting financeiro |
| housing_manager | habitacao | obrigatorio | fogos, atribuicoes, ocupacao e visitas |
| maintenance_manager | manutencao | recomendado | manutencao, vistorias tecnicas e dashboards operacionais |
| inspection_manager | vistorias | obrigatorio | vistorias, autos e evidencias tecnicas |
| support_agent | atendimento | recomendado | tickets, FAQ e apoio ao candidato |
| candidate | candidato/inquilino funcional | nao obrigatorio | acesso apenas aos proprios recursos |
| auditor | auditoria | obrigatorio | leitura/auditoria sem mutacao |

## Equipas municipais

| Equipa | Escopos | Perfis naturais |
| --- | --- | --- |
| Gabinete Tecnico | candidaturas, documentos, elegibilidade | municipal_technician |
| Gabinete Juridico | contratos, reclamacoes, audiencia | legal_manager, jury |
| Gabinete Financeiro | rendas e pagamentos administrativos | financial_manager |
| Gabinete de Habitacao | fogos, atribuicoes, ocupacao | housing_manager |
| Manutencao | pedidos e intervencoes | maintenance_manager |
| Vistorias | vistorias e autos | inspection_manager |
| Atendimento | apoio ao candidato, tickets, FAQ | support_agent |
| Auditoria | auditoria, RGPD, acessos | auditor, administrator |

## Restrições negativas

- support_agent nao consulta documentos sensiveis sem permissao propria.
- financial_manager nao altera scoring nem publica listas.
- maintenance_manager nao consulta rendimentos nem documentos do candidato por defeito.
- legal_manager nao gere pagamentos nem roles.
- auditor consulta mas nao altera.
- candidate nao entra no backoffice nem ve Work Tasks.
- roles e equipas so sao geridas por administrator.

## Work Tasks por competencia

| Tipo | Equipa | Perfis |
| --- | --- | --- |
| document_review | Gabinete Tecnico | municipal_technician |
| complaint_review | Gabinete Juridico | legal_manager, jury |
| hearing_review | Gabinete Juridico | legal_manager |
| contract_review | Gabinete Juridico / Gabinete de Habitacao | legal_manager, housing_manager |
| rent_review | Gabinete Financeiro | financial_manager |
| maintenance_triage | Manutencao | maintenance_manager |
| inspection_schedule | Vistorias | inspection_manager |
| visit_schedule | Atendimento / Gabinete de Habitacao | support_agent, housing_manager |
| support_ticket | Atendimento | support_agent |
| rgpd_request | Auditoria | auditor, administrator |

## Controlo operacional

- toda alteracao critica exige justificacao;
- role escalation e self-promotion ficam bloqueados por policy/service;
- ultimo administrator ativo deve ser protegido;
- MFA deve manter-se em perfis sensiveis;
- auditoria de roles/equipas deve ser revista periodicamente.
