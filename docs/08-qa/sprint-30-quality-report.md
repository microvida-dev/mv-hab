# Sprint 30 — Relatório de Qualidade

## Âmbito

Validação automática assistida por IA entre dados declarados na candidatura e campos estruturados extraídos dos documentos.

## Cobertura criada

- `tests/Unit/DocumentIntelligence/DocumentValidationServicesTest.php`
- `tests/Feature/DocumentIntelligence/DocumentCandidateValidationPipelineTest.php`
- `tests/Feature/Backoffice/DocumentAiValidationPanelTest.php`

## Verificações cobertas

- comparação por nome aproximado, NIF normalizado e valores monetários;
- regra crítica para rendimento documental superior ao declarado;
- registry de regras para contrato de arrendamento;
- criação de run, validações, flags, audit logs e eventos;
- queue fake após extração estruturada;
- garantia de que o estado da candidatura não é alterado;
- bloqueio de guest/candidato no painel de backoffice;
- mascaramento de dados sensíveis para técnico sem permissão de auditoria;
- marcação manual de revisão.

## Resultados dos comandos

| Comando | Resultado |
| --- | --- |
| `php artisan migrate` | OK; migration `2026_06_22_000030_create_document_ai_validation_tables` aplicada. |
| `php artisan route:list` | OK; 1078 rotas listadas. |
| `composer validate --no-check-publish` | OK; `composer.json` válido. |
| `php artisan test` | Falhou por limite de memória PHP de 128 MB durante testes legados de ficheiros/exportações; antes da falha reportou testes a passar. |
| `php -d memory_limit=512M vendor/bin/phpunit` | OK; 264 testes / 1640 asserções. |
| `./vendor/bin/pint --test` | OK. |
| `npm run build` | OK. |
| `./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M --error-format=table` | Falhou com 2890 erros PHPStan. A lista é dominada por dívida Larastan/tipagem preexistente; foi identificado e corrigido um aviso em ficheiro da Sprint 30 após a tentativa única global. |

## Riscos residuais

- As regras atuais são determinísticas e configuráveis, mas devem ser afinadas com amostras reais anonimizadas.
- A precisão depende da qualidade do OCR e da extração estruturada.
- A validação de número de membros/dependentes fica dependente de evidência documental estruturada adicional.
- A Sprint 30 não decide elegibilidade, classificação ou exclusão.

## Estado PHPStan

- Tentativa global realizada uma vez com `phpstan.neon`.
- Resultado global: 2890 erros.
- Erros observados concentram-se em `argument.type`, `propertyType`, generics de coleções, relações Eloquent e checks estáticos já conhecidos.
- Um aviso em `DocumentAiValidationController` foi corrigido depois da tentativa global.
- Não foi repetida a análise global para respeitar a regra de tentativa única da sprint.
