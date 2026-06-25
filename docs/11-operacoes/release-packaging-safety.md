# Release Packaging Safety

## Objetivo

Garantir que artefactos de staging, demo e piloto municipal nao incluem segredos, dados pessoais reais, dumps, documentos privados ou configuracao insegura.

## Artefactos permitidos

- codigo versionado;
- `composer.lock` e `package-lock.json`;
- `public/build` apenas como artefacto gerado fora do Git, quando aplicavel;
- `.env.example` com placeholders;
- documentacao operacional sem segredos reais;
- evidencias QA sanitizadas.

## Artefactos bloqueados

- `.env` e variantes locais;
- APP_KEY real;
- debug ativo em staging/demo;
- DB_PASSWORD com valor real;
- chaves privadas;
- tokens;
- dumps SQL;
- backups;
- documentos reais;
- ficheiros zip nao aprovados;
- paths locais pessoais;
- storage privado;
- NIF, NISS, IBAN, moradas ou rendimentos reais.

## Scripts locais

Executar antes de preparar pacote externo:

```bash
php scripts/check-secrets.php
php scripts/check-release-artifact-safety.php
```

Para validar uma pasta ou ficheiro especifico:

```bash
php scripts/check-release-artifact-safety.php /path/to/release-artifact
```

## Procedimento

1. Preparar build e dependencias no ambiente de release.
2. Confirmar que `.env.example` contem apenas placeholders.
3. Confirmar que `.env` fica fora do Git e fora do pacote.
4. Executar scanner de segredos e artefactos.
5. Rever manualmente documentacao externa e screenshots.
6. Se houver finding critico, bloquear o pacote e remover o artefacto.

## Decisao

Um pacote so pode seguir para demo municipal controlada se os scripts nao detetarem segredos reais, documentos reais, dumps, backups ou configuracao insegura.
