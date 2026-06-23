# Document Intelligence — Segurança e RGPD

## Documentos privados

O módulo reutiliza o storage privado existente. Não cria downloads por path, rotas públicas ou exposição direta de ficheiros.

## Dados pessoais

Documentos submetidos podem conter dados pessoais e dados sensíveis. A Sprint 27 trata `raw_text` e `raw_ai_json` como informação sensível.

## `raw_text` e `ocr_text`

Na Sprint 28, o OCR pode guardar texto documental em `raw_text` e `ocr_text`. Estes campos são tratados como sensíveis, não são expostos ao candidato e só aparecem no backoffice para perfis com permissão de auditoria documental.

## `raw_ai_json`

O JSON bruto é guardado na tabela de análise e não é exposto ao candidato. A auditoria não copia o JSON bruto nem o texto OCR.

## Auditoria

São auditados:

- criação de análise pendente;
- início de processamento;
- conclusão;
- falha controlada;
- revisão manual;
- criação de flags técnicas.
- início/conclusão/falha de OCR;
- início/conclusão/falha de classificação;
- marcação manual de revisão no painel backoffice;
- consulta do detalhe de classificação no backoffice.

Os eventos de auditoria registam identificadores, estado e códigos técnicos. Não registam texto integral do documento, NIF, IBAN, moradas completas, contactos ou JSON bruto.

## Logs minimizados

`document_ai_processing_logs.context` aceita apenas metadados técnicos minimizados, como:

- ID da submissão documental;
- ID da versão documental;
- MIME type;
- tamanho;
- presença de checksum;
- estado;
- código técnico de falha;
- contagem de fields/flags.

## Limites de acesso

A interface administrativa da Sprint 28 usa `DocumentAiAnalysisPolicy`. Candidatos não acedem ao painel. Técnicos veem metadados e classificação. O texto OCR fica oculto por defeito e requer permissão de auditoria documental.

## Revisão manual

Flags e estados `manual_review` apoiam o técnico municipal, mas não decidem nem invalidam documentos automaticamente.

## Princípios RGPD aplicados na Sprint 28

- minimização em logs e auditoria;
- ausência de APIs pagas/externas;
- storage privado já existente;
- nenhuma alteração automática a candidatura, elegibilidade ou validação documental;
- fixtures de teste sem dados pessoais reais;
- revisão humana obrigatória quando a confiança é baixa, quando OCR falha ou quando a classificação é `outro`.
