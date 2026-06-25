# Municipal Demo Data Guide

## Objetivo

Executar dados demo seguros para apresentacao municipal controlada de Alcanena.

## Seeder principal

```bash
php artisan db:seed --class=MunicipalPilotStagingSeeder
```

Este seeder chama configuracoes base, cria municipio/programa/concurso Alcanena, fogos demo, apoio ao candidato, FAQ, visitas e Work Tasks.

## Dados permitidos

- emails `@example.test` e `@exemplo.pt`;
- nomes ficticios marcados como demo;
- moradas ficticias;
- documentos placeholder;
- utilizadores demo sem credenciais triviais;
- dados operacionais suficientes para smoke municipal.

## Dados proibidos

- documentos reais de cidadaos;
- emails reais;
- NIF, NISS ou IBAN reais;
- passwords em claro;
- backups ou dumps;
- anexos privados reais.

## Acesso demo

Nao documentar password fixa. Para acesso manual, usar convite, reset seguro ou criacao administrativa conforme ambiente.

## Smoke recomendado

1. homepage;
2. concursos publicos;
3. oferta habitacional;
4. login candidato;
5. candidatura/documentos;
6. backoffice;
7. Work Tasks;
8. visitas;
9. tickets;
10. FAQ;
11. area inquilino;
12. auditoria/RGPD.
