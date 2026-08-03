<?php

namespace App\Enums;

enum Program53FailureCode: string
{
    case SourceNotFound = 'source_not_found';
    case StaleSource = 'stale_source';
    case AuthorizationRevoked = 'authorization_revoked';
    case SchemaInvalid = 'schema_invalid';
    case StorageUnavailable = 'storage_unavailable';
    case DatabaseDeadlock = 'database_deadlock';
    case DatabaseUnavailable = 'database_unavailable';
    case PackageCorrupted = 'package_corrupted';
    case DocumentUnavailable = 'document_unavailable';
    case UnexpectedFailure = 'unexpected_failure';

    public function safeMessage(): string
    {
        return match ($this) {
            self::SourceNotFound,
            self::StaleSource => 'A fonte temporal deixou de estar disponível ou válida.',
            self::AuthorizationRevoked => 'A autorização necessária deixou de estar disponível.',
            self::SchemaInvalid => 'Um formato gerado não cumpriu o schema versionado.',
            self::StorageUnavailable => 'O storage privado não respondeu à operação.',
            self::DatabaseDeadlock,
            self::DatabaseUnavailable => 'A base de dados não concluiu a operação técnica.',
            self::PackageCorrupted => 'O pacote municipal não passou a validação de integridade.',
            self::DocumentUnavailable => 'O dossier contém documentos indisponíveis para inclusão segura.',
            self::UnexpectedFailure => 'A operação não pôde ser concluída.',
        };
    }
}
