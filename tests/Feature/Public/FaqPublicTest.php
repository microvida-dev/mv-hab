<?php

namespace Tests\Feature\Public;

use App\Models\ContextualFaq;
use App\Models\ContextualFaqCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_faq_lists_only_published_public_entries_and_supports_search(): void
    {
        $category = ContextualFaqCategory::factory()->create([
            'code' => 'visitas',
            'name' => 'Visitas',
        ]);
        ContextualFaq::factory()->create([
            'contextual_faq_category_id' => $category->id,
            'context_key' => 'public',
            'question' => 'Como posso reagendar uma visita?',
            'answer' => 'Aceda à área do candidato e escolha a visita ativa.',
            'is_active' => true,
            'published_at' => now()->subMinute(),
        ]);
        ContextualFaq::factory()->create([
            'contextual_faq_category_id' => $category->id,
            'context_key' => 'public',
            'question' => 'Pergunta arquivada',
            'answer' => 'Não deve aparecer publicamente.',
            'is_active' => false,
            'published_at' => now()->subMinute(),
        ]);
        ContextualFaq::factory()->create([
            'context_key' => 'application_draft',
            'question' => 'FAQ contextual reservada',
            'answer' => 'Não deve aparecer na FAQ pública.',
        ]);

        $this->get(route('public.faq', ['q' => 'reagendar']))
            ->assertOk()
            ->assertSee('Como posso reagendar uma visita?')
            ->assertSee('Aceda à área do candidato')
            ->assertDontSee('Pergunta arquivada')
            ->assertDontSee('FAQ contextual reservada');
    }

    public function test_public_faq_fallback_keeps_institutional_guidance_when_no_dynamic_entries_exist(): void
    {
        $this->get(route('public.faq'))
            ->assertOk()
            ->assertSee('O que é o Arrendamento Acessível?')
            ->assertSee('Onde posso pedir apoio?');
    }
}
