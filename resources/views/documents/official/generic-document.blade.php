<!DOCTYPE html>
<html lang="pt">
<head><meta charset="utf-8"><title>{{ $title }}</title><style>body{font-family:Arial,sans-serif;color:#18212f;line-height:1.6;max-width:800px;margin:40px auto;padding:0 32px}header,footer{color:#596579;font-size:13px}header{border-bottom:1px solid #dfe4ea;padding-bottom:16px}footer{border-top:1px solid #dfe4ea;padding-top:16px;margin-top:48px}.number{font-family:monospace;font-size:12px;color:#596579}h1{font-size:24px;margin:32px 0 24px}</style></head>
<body><header>{!! nl2br(e($header ?? 'Município MV HAB')) !!}</header><p class="number">{{ $documentNumber }}</p><h1>{{ $title }}</h1><main>{!! $body !!}</main><footer>{!! nl2br(e($footer ?? 'Documento gerado pela plataforma MV HAB.')) !!}</footer></body>
</html>
