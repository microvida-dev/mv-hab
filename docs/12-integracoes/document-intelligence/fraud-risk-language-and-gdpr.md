# Linguagem de risco, RGPD e auditoria

## Princípio

A plataforma deve falar em indicadores de risco, divergências e necessidade de revisão manual. Não deve declarar fraude automaticamente.

## RGPD

- Minimizar logs e eventos.
- Não copiar OCR bruto ou JSON bruto para audit logs.
- Não expor paths internos.
- Não duplicar dados extraídos sensíveis em tabelas de score.
- Manter acesso backoffice protegido por policy.

## Auditoria

Eventos auditados:

- início do cálculo de score;
- score calculado;
- falha controlada;
- consulta do assistente;
- edição, aceitação e descarte de sugestões.

## Decisão humana

O score IA nunca substitui a análise municipal. Qualquer exclusão, pedido de aperfeiçoamento, decisão administrativa ou alteração de estado deve continuar a passar por fluxo técnico autorizado.
