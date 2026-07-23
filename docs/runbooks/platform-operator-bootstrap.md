# Runbook - Bootstrap de operadores de plataforma

## Objetivo

Inicializar associações globais aprovadas sem inferir utilizadores e sem
atribuir roles ou permissions.

## Pré-condições

1. Confirmar backup e capacidade de restauro.
2. Confirmar janela operacional e equipa responsável.
3. Obter manifesto aprovado fora do repositório.
4. Confirmar pelo menos duas referências de aprovação distintas.
5. Confirmar IDs internos explícitos, sem emails ou dados pessoais adicionais.
6. Confirmar que cada conta é dedicada, ativa e não candidate.
7. Confirmar `municipality_id = null` sem usar este campo como critério de
   seleção.
8. Confirmar MFA configurado e dispositivo confirmado.
9. Confirmar permissions administrativas mínimas através de role ativa.
10. Confirmar que o manifesto declara o ambiente de destino.

Formato:

```json
{
  "environment": "staging",
  "approved_user_ids": [123],
  "approval_references": [
    "SEC-APPROVAL-001",
    "MANAGEMENT-APPROVAL-001"
  ],
  "bootstrap_operator_reference": "OPS-RUNBOOK-001",
  "approved_at": "2026-07-23"
}
```

O ficheiro deve permanecer fora da árvore do repositório e não pode conter
passwords, tokens, segredos MFA ou documentos.

## Execução

1. Ativar maintenance mode:

   ```bash
   php artisan down
   ```

2. Publicar a versão validada.
3. Executar migrations:

   ```bash
   php artisan migrate --force
   ```

4. Limpar caches:

   ```bash
   php artisan optimize:clear
   ```

5. Executar dry-run:

   ```bash
   php artisan platform-operators:bootstrap \
     --manifest=/caminho/externo/platform-operators.json \
     --dry-run
   ```

6. Rever todos os IDs e estados apresentados. O output não deve conter nomes,
   emails ou outros dados pessoais.
7. Corrigir o manifesto ou a conta se o dry-run falhar. Não ativar fallback.
8. Executar a mutação:

   ```bash
   php artisan platform-operators:bootstrap \
     --manifest=/caminho/externo/platform-operators.json \
     --confirm
   ```

9. Confirmar associações ativas por ID através de consulta administrativa
   protegida.
10. Confirmar um evento `platform_operator_bootstrapped` por nova associação.
11. Confirmar que roles e permissions não foram alteradas.
12. Testar, numa sessão separada com MFA, a listagem de operadores e a gestão
    de funcionalidades municipais.
13. Testar uma conta sem associação e confirmar recusa segura.
14. Executar smoke tests de autenticação, autorização e auditoria.
15. Limpar caches novamente.
16. Retirar maintenance mode:

   ```bash
   php artisan up
   ```

## Idempotência

Repetir o mesmo manifesto mantém a associação ativa existente e não cria
duplicados nem novos eventos de bootstrap. Uma associação revogada não é
reativada automaticamente.

## Evidência de deploy

Guardar fora do repositório:

- hash da versão;
- referência da mudança;
- caminho seguro do manifesto;
- output sanitizado do dry-run;
- output sanitizado da execução;
- IDs das associações;
- IDs dos eventos de auditoria;
- resultado dos smoke tests.

Sem esta evidência, o estado é `DEPLOYMENT_GATED`, não `DEPLOYED`.
