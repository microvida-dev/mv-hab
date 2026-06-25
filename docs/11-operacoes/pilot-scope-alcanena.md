# Pilot Scope Alcanena

## Ambito aceite

O piloto municipal controlado da MV HAB inclui:

- portal publico;
- programas, concursos e oferta habitacional;
- area do candidato;
- candidaturas;
- documentos e aperfeicoamento;
- elegibilidade, scoring e listas;
- audiencia/reclamacoes;
- contratos com gestao administrativa/manual;
- rendas com gestao administrativa/manual;
- area do inquilino;
- manutencao e vistorias;
- visitas;
- tickets;
- FAQ;
- Work Tasks e SLA;
- auditoria;
- RGPD;
- IA documental assistiva sem decisao automatica.

## Fora de ambito por decisao municipal

| Elemento | Estado |
| --- | --- |
| CMD | Out of scope by municipal decision |
| Autenticacao.gov | Out of scope by municipal decision |
| pagamentos via plataforma | Out of scope by municipal decision |
| MB WAY | Out of scope by municipal decision |
| Multibanco | Out of scope by municipal decision |
| cartao | Out of scope by municipal decision |
| gateway de pagamentos | Out of scope by municipal decision |
| reconciliacao bancaria automatica | Out of scope by municipal decision |
| importacao SEPA automatica | Out of scope by municipal decision |
| assinatura digital qualificada | Out of scope by municipal decision |

## Alternativa operacional aceite

- autenticacao local da plataforma;
- contratos e assinaturas com gestao administrativa/manual;
- rendas e pagamentos com gestao administrativa/manual;
- comprovativos e registos internos quando aplicavel;
- auditoria;
- Work Tasks;
- documentacao operacional.

## Condicoes da demonstracao

- usar apenas dados ficticios;
- nao usar documentos reais de cidadaos;
- nao apresentar integracoes externas excluidas como ativas;
- validar restore/rollback real antes de producao plena;
- manter logs/evidencias sanitizados;
- rever smoke municipal antes de cada apresentacao.

## Riscos aceites

- restore/rollback real depende de ambiente nao produtivo descartavel;
- pagamentos digitais e assinatura digital qualificada permanecem roadmap;
- scheduler nao tem tarefas agendadas atuais, mas deve estar configurado.
