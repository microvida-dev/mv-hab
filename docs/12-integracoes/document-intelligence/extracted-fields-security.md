# Document Intelligence — Segurança dos Campos Extraídos

## Classificação de dados

Campos extraídos podem conter dados pessoais, dados financeiros e dados de saúde. Por defeito, a interface administrativa trata os campos como sensíveis.

Dados de saúde existem especialmente em `atestado_multiusos` e exigem permissão mais restrita.

## Acesso

- Candidatos não acedem a resultados de extração IA.
- Backoffice precisa de `documents.view` ou `documents.audit` para ver o painel.
- Valores sensíveis são mascarados quando o utilizador não tem permissão de auditoria documental.
- Dados de saúde ficam ocultos sem permissão de auditoria/RGPD compatível.
- Ações de marcação manual de revisão exigem permissão de atualização, aprovação ou auditoria documental.

## Logs e eventos

Auditoria, processing logs e eventos registam apenas metadados:

- IDs da análise/documento;
- tipo documental;
- estado;
- confiança;
- contagens;
- códigos de flag.

Não são enviados valores extraídos, texto OCR, JSON bruto ou paths internos.

## Retenção

A retenção específica de `ocr_text`, `raw_ai_json`, `extraction_json` e `document_ai_fields` deve ser validada pelo DPO antes de produção.
