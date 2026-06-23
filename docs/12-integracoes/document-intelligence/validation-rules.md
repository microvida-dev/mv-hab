# Document Intelligence — Regras de Validação

## Identificação

| Documento | Campo | Comparação | Severidade base |
| --- | --- | --- | --- |
| Cartão de Cidadão | Nome | Nome aproximado normalizado | Crítica |
| Cartão de Cidadão | NIF | Exata normalizada | Crítica |
| Cartão de Cidadão | Data nascimento | Data ISO | Crítica |
| Cartão de Cidadão | Número documento | Exata normalizada | Média |
| Título de Residência | Nome | Nome aproximado normalizado | Crítica |
| Título de Residência | Número | Exata normalizada | Média |
| Título de Residência | Nacionalidade | Exata normalizada | Média |

## Rendimentos

| Documento | Campo | Comparação | Severidade base |
| --- | --- | --- | --- |
| IRS | Rendimento global | Tolerância monetária anual | Crítica |
| Nota de Liquidação | Total rendimento | Tolerância monetária anual | Crítica |
| Recibo de vencimento | Ilíquido | Tolerância monetária mensal | Crítica |
| Recibo de vencimento | Líquido | Tolerância monetária mensal | Média |
| Segurança Social | Valor | Tolerância monetária mensal | Média |

## Habitação

| Documento | Campo | Comparação | Severidade base |
| --- | --- | --- | --- |
| Contrato de arrendamento | Inquilino | Nome aproximado normalizado | Crítica |
| Contrato de arrendamento | Morada | Similaridade de morada | Média |
| Contrato de arrendamento | Renda | Tolerância monetária mensal | Média |
| Comprovativo de morada | Morada | Similaridade de morada | Média |

## Agregado e saúde

| Documento | Campo | Comparação | Severidade base |
| --- | --- | --- | --- |
| Atestado Multiusos | Grau de incapacidade | Consistência documental | Média |

## Tolerâncias configuráveis

Configuração em `config/document-ai-validation.php`:

- `name_similarity_match`
- `name_similarity_partial`
- `money_light_tolerance_percent`
- `money_medium_tolerance_percent`
- `critical_income_difference_percent`
- `address_similarity_match`
- `address_similarity_partial`

As tolerâncias servem apenas para classificar risco de revisão. Não geram decisão automática.
