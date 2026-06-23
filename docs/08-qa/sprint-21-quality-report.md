# Relatório de Qualidade — Sprint 21

## Implementado

- Simulador público anónimo.
- Simulador autenticado na área do candidato.
- Persistência de sessões, snapshots, resultados, impedimentos e recomendações.
- Tipologia recomendada.
- Estimativa indicativa de renda.
- Recomendações de concursos publicados.
- Pré-preenchimento de candidatura a partir de simulação autenticada.
- Perfil de reutilização de dados do candidato.
- Renovação simplificada de Registo de Adesão.
- Insights e configuração backoffice.
- Policies, Form Requests, factories, seeder e testes.

## Testes executados inicialmente para a sprint

```bash
php artisan test tests/Feature/Public/AdvancedSimulatorTest.php tests/Feature/Candidate/CandidateSimulationTest.php tests/Feature/Candidate/ApplicationPrefillTest.php tests/Feature/Candidate/RegistrationRenewalTest.php tests/Feature/Security/SimulatorPrivacyTest.php tests/Unit/Simulator
```

Resultado: 11 testes, 31 asserções, OK.

## PHPStan

O PHPStan inicial confirmou o passivo conhecido de 2471 erros legados. A sprint deve ser aceite apenas se a análise final não introduzir erros novos nos ficheiros criados ou alterados.

## Riscos residuais

- As regras de tipologia/renda são indicativas e dependem da configuração municipal.
- O pré-preenchimento não substitui validação documental nem submissão formal.
- A retenção operacional das simulações deve ser validada com DPO/serviços municipais.

