# Registo de vencedor

## Objetivo

Registar o vencedor administrativo a partir de um resultado de sorteio validado.

## Regras

- Resultado tem de estar validado.
- Apenas resultado selecionado pode gerar vencedor.
- Não permite vencedor ativo duplicado para a mesma habitação.
- O registo é transacional e auditado.
- O participante vencedor passa para estado `winner`.

## Integrações

Quando existe atribuição compatível, o vencedor fica associado à atribuição e à habitação.
