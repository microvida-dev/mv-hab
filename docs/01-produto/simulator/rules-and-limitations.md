# Regras e Limitações do Simulador

## Regras

- O simulador usa apenas dados declarados ou dados já existentes do candidato autenticado.
- A estimativa de renda usa regra de concurso/programa quando existir; caso contrário usa taxa de esforço configurável.
- A recomendação de tipologia usa regras de adequação quando existirem; caso contrário aplica uma recomendação conservadora por dimensão do agregado.
- Os impedimentos assinalados são avisos ou bloqueios indicativos para preparação da candidatura.
- Concursos recomendados são apenas concursos publicamente visíveis.

## Limitações

- Não executa decisão formal de elegibilidade.
- Não calcula classificação, ranking, sorteio ou atribuição.
- Não substitui validação documental.
- Não cria candidatura submetida.
- Não garante renda contratual.
- Não integra AT, Segurança Social, IRN, Autenticação.gov, OCR ou assinatura digital.

## Configuração

O backoffice pode consultar insights em `/backoffice/simulator/insights` e editar parâmetros gerais em `/backoffice/simulator/configuration`.

Parâmetros disponíveis:

- ativação global;
- ativação do simulador público;
- ativação do simulador autenticado;
- número máximo de concursos recomendados;
- taxa de esforço padrão;
- retenção de sessões.

