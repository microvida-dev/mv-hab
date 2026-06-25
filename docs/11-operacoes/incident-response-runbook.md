# Incident Response Runbook

## Objetivo

Normalizar resposta a incidentes no piloto municipal controlado da MV HAB.

## Severidades

| Severidade | Criterio | Tempo alvo inicial |
| --- | --- | --- |
| SEV1 | Exposicao documental, perda de dados, login/backoffice indisponivel, lista/contrato/renda com impacto juridico | 30 minutos |
| SEV2 | Fluxo critico degradado, upload documental falha, job critico falhado, export sensivel indevido bloqueado | 2 horas |
| SEV3 | Funcionalidade nao critica indisponivel, erro de dashboard, SLA operacional vencido | 1 dia util |
| SEV4 | Pedido informativo, melhoria documental, duvida de utilizador | 5 dias uteis |

## Playbooks

### Login indisponivel

1. Confirmar ambiente, commit e hora.
2. Validar `php artisan route:list --except-vendor`.
3. Validar logs locais sem copiar dados pessoais.
4. Confirmar sessoes e MFA.
5. Executar smoke de login com conta piloto ficticia.
6. Registar incidente e acao tomada.

### Backoffice indisponivel

1. Confirmar autenticacao e middleware.
2. Validar `mvhab:operations:health`.
3. Confirmar DB/cache/rotas.
4. Acionar rollback se a indisponibilidade for pos-deploy.

### Upload documental falha

1. Confirmar storage privado gravavel.
2. Validar tamanho/tipo do ficheiro.
3. Confirmar queue se processamento assicrono estiver envolvido.
4. Nao copiar documentos para tickets externos.

### Documento privado exposto

1. Classificar como SEV1.
2. Remover exposicao publica ou colocar maintenance mode se necessario.
3. Preservar evidencia tecnica sanitizada.
4. Notificar responsavel municipal e DPO/juridico.
5. Auditar acessos afetados.
6. Preparar comunicacao formal se aplicavel.

### Job falhado

1. Executar `php artisan queue:failed`.
2. Confirmar idempotencia.
3. Corrigir causa antes de `queue:retry`.
4. Nao apagar evidencia sem registo.

### IA documental indisponivel

1. Confirmar que upload documental nao ficou bloqueado.
2. Marcar analise como manual quando aplicavel.
3. Registar risco operacional.

### Candidatura bloqueada

1. Validar estado da candidatura.
2. Confirmar documentos, aperfeicoamento e locks.
3. Nao alterar elegibilidade/scoring sem justificação e auditoria.

### Visita ou ticket com SLA vencido

1. Rever Work Tasks vencidas.
2. Reatribuir com justificação quando necessario.
3. Comunicar ao responsavel de equipa.

### Export sensivel indevido

1. Revogar acesso ao ficheiro privado se aplicavel.
2. Auditar downloads.
3. Notificar DPO/juridico.
4. Rever permissoes.

### Erro em lista provisoria/definitiva

1. Bloquear nova publicacao se necessario.
2. Preservar snapshots.
3. Registar analise juridica.
4. Reprocessar apenas por fluxo auditavel.

### Erro em contrato/renda manual

1. Validar contrato/renda afetada.
2. Preservar historico.
3. Corrigir apenas por fluxo administrativo com auditoria.

### Suspeita de acesso indevido

1. Consultar access logs e sensitive access logs.
2. Revogar sessoes se necessario.
3. Rever roles/equipas.
4. Notificar DPO/juridico quando aplicavel.

### Necessidade de rollback

1. Entrar em maintenance mode.
2. Registar commit atual.
3. Seguir `docs/11-operacoes/rollback-runbook.md`.
4. Executar smoke tests pos-rollback.

## Comunicacao

- nunca enviar segredos ou documentos privados por canais externos;
- usar Work Task/incidente interno;
- dossier municipal deve conter apenas evidencia sanitizada.
