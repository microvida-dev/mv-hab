<?php

namespace App\Services\Reporting\Temporal;

use JsonException;
use Opis\JsonSchema\Validator;
use RuntimeException;
use XMLReader;

final class ApplicationResultExportSchemaValidator
{
    public const JSON_SCHEMA = 'schema/mvhab-application-results-v1.schema.json';

    public const XML_SCHEMA = 'schema/mvhab-application-results-v1.xsd';

    private ?object $jsonSchema = null;

    /**
     * Validate one record at a time so package generation remains streaming.
     *
     * @param  array<string, mixed>  $record
     */
    public function validateJsonRecord(string $definition, array $record): void
    {
        $schema = $this->jsonDefinition($definition);
        $data = json_decode(
            json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
        $result = (new Validator)->validate($data, $schema);
        if (! $result->isValid()) {
            throw new RuntimeException("O registo JSON não cumpre o schema {$definition}.");
        }
    }

    /** @param array<string, mixed> $metadata */
    public function validateJsonMetadata(array $metadata): void
    {
        $this->validateJsonRecord('export', $metadata);
    }

    public function validateJsonDocument(string $absolutePath): void
    {
        $contents = file_get_contents($absolutePath);
        if (! is_string($contents)) {
            throw new RuntimeException('Não foi possível ler o JSON para validação.');
        }

        try {
            $data = json_decode($contents, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('O artefacto JSON não é válido.', previous: $exception);
        }

        $result = (new Validator)->validate($data, $this->schema());
        if (! $result->isValid()) {
            throw new RuntimeException('O artefacto JSON não cumpre o schema versionado.');
        }
    }

    public function validateXmlDocument(string $absolutePath): void
    {
        if ($this->containsProhibitedXmlDeclaration($absolutePath)) {
            throw new RuntimeException('O artefacto XML contém DTD ou entidades proibidas.');
        }

        $schemaPath = base_path(self::XML_SCHEMA);
        $schema = file_get_contents($schemaPath);
        if (
            ! is_string($schema)
            || preg_match('/<(?:xs:)?(?:include|import|redefine)\b/i', $schema) === 1
        ) {
            throw new RuntimeException('O XSD local contém referências externas não autorizadas.');
        }

        $reader = new XMLReader;
        if (! $reader->open($absolutePath, 'UTF-8', LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('Não foi possível abrir o XML para validação.');
        }

        $xmlError = null;
        set_error_handler(static function (int $severity, string $message) use (&$xmlError): bool {
            $xmlError = $message;

            return true;
        });

        try {
            $reader->setParserProperty(XMLReader::LOADDTD, false);
            $reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);
            if (! $reader->setSchema($schemaPath)) {
                throw new RuntimeException('Não foi possível aplicar o XSD local.');
            }

            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::DOC_TYPE) {
                    throw new RuntimeException('O artefacto XML contém um DTD proibido.');
                }
            }

            if (! $reader->isValid()) {
                throw new RuntimeException(
                    'O artefacto XML não cumpre o XSD versionado.'
                    .($xmlError === null ? '' : ' '.$xmlError),
                );
            }
        } finally {
            restore_error_handler();
            $reader->close();
        }
    }

    private function containsProhibitedXmlDeclaration(string $absolutePath): bool
    {
        $stream = fopen($absolutePath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Não foi possível ler o XML para validação.');
        }

        $tail = '';
        try {
            while (($chunk = fread($stream, 8192)) !== false && $chunk !== '') {
                $candidate = $tail.$chunk;
                if (preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $candidate) === 1) {
                    return true;
                }
                $tail = substr($candidate, -32);
            }
        } finally {
            fclose($stream);
        }

        return false;
    }

    private function schema(): object
    {
        if ($this->jsonSchema instanceof \stdClass) {
            return $this->jsonSchema;
        }

        $contents = file_get_contents(base_path(self::JSON_SCHEMA));
        if (! is_string($contents)) {
            throw new RuntimeException('O JSON Schema versionado não está disponível.');
        }

        try {
            $schema = json_decode($contents, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('O JSON Schema versionado não é válido.', previous: $exception);
        }

        if (! $schema instanceof \stdClass) {
            throw new RuntimeException('O JSON Schema versionado não é um objeto.');
        }

        return $this->jsonSchema = $schema;
    }

    private function jsonDefinition(string $definition): object
    {
        $schema = $this->schema();
        $definitions = $schema->{'$defs'} ?? null;
        $definitionSchema = $definitions instanceof \stdClass
            ? ($definitions->{$definition} ?? null)
            : null;

        if (! $definitionSchema instanceof \stdClass) {
            throw new RuntimeException("A definição JSON {$definition} não existe.");
        }

        return $definitionSchema;
    }
}
