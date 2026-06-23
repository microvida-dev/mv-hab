# Regras de Publicação Pública

## Habitações

Uma habitação só aparece no portal público quando:

- `is_public = true`;
- `public_visibility_status = published`;
- `published_at` está vazio ou no passado;
- `unpublished_at` está vazio.

## Localização

- A morada completa só é mostrada quando `public_address_visible = true`.
- Por defeito, a localização é apresentada por freguesia/localidade.
- Coordenadas públicas devem ser aproximadas quando a publicação da morada exata não for necessária.

## Imagens

- Apenas imagens com `is_public = true` são usadas na galeria pública.
- A imagem de capa pode atualizar `og_image_path`.
- Imagens devem ser institucionais, sem pessoas identificáveis salvo autorização própria.

## Documentos públicos

Um documento só pode ser descarregado quando:

- `is_public = true`;
- `published_at` está vazio ou no passado;
- `expires_at` está vazio ou no futuro;
- a habitação associada está publicada.

O download passa por `PublicHousingDocumentController` e incrementa `download_count`.

## Backoffice

Perfis autorizados precisam de permissões existentes sobre `housing_units` ou `settings`.

## Proibições

- Não publicar documentos de candidatura.
- Não publicar comprovativos pessoais.
- Não publicar notas internas.
- Não expor paths internos de storage.
- Não publicar dados de candidatos, agregados ou rendimentos.
