# Document Intelligence — Classificação Automática

## Pipeline

1. Extrair texto por OCR.
2. Classificar por palavras-chave.
3. Extrair sinais de layout.
4. Pedir classificação JSON ao Ollama local quando ativado.
5. Combinar sinais num score final.
6. Guardar classificação, confiança, sinais, JSON bruto minimizado e flags.

## Prompt IA local

O prompt usa a instrução:

```text
Classifique este documento numa das categorias suportadas e devolva apenas JSON.
```

O formato esperado é JSON com `document_type`, `label`, `confidence` e `reason`.

## Score

O score combina fontes determinísticas e IA local. O sistema não depende exclusivamente da IA.

| Fonte | Papel |
| --- | --- |
| OCR | texto base |
| Palavras-chave | sinais determinísticos |
| Layout | padrões estruturais, como IBAN, cláusulas, percentagens ou modelos fiscais |
| IA local | desempate e ambiguidade quando ativa |

## Limites

A classificação não altera candidatura, validação documental, elegibilidade, pontuação, ranking, listas ou decisões administrativas.
