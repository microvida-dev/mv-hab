# Document Intelligence — Segurança e RGPD da Validação

## Princípios

- A validação IA é evidência auxiliar.
- A decisão final permanece humana e municipal.
- Payloads de eventos não incluem valores pessoais.
- Logs técnicos registam IDs, contadores, grupos e chaves, não OCR bruto.
- Valores comparados podem ser guardados conforme configuração, mas são também guardados em hash para auditoria técnica.

## Dados sensíveis

`document_ai_validations` guarda:

- valor declarado e valor extraído quando `DOCUMENT_AI_VALIDATION_STORE_PLAIN_VALUES=true`;
- hashes SHA-256 quando `DOCUMENT_AI_VALIDATION_HASH_VALUES=true`;
- metadados sobre sensibilidade, rendimento e saúde;
- estado, severidade, mensagem e recomendação.

## Mascaramento

O painel usa `DocumentValidationValuePresenter`:

- dados sensíveis ficam mascarados para operadores sem permissão de auditoria;
- dados de saúde ficam ocultos para operadores sem permissão de auditoria ou privacidade;
- candidatos não têm acesso ao painel.

## Auditoria

São auditados:

- início da validação;
- conclusão da validação;
- falha controlada;
- consulta do painel;
- consulta de detalhe;
- marcação manual de revisão.

## Riscos e mitigação

| Risco | Mitigação |
| --- | --- |
| Falso positivo de divergência | Severidade e revisão manual obrigatória; sem decisão automática. |
| Exposição indevida de dados | Policies, backoffice protegido, presenter com mascaramento e eventos minimizados. |
| Uso de dados de saúde | Flag `health_data`, ocultação por permissão e auditoria de acesso. |
| Reprocessamento sem controlo | Form Request, policy e audit log. |
| Alteração indevida do processo | Pipeline não escreve em candidatura, elegibilidade, pontuação ou workflow. |
