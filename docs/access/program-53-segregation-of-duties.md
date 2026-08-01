# Segregação de funções do Programa 53

## Combinação permitida

O perfil `analista-candidaturas-exportacao` combina deliberadamente análise, decisão documental, aperfeiçoamento, selagem, publicação e exportação municipal não sensível. Esta combinação é uma decisão funcional explícita do Programa 53 e não introduz maker-checker sem fonte regulamentar.

## Fronteiras obrigatórias

- Exportação sensível exige uma autorização separada e nunca integra o template.
- Candidate não pode receber roles municipais internas.
- Auditor não pode receber, pelo fluxo normal, um template municipal mutável do Programa 53.
- Conta inativa ou role inativa não pode receber o perfil.
- A role municipal não cria `PlatformOperatorAssignment` nem scope global.
- A aplicação do template não atribui a role a utilizadores e não ativa entitlements.
- A atribuição deve respeitar Município do ator, alvo e role e nunca criar permissões diretas.

## Conflitos

| Condição | Resultado esperado |
|---|---|
| Candidate + template interno | Recusa fail-closed |
| Auditor + template mutável | Recusa fail-closed |
| Utilizador inativo | Recusa fail-closed |
| Role inativa | Recusa fail-closed |
| Role de outro Município | Recusa fail-closed |
| Ator sem todas as permissões da role | Recusa fail-closed |
| Template sem permission de catálogo | Recusa fail-closed |
| Drift da role | Preview; nenhuma remoção silenciosa |
| Exportação sensível sem grant separado | Recusa pela Policy |

## Drift e reconciliação

Roles históricas continuam válidas e não são associadas a templates por nome ou label. Uma role criada por template terá key, versão e fingerprint. Divergência entre matriz efetiva e template é apresentada antes de qualquer reconciliação. A reconciliação exige confirmação administrativa explícita; nunca remove permissões silenciosamente.

## Auditoria minimizada

Registar apenas IDs técnicos, Município, role, template, versão, fingerprint, contagens e códigos de permissões adicionadas/removidas. Não registar email, nome, NIF, conteúdo documental, payload de candidatura, paths, tokens, códigos MFA ou passwords.
