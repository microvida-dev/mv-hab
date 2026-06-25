# External Dossier Sanitization Checklist

## Objetivo

Garantir dossier externo municipal sem promessas funcionais indevidas, segredos, dados pessoais reais ou screenshots sensiveis.

## Checklist

- [ ] Usar apenas dados ficticios.
- [ ] Remover credenciais, tokens, chaves e variaveis de ambiente.
- [ ] Confirmar que CMD aparece como fora de ambito.
- [ ] Confirmar que Autenticacao.gov aparece como fora de ambito.
- [ ] Confirmar que pagamentos via plataforma aparecem como fora de ambito.
- [ ] Confirmar que assinatura digital qualificada aparece como fora de ambito.
- [ ] Confirmar que MB WAY, Multibanco, cartao, gateway e reconciliacao automatica aparecem como fora de ambito.
- [ ] Nao mostrar documentos privados.
- [ ] Nao mostrar NIF, NISS, IBAN, moradas ou rendimentos reais.
- [ ] Nao mostrar paths internos.
- [ ] Nao mostrar logs com stack traces sensiveis.
- [ ] Confirmar linguagem de piloto/staging, nao producao plena.

## Linguagem recomendada

- "Out of scope by municipal decision" para integracoes excluidas.
- "gestao administrativa/manual" para contratos, rendas e pagamentos no piloto.
- "IA assistiva sem decisao automatica" para document intelligence.
- "dados ficticios" para qualquer evidencia externa.

## Bloqueadores

- claims de integracao ativa sem implementacao;
- segredos reais;
- dados pessoais reais;
- documentos privados;
- screenshots com emails reais ou paths internos;
- dumps, backups ou exportacoes anexadas.
