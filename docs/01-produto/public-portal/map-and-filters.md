# Mapa e Filtros Públicos

## Filtros suportados

- Pesquisa livre (`q`).
- Tipologia.
- Freguesia.
- Estado público da habitação.
- Renda mínima e máxima.
- Concurso.
- Estado público do concurso: aberto, futuro ou encerrado.
- Acessibilidade associada à habitação por concurso.
- Ordenação por publicação, renda ou tipologia.

## Endpoint de mapa

`GET /oferta-habitacional/mapa`

Resposta:

- `enabled`;
- `center.latitude`;
- `center.longitude`;
- `center.zoom`;
- `markers`.

Cada marcador contém apenas dados públicos:

- título;
- URL pública;
- tipologia;
- renda;
- estado público;
- localização pública;
- latitude/longitude públicas;
- precisão da localização.

## Privacidade

O endpoint não inclui:

- morada completa;
- paths de ficheiros;
- dados pessoais;
- IDs de candidatos;
- notas internas.

## Fallback operacional

Sem integração cartográfica externa, a página apresenta uma lista de marcadores e o endpoint JSON. Esta solução é suficiente para teste local e futura ligação a Leaflet, Google Maps ou cartografia municipal.
