# Dossier Documental Padronizado — Sprint 24

## Objetivo

Criar uma visão normalizada dos documentos de uma candidatura, classificando documentos em falta, submetidos, validados, rejeitados, expirados ou duplicados.

## Implementação

- Tabelas: `document_dossiers`, `document_dossier_items`
- Models: `DocumentDossier`, `DocumentDossierItem`
- Controller: `DocumentDossierController`
- Services: `DocumentDossierService`, `DocumentDossierBuilder`, `DocumentStandardizationService`, `DocumentDossierExportService`

## Segurança

- Não expõe paths internos de documentos.
- O dossier é índice operacional; o download de documentos reais continua a passar pelos controllers documentais autorizados.
- Exportação HTML fica em storage privado.

## Pendências

- Confirmar categorias documentais e nomenclatura final com os serviços municipais.
- Validar política de retenção e anonimização com RGPD.
