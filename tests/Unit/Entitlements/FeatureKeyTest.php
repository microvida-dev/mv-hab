<?php

namespace Tests\Unit\Entitlements;

use App\Enums\FeatureKey;
use PHPUnit\Framework\TestCase;

class FeatureKeyTest extends TestCase
{
    public function test_catalog_contains_exactly_the_three_application_features(): void
    {
        $this->assertSame([
            'applications.intake',
            'applications.review',
            'applications.export',
        ], array_column(FeatureKey::cases(), 'value'));
    }

    public function test_labels_are_stable_and_written_in_portuguese(): void
    {
        $this->assertSame('Recolha de candidaturas', FeatureKey::ApplicationIntake->label());
        $this->assertSame('Análise de candidaturas', FeatureKey::ApplicationReview->label());
        $this->assertSame('Exportação de candidaturas', FeatureKey::ApplicationExport->label());
    }

    public function test_dependencies_are_explicit_and_acyclic(): void
    {
        $this->assertSame([], FeatureKey::ApplicationIntake->dependencies());
        $this->assertSame([FeatureKey::ApplicationIntake], FeatureKey::ApplicationReview->dependencies());
        $this->assertSame([FeatureKey::ApplicationIntake], FeatureKey::ApplicationExport->dependencies());

        foreach (FeatureKey::cases() as $feature) {
            $this->assertNotContains($feature, $feature->dependencies());

            foreach ($feature->dependencies() as $dependency) {
                $this->assertNotContains($feature, $dependency->dependencies());
            }
        }
    }
}
