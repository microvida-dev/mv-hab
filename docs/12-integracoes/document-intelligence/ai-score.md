# Score IA documental

## Objetivo

O score IA documental atribui uma pontuação de 0 a 100 a cada `document_ai_analysis`, com base em sinais já produzidos pelas Sprints 27 a 30. O score apoia a revisão técnica municipal e não produz decisão automática.

## Componentes

Os pesos ficam configurados em `config/document-ai-score.php`:

| Componente | Peso |
| --- | ---: |
| OCR | 20 |
| Classificação | 20 |
| Campos extraídos | 20 |
| Consistência com candidatura | 25 |
| Flags e risco documental | 15 |

As penalizações por indicador de risco também são configuráveis.

## Labels

| Intervalo | Label |
| --- | --- |
| 90-100 | `muito_confiavel` |
| 75-89 | `confiavel_com_atencao` |
| 60-74 | `requer_revisao` |
| 40-59 | `baixa_confianca` |
| 0-39 | `critico` |

## Segurança

- O score nunca altera candidatura, elegibilidade, ranking, listas, contratos ou workflow.
- O job `CalculateDocumentAiScoreJob` recebe apenas o ID da análise.
- O score guarda componentes, explicação e resumo, mas não duplica OCR bruto nem JSON bruto da IA.
- A decisão final permanece sempre com os técnicos municipais.
