# Sorteios auditáveis

## Objetivo

O módulo de sorteios auditáveis suporta sorteios associados a execuções de atribuição, usando participantes carregados a partir de lista definitiva e resultado persistido com hash.

## Estados

- `draft`
- `participants_loaded`
- `participants_locked`
- `ready`
- `running`
- `completed`
- `validated`
- `cancelled`
- `superseded`
- `failed`

## Participantes e hashes

Os participantes são carregados da lista definitiva elegível, recebem `participant_number`, snapshot mínimo, posição anterior e pontuação anterior. O bloqueio gera `participants_hash`.

## Execução

O motor `App\Services\Lottery\AuditableLotteryEngine` usa seed controlada e `sha256(seed:participant)`. Não usa `rand()`. A mesma seed com os mesmos participantes gera a mesma ordem.

## Validação administrativa

O resultado concluído deve ser validado por perfil autorizado antes de produzir efeitos administrativos definitivos.

Texto obrigatório:

> O sorteio deve ser validado pelos serviços competentes antes de produzir efeitos administrativos definitivos. O resultado registado na plataforma é auditável e fica associado ao procedimento.

## Segurança

Guest e candidato não acedem ao backoffice de sorteios. Auditor consulta sem alterar. Ações críticas passam por service e geram auditoria.
