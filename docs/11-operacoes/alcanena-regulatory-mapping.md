# Alcanena Regulatory Mapping

## Objetivo

Mapear os artigos principais do Regulamento Municipal de Arrendamento Acessivel de Alcanena para a parametrizacao tecnica existente na MV HAB.

## Matriz regulamentar

| Artigo | Materia | Implementacao/parametrizacao | Estado |
| --- | --- | --- | --- |
| Artigo 8 | Condicoes de elegibilidade | `EligibilityRuleSet` com criterios de idade, residencia, rendimentos, agregado, tipologia, documentos e taxa de esforco | parametrizado |
| Artigo 9 | Impedimentos | criterios manuais com `requires_manual_review` para propriedade, apoios incompatíveis, dividas, fraude e incumprimentos | parametrizado com validacao humana |
| Artigo 12 | Documentacao obrigatoria | `DocumentType` e `RequiredDocument` com identificacao, NIF, Seguranca Social, domicilio fiscal, IRS, AT/ISS, propriedade, incapacidade e gravidez | parametrizado |
| Artigo 14 | Aperfeicoamento documental | prazo demo de aperfeicoamento e workflow administrativo com pedidos documentais | parametrizado |
| Artigo 15 | Classificacao e desempate | `ScoringRuleSet`, `ScoringRule` e `TieBreakerRule` com matriz do Anexo I e desempates determinísticos | parametrizado |
| Artigo 17 | Listas, audiencia e reclamacoes | prazos de reclamacao/audiencia, snapshots e publicacao de listas validados nas QA-23/QA-36 | parametrizado |

## Pontos que exigem validacao municipal antes de producao

- datas finais do edital;
- identificacao e caracteristicas reais dos fogos;
- rendas minimas/maximas reais;
- rendimento anual maximo aplicavel no ano do concurso;
- textos finais de edital e minuta contratual;
- responsaveis municipais e membros do juri;
- validacao juridica das clausulas contratuais.

## Decisoes humanas obrigatorias

A plataforma nao decide automaticamente:

- elegibilidade final;
- aprovacao/rejeicao documental;
- deferimento/indeferimento de reclamacoes;
- publicacao de listas;
- atribuicao final quando houver excecoes ou sorteio;
- assinatura/validacao contratual.

## Integrações fora de ambito

Estes elementos sao `Out of scope by municipal decision`:

- assinatura digital;
- Autenticacao.gov/CMD;
- pagamentos via plataforma;
- MB WAY, Multibanco, cartao e gateway de pagamentos;
- reconciliacao bancaria automatica;
- importacao SEPA automatica.

O piloto usa autenticacao local, gestao administrativa/manual de contratos e pagamentos, auditoria, Work Tasks e documentacao operacional.
