<?php

namespace Tests\Feature\UX;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MunicipalApplicationDemoBannerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mvhab.regulatory_demo_mode', false);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            false,
        );
    }

    public function test_banner_is_hidden_by_default(): void
    {
        $this->assertSame(
            '',
            trim($this->renderBanner()),
        );
    }

    public function test_banner_is_hidden_when_only_one_demo_mode_is_enabled(): void
    {
        config()->set('mvhab.regulatory_demo_mode', true);

        $this->assertSame(
            '',
            trim($this->renderBanner()),
        );

        config()->set('mvhab.regulatory_demo_mode', false);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );

        $this->assertSame(
            '',
            trim($this->renderBanner()),
        );
    }

    public function test_banner_is_present_when_both_demo_modes_are_enabled(): void
    {
        $this->enableDemoModes();

        $html = $this->renderBanner();

        $this->assertStringContainsString(
            'role="status"',
            $html,
        );
        $this->assertStringContainsString(
            'aria-label="Ambiente de demonstração"',
            $html,
        );
        $this->assertStringContainsString(
            'Ambiente de demonstração',
            $html,
        );
    }

    public function test_banner_contains_the_complete_demo_disclaimer(): void
    {
        $this->enableDemoModes();

        $text = html_entity_decode(
            strip_tags($this->renderBanner()),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        $normalizedText = preg_replace(
            '/\s+/u',
            ' ',
            trim($text),
        );

        $this->assertSame(
            'Ambiente de demonstração · '
            .'Dados fictícios · '
            .'Sem efeitos administrativos',
            $normalizedText,
        );
    }

    public function test_banner_component_is_inside_the_body_of_authenticated_and_guest_layouts(): void
    {
        $appLayout = file_get_contents(
            resource_path('views/layouts/app.blade.php'),
        );
        $guestLayout = file_get_contents(
            resource_path('views/layouts/guest.blade.php'),
        );

        $this->assertIsString($appLayout);
        $this->assertIsString($guestLayout);

        $this->assertComponentIsInsideBody($appLayout);
        $this->assertComponentIsInsideBody($guestLayout);
    }

    private function assertComponentIsInsideBody(
        string $layout,
    ): void {
        $bodyStart = strpos($layout, '<body');
        $bodyEnd = strpos($layout, '</body>');
        $component = strpos(
            $layout,
            '<x-demo-environment-banner />',
        );

        $this->assertNotFalse(
            $bodyStart,
            'O layout não contém uma abertura de body.',
        );
        $this->assertNotFalse(
            $bodyEnd,
            'O layout não contém um fecho de body.',
        );
        $this->assertNotFalse(
            $component,
            'O layout não contém o banner demo.',
        );

        $this->assertGreaterThan(
            $bodyStart,
            $component,
            'O banner deve estar depois da abertura do body.',
        );
        $this->assertLessThan(
            $bodyEnd,
            $component,
            'O banner deve estar antes do fecho do body.',
        );
    }

    private function renderBanner(): string
    {
        return Blade::render(
            '<x-demo-environment-banner />',
        );
    }

    private function enableDemoModes(): void
    {
        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );
    }
}
