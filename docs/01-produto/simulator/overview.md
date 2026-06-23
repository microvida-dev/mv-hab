# Simulador Avançado

O simulador avançado permite ao cidadão obter uma leitura indicativa antes de iniciar ou atualizar uma candidatura. A experiência existe em dois contextos:

- público anónimo em `/simulador`;
- área do candidato em `/area-candidato/simulacoes`.

A simulação cruza dados mínimos de agregado, rendimento, situação habitacional, preferências, impedimentos declarados, concursos publicados, tipologia recomendada e estimativa de renda. O resultado nunca constitui decisão administrativa.

Mensagem obrigatória apresentada ao utilizador:

> A simulação apresentada é meramente indicativa e não substitui a análise formal dos serviços municipais. A elegibilidade, tipologia, renda e possibilidade de candidatura dependem da validação dos dados, documentos e regras aplicáveis ao concurso.

## Componentes implementados

- `simulation_sessions`
- `simulation_input_snapshots`
- `simulation_results`
- `simulation_impediments`
- `simulation_recommended_contests`
- `candidate_data_reuse_profiles`
- `application_prefills`
- `registration_renewals`
- `simulator_configurations`

## Fluxo

1. O utilizador preenche dados mínimos.
2. A plataforma cria uma sessão de simulação com hashes de IP/user-agent.
3. A plataforma calcula completude, tipologia, estimativa de renda e impedimentos.
4. A plataforma recomenda concursos publicados compatíveis.
5. O candidato autenticado pode guardar a simulação e criar pré-preenchimento.
6. A candidatura formal continua dependente do fluxo próprio de candidatura.

