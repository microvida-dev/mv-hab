<?php

namespace Tests\Unit\Audit;

use App\Services\Audit\AuditEventFormatter;
use Tests\TestCase;

class AuditEventFormatterTest extends TestCase
{
    public function test_sensitive_values_are_masked_recursively_without_losing_safe_context(): void
    {
        $masked = app(AuditEventFormatter::class)->mask([
            'name' => 'Cenario QA',
            'nif' => 'S19-000000',
            'profile' => [
                'document_number' => 'DOC-TEST',
                'email' => 's19-safe@example.test',
                'nested' => [
                    'storage_path' => 'documents/testing/s19/file.pdf',
                    'label' => 'valor visivel',
                ],
            ],
            'token_value' => 'secret-token',
        ]);

        $this->assertSame('Cenario QA', $masked['name']);
        $this->assertSame('[masked]', $masked['nif']);
        $this->assertSame('[masked]', $masked['profile']['document_number']);
        $this->assertSame('s19-safe@example.test', $masked['profile']['email']);
        $this->assertSame('[masked]', $masked['profile']['nested']['storage_path']);
        $this->assertSame('valor visivel', $masked['profile']['nested']['label']);
        $this->assertSame('[masked]', $masked['token_value']);
    }
}
