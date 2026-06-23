# Checklist local — demonstração de Arrendamento Acessível de Alcanena

## Objetivo

Validar localmente a configuração criada por `DemoAlcanenaAffordableRentSeeder`, sem usar dados pessoais reais. A partir da Sprint 32, o seeder deixa o programa e o concurso em estado publicado para permitir demonstração pública controlada.

## Referências

- Regulamento Municipal de Arrendamento Acessível de Alcanena, Edital n.º 1820/2024.
- Manual de Concursos de Habitação Acessível fornecido com o projeto.
- Portaria n.º 175/2019, na redação da Portaria n.º 52/2024.
- Decreto-Lei n.º 139/2025 para a RMMG continental de 2026.

Os parâmetros legais devem ser novamente confirmados pelo Município antes de um concurso real.

## Preparação

- Confirmar que o ambiente é local ou de testes.
- Não usar candidatos, documentos, NIF, contactos ou moradas reais.
- Executar apenas migrations incrementais, nunca `migrate:fresh` sobre uma base com dados úteis.

```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\DemoAlcanenaAffordableRentSeeder
php artisan test tests/Feature/DemoAlcanenaAffordableRentSeederTest.php
```

O seeder é idempotente e pode ser repetido.

## Resultado esperado

- Município: `Município de Alcanena`.
- Programa: `Programa Municipal de Arrendamento Acessível de Alcanena`.
- Concurso: `ALC-RAA-01-2026`.
- Estado do programa e concurso: `published`.
- Período fictício: 1 de junho a 31 de dezembro de 2026.
- 5 prazos processuais: candidaturas, aperfeiçoamento, reclamações, audiência e aceitação da atribuição.
- 3 utilizadores fictícios de júri com emails `example.test` e sem password divulgada.
- 4 utilizadores demo controlados com domínio `@exemplo.pt` e password temporária `password`.
- 22 critérios de elegibilidade, dos quais 7 exigem validação manual.
- 4 critérios de classificação, 18 regras de escala e 4 desempates.
- 11 requisitos documentais.
- 4 habitações fictícias públicas: T1 Alcanena Centro, T2 Alcanena, T3 Minde e T2 Monsanto.
- 1 configuração de workflow administrativo para aperfeiçoamento e aprovação de decisão.
- Método de atribuição: ranking, preferências e sorteio apenas para empates remanescentes.
- 1 regra de renda/caução para teste da taxa de esforço de 35%.
- 1 minuta contratual, 5 cláusulas obrigatórias e modelos demo sujeitos a validação jurídica.
- 4 templates de comunicação e 4 modelos documentais demo com versões ativas.

## Validação no backoffice

- Abrir o programa e confirmar objeto, âmbito, tipo, regime, duração, taxa de esforço e publicitação.
- Abrir o concurso e confirmar que está publicado apenas para o ambiente de demonstração.
- Confirmar os prazos processuais demo e os três membros fictícios do júri.
- Rever as regras de elegibilidade e confirmar que os impedimentos do artigo 9.º não são aprovados automaticamente.
- Rever a matriz e confirmar os pesos `30%`, `40%`, `20%` e `10%`.
- Confirmar a ordem de desempate: idade, qualificação, dependentes e deficiência.
- Rever a checklist e confirmar os documentos condicionais.
- Confirmar rendas fictícias de `320 EUR`, `390 EUR`, `470 EUR` e `410 EUR`.
- Confirmar configuração de renda/caução, minuta contratual, cláusulas, templates de comunicação e modelos documentais.
- Confirmar que todas as moradas têm indicação explícita de conteúdo fictício.

## Credenciais demo controladas

Usar apenas em ambiente local ou demo controlado:

| Perfil | Email | Password |
| --- | --- | --- |
| Administrador | `admin-demo@exemplo.pt` | `password` |
| Técnico municipal | `tecnico-demo@exemplo.pt` | `password` |
| Júri | `juri-demo@exemplo.pt` | `password` |
| Candidato | `candidato-demo@exemplo.pt` | `password` |

Estas credenciais são temporárias e não devem ser usadas em produção.

## Simulação do ciclo

- Criar uma conta candidata pelo fluxo normal da aplicação.
- Finalizar o Registo de Adesão com dados inteiramente fictícios.
- Adicionar agregado, qualificação QNQ, rendimentos e situação habitacional.
- Confirmar que gravidez, deficiência/multideficiência e dispensa de IRS ativam apenas os documentos correspondentes.
- Confirmar que o programa e o concurso já ficam publicados pelo seeder apenas para demonstração controlada.
- Iniciar uma candidatura e escolher uma ou mais habitações adequadas.
- Confirmar que uma renda superior a 35% do rendimento mensal bloqueia a elegibilidade automática.
- Confirmar que tipologia incompatível bloqueia a regra de adequação.
- Submeter e validar os documentos fictícios.
- Executar a verificação formal de elegibilidade.
- Resolver manualmente os sete impedimentos legais com evidência fictícia.
- Admitir a candidatura para classificação.
- Executar a classificação e validar a matriz do Anexo I.
- Gerar lista provisória, simular reclamação/audiência e gerar lista definitiva.
- Executar a atribuição e confirmar preferências e desempate por sorteio quando aplicável.

## Limites da demonstração

- Não são criadas contas de candidato ou candidaturas automaticamente.
- São criadas apenas contas de júri fictícias, com emails reservados `example.test` e passwords aleatórias não divulgadas.
- Não existe consulta automática à AT, Segurança Social ou cadastro predial.
- Fraude, dívidas, apoios incompatíveis e incumprimentos anteriores exigem decisão humana.
- Os valores legais e as rendas fictícias não constituem edital, proposta contratual ou decisão administrativa.
- A publicação real exige validação jurídica, DPO, aprovação municipal e revisão dos parâmetros em vigor.
