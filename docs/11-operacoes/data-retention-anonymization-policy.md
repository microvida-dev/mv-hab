# Data Retention and Anonymization Policy

## Objetivo

Definir politica tecnica preliminar de retencao e anonimizacao para validacao municipal/juridica e DPO.

## Retencao por dominio

| Dominio | Prazo indicativo | Acao final | Validacao |
| --- | --- | --- | --- |
| candidaturas | Conforme arquivo administrativo aplicavel | reter/arquivar/anonimizar | DPO/Juridico |
| documentos | Conforme procedimento e obrigacoes legais | reter ou eliminar ficheiro privado | DPO/Juridico |
| contratos | Conforme obrigacoes contratuais e arquivo | reter/arquivar | DPO/Juridico |
| rendas | Conforme obrigacoes financeiras | reter/arquivar | DPO/Juridico |
| logs de acesso | Prazo limitado e justificado | anonimizar/agregar | DPO/Juridico |
| tickets | Prazo operacional limitado | anonimizar ou arquivar | DPO/Juridico |
| auditoria | Prazo compativel com prova administrativa | reter minimizado | DPO/Juridico |
| backups | Retencao curta e cifrada | rotacao segura | Responsavel tecnico |

## Anonimizacao

Campos a anonimizar quando juridicamente permitido:

- nome;
- email;
- telefone;
- NIF;
- morada;
- documentos;
- agregado;
- rendimentos.

## Regras

- irreversivel;
- preservar auditoria minimizada;
- preservar estatistica agregada;
- exigir aprovacao DPO quando aplicavel;
- nao executar em dados reais sem validacao municipal/juridica.

## Ambientes de teste

Dados de teste devem ser ficticios ou anonimizados. Exports e dumps reais nao podem ser versionados.
