<?php

namespace Tests\Feature\Candidate;

use Tests\TestCase;

class DocumentUploadAccessibilityTest extends TestCase
{
    public function test_candidate_document_upload_view_has_labels_instructions_and_errors(): void
    {
        $view = (string) file_get_contents(resource_path('views/candidate/documents/create.blade.php'));

        $this->assertStringContainsString('for="title"', $view);
        $this->assertStringContainsString('for="issue_date"', $view);
        $this->assertStringContainsString('for="expiry_date"', $view);
        $this->assertStringContainsString('for="file"', $view);
        $this->assertStringContainsString('type="file"', $view);
        $this->assertStringContainsString('Formatos permitidos', $view);
        $this->assertStringContainsString('$errors->get(\'file\')', $view);
        $this->assertStringContainsString('enctype="multipart/form-data"', $view);
    }
}
