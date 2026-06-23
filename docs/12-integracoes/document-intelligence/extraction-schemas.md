# Document Intelligence — Schemas de Extração

## Tipos suportados na Sprint 29

| Tipo documental | Campos extraídos |
| --- | --- |
| Cartão de Cidadão | nome, data de nascimento, sexo, nacionalidade, número de documento, validade, NIF quando presente |
| Título de Residência | nome, número, validade, nacionalidade |
| IRS | ano fiscal, sujeito passivo, NIF, rendimento global, rendimento coletável |
| Nota de Liquidação | ano, total de rendimento, estado |
| Recibo de vencimento | entidade patronal, trabalhador, salário base, ilíquido, líquido |
| Declaração Segurança Social | beneficiário, número, prestação, valor |
| Contrato de arrendamento | senhorio, inquilino, morada, renda, data início, data fim |
| Atestado Multiusos | grau de incapacidade, data de emissão, entidade, resultado |

## Normalização

- Datas: normalizadas para `YYYY-MM-DD`.
- Valores monetários: normalizados para decimal com duas casas.
- Percentagens: normalizadas para intervalo 0-100.
- NIF e identificadores: normalizados por remoção de espaços e caracteres redundantes.
- Moradas: preservadas como texto normalizado, sem geocoding.

## Confiança

Campos extraídos por regex recebem confiança alta quando o rótulo é identificado e normalizado sem ambiguidade. Campos obrigatórios ausentes, valores ambíguos ou confiança inferior ao limiar configurado geram flags e revisão manual.

## Versionamento

A versão ativa do schema fica em `config/document-ai-extraction.php` e é persistida em `extraction_schema_version` e `extraction_json.schema_version`.
