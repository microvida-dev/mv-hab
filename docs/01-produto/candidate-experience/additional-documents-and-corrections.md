# Documentacao Adicional e Pedidos de Aperfeicoamento

## Objetivo

Permitir que o municipio solicite documentacao adicional ou aperfeicoamentos e que o candidato responda pela area pessoal, com historico, prazo e rastreabilidade.

## Ambito implementado

- pedidos municipais de documentacao adicional;
- submissao de documento adicional pelo candidato;
- decisao municipal sobre documento submetido;
- resposta a pedidos de aperfeicoamento existentes;
- registo de eventos na timeline;
- isolamento de acesso por candidato;
- storage privado para ficheiros submetidos.

## Fluxo de documento adicional

1. Municipio cria pedido associado a candidatura.
2. Candidato visualiza pedido na area de processo.
3. Candidato submete ficheiro e nota opcional.
4. Municipio analisa, aceita ou rejeita.
5. Resultado fica associado a timeline e ao processo.

## Fluxo de aperfeicoamento

1. Pedido de aperfeicoamento e emitido pelo workflow administrativo.
2. Candidato abre formulario de resposta.
3. Candidato confirma a resposta e submete informacao.
4. Servicos municipais analisam a resposta no fluxo existente.

## Regras de seguranca

- downloads e visualizacao de ficheiros devem passar por controller autorizado;
- paths internos de storage nao podem ser expostos;
- ficheiros publicos e ficheiros sensiveis devem ser tratados em dominios separados;
- o candidato nao pode responder a pedidos pertencentes a outra candidatura;
- a decisao municipal deve ser feita por perfis autorizados.

## Limites atuais

- validacao automatica de autenticidade documental, OCR e integracoes externas permanecem fora do ambito;
- a validade juridica de cada documento depende de revisao municipal;
- documentos reutilizados em candidaturas futuras exigem confirmacao e nao sao automaticamente considerados validos.
