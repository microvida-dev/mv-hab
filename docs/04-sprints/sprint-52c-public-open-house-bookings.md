# Sprint 52C — Marcações públicas de visitas / Open House

## Objetivo

Separar as visitas públicas do domínio legacy de visitas do candidato. A nova
marcação não exige registo, autenticação, candidatura ou agregado e fica ligada
a um fogo público e a um `VisitSlot` municipal.

## Arquitetura

- `public_visit_bookings`: domínio dedicado, sem `candidate_user_id` ou
  `application_id`;
- `VisitSlot`: agenda e capacidade partilhadas com o backoffice;
- `PublicVisitBookingService`: locks, capacidade, duplicados, cancelamento e
  transições;
- `PublicVisitChallengeService`: integração Turnstile fail-closed quando ativa;
- `DeliverPublicVisitBookingConfirmation`: entrega em fila sem guardar o token
  em payloads de queue;
- `PublicVisitBookingRetentionService`: anonimização por prazo;
- `PublicVisitBookingAuditService`: auditoria sem IP, user-agent, email, nome ou
  telefone.

## Segurança e RGPD

- nome, email, telefone, token recuperável e notas são cifrados por cast
  Eloquent;
- email e deduplicação usam HMAC não reversível;
- o token público é guardado por hash para lookup e cifrado apenas enquanto a
  confirmação aguarda entrega;
- não são persistidos IP, user-agent, cookies ou payloads;
- a consulta backoffice de contactos cria evento de auditoria;
- o formulário recolhe a aceitação da informação de privacidade, sem usar consentimento como base técnica implícita;
- os dados pessoais são anonimizados após o prazo configurado;
- a tabela não permite eliminação física através do modelo.

## Concorrência

A criação bloqueia o `VisitSlot` com `lockForUpdate`, volta a validar
publicação, Município, disponibilidade e capacidade e só depois incrementa
`booked_count`. O cancelamento mantém a mesma ordem de lock e devolve a
capacidade. O fingerprint único impede duas reservas ativas do mesmo email no
mesmo horário.

## Configuração

Variáveis suportadas:

```text
MVHAB_PUBLIC_VISITS_ENABLED=true
MVHAB_PUBLIC_VISITS_QUEUE=communications
MVHAB_PUBLIC_VISITS_MAX_GUESTS=6
MVHAB_PUBLIC_VISITS_RATE_LIMIT_ATTEMPTS=5
MVHAB_PUBLIC_VISITS_RATE_LIMIT_DECAY=600
MVHAB_PUBLIC_VISITS_CANCELLATION_CUTOFF_MINUTES=60
MVHAB_PUBLIC_VISITS_RETENTION_MONTHS=6
MVHAB_PUBLIC_VISITS_PRIVACY_NOTICE_VERSION=2026-07-30
MVHAB_PUBLIC_VISITS_TURNSTILE_ENABLED=false
MVHAB_PUBLIC_VISITS_TURNSTILE_SITE_KEY=
MVHAB_PUBLIC_VISITS_TURNSTILE_SECRET_KEY=
MVHAB_PUBLIC_VISITS_TURNSTILE_VERIFY_URL=https://challenges.cloudflare.com/turnstile/v0/siteverify
MVHAB_PUBLIC_VISITS_TURNSTILE_TIMEOUT=5
```

## Operação

Executar periodicamente:

```bash
php artisan public-visits:anonymize --limit=500
```

O worker deve consumir a fila configurada em
`MVHAB_PUBLIC_VISITS_QUEUE`.

## Compatibilidade

- as rotas candidate legacy permanecem no repositório atrás de
  `candidate.feature:legacy_visits` e desligadas por defeito;
- `housing_visits` não é usado pelo novo fluxo;
- não existem alterações ao processo de candidatura;
- as permissions backoffice existentes de visitas são reutilizadas.

## Gates de deployment

Antes de disponibilizar marcações reais:

- configurar o mailer e o worker da fila `communications`;
- ativar Turnstile e instalar chaves por ambiente, salvo decisão formal de usar outro challenge compatível;
- publicar e versionar a informação de privacidade aplicável;
- configurar a execução periódica de `public-visits:anonymize`;
- confirmar o prazo de retenção aprovado pelo Município/DPO;
- monitorizar `confirmation_failed_at` no backoffice e `failed_jobs`.
