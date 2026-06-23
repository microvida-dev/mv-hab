# Convocatórias de sorteio

## Objetivo

Gerar e gerir convocatórias associadas a sorteios, participantes, candidaturas e candidatos.

## Regras

- Cada convocatória tem data, hora, local, instruções e estado.
- Não são criadas convocatórias duplicadas ativas para a mesma candidatura e sorteio.
- Candidato só consulta convocatórias próprias.
- Envio real externo não foi implementado; o estado `sent` é marcação interna auditável.

Texto obrigatório:

> A convocatória indica a data, hora, local e instruções do ato. A falta de comparência pode produzir efeitos no procedimento, nos termos aplicáveis ao concurso.

## Estados

`draft`, `generated`, `sent`, `delivered`, `read`, `failed`, `cancelled`, `expired`.
