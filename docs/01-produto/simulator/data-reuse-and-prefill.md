# Reutilização de Dados e Pré-preenchimento

O Sprint 21 introduz perfis de reutilização de dados para reduzir repetição de preenchimento pelo candidato.

## Perfil de reutilização

`candidate_data_reuse_profiles` guarda snapshots dos dados confirmados pelo candidato:

- registo de adesão;
- agregado;
- rendimentos;
- situação habitacional;
- estado documental resumido.

Os snapshots são privados, associados ao utilizador e expiram por configuração operacional.

## Pré-preenchimento

`application_prefills` permite transformar uma simulação autenticada num pacote de dados para rascunho de candidatura.

O utilizador deve:

1. confirmar que reviu os dados;
2. aplicar o pacote a uma candidatura em rascunho;
3. continuar a revisão formal da candidatura.

O sistema não copia documentos, declarações finais nem decisões formais. O pré-preenchimento nunca submete uma candidatura.

