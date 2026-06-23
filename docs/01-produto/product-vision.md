# Visao do produto

## Produto

MV HAB sera uma plataforma municipal para gerir o ciclo completo de Arrendamento Acessivel, desde a adesao inicial do candidato ate ao encerramento do contrato ou processo habitacional.

O objetivo e transformar o CRM atual numa plataforma processual, segura, auditavel e preparada para operacao municipal real, mantendo rastreabilidade das decisoes administrativas e respeitando RGPD desde a concecao.

## Problema a resolver

Os processos municipais de habitacao envolvem dados pessoais, documentos sensiveis, criterios legais, prazos, comunicacoes, reclamacoes, atribuicoes e contratos. Uma solucao apenas CRM permite registar informacao, mas nao garante por si so:

- cumprimento processual;
- segregacao de funcoes;
- historico de estados e decisoes;
- auditoria de acessos e alteracoes;
- rastreabilidade de documentos;
- publicacao controlada de listas;
- gestao de prazos legais;
- protecao RGPD operacional;
- indicadores executivos consistentes.

## Diferenca entre CRM atual e plataforma final

| Dimensao | CRM atual | Plataforma MV HAB final |
| --- | --- | --- |
| Foco | Registo e consulta de entidades | Gestao completa de processos habitacionais |
| Candidatura | CRUD simples de candidatura | Submissao formal, estados, bloqueio, historico e workflow |
| Documentos | Upload associado a entidades | Catalogo documental, obrigatoriedade, revisao, sensibilidade e auditoria |
| Elegibilidade | Nao modelada como motor | Motor versionado com resultados explicaveis |
| Classificacao | Pontuacao simples | Matriz de criterios, regras, ranking e snapshots |
| Atribuicao | Nao processual | Atribuicao auditavel por ranking/sorteio/regra |
| Contratos | Registo basico | Contrato ligado a candidatura, renda, caucao e revisoes |
| Pagamentos | Registo simples | Planos, incumprimentos, conciliacao e relatorios |
| Manutencao | Pedidos basicos | SLA, vistorias, relatorios e historico do imovel |
| Utilizadores | Autenticacao geral | Roles, permissions, policies e segregacao de funcoes |
| RGPD | Nao sistematizado | Finalidades, consentimentos, retencao, direitos e logs |
| Auditoria | Nao formalizada | Logs de acesso, alteracao, decisao, publicacao e exportacao |

## Ciclo de vida alvo

```text
Registo de adesao
→ Simulacao
→ Candidatura
→ Validacao documental
→ Elegibilidade
→ Classificacao
→ Lista provisoria
→ Reclamacoes / audiencia de interessados
→ Lista definitiva
→ Atribuicao
→ Contrato
→ Pagamentos
→ Manutencao
→ Revisao de renda
→ Encerramento
```

## Principios de produto

- O candidato deve compreender o estado do seu processo e o que falta entregar.
- O tecnico municipal deve conseguir trabalhar filas, prazos e excecoes sem perder rastreabilidade.
- O juri deve decidir com base em dados validados e criterios versionados.
- O municipio deve conseguir auditar quem acedeu, alterou, decidiu, publicou ou exportou.
- A plataforma deve recolher apenas dados necessarios para cada finalidade.
- A publicacao de informacao deve usar identificadores e minimizacao adequados.
- Os relatorios devem apoiar decisao executiva sem expor dados pessoais desnecessarios.

## Resultado esperado da Sprint 0

Sprint 0 nao entrega novas funcionalidades. Entrega alinhamento documental para que Sprint 1 e Sprint 2 possam ser executadas com controlo tecnico, funcional, RGPD e arquitetural.
