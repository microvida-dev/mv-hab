<?php

namespace App\Services\DocumentIntelligence;

use App\Enums\DocumentAiRiskFlagCode;

class DocumentSuggestionTemplateRegistry
{
    public function templateFor(DocumentAiRiskFlagCode $code): string
    {
        return match ($code) {
            DocumentAiRiskFlagCode::DocumentExpired => 'Foi identificada uma data de validade expirada no documento submetido. Solicita-se a confirmação manual e, se aplicável, o envio de documento atualizado.',
            DocumentAiRiskFlagCode::DocumentUnreadable => 'O documento submetido não apresenta legibilidade suficiente para validação técnica. Solicita-se o envio de uma versão mais legível.',
            DocumentAiRiskFlagCode::PageCropped => 'Foram detetados sinais de página incompleta. Solicita-se a submissão do documento completo para revisão.',
            DocumentAiRiskFlagCode::InsufficientOcr => 'A leitura automática não extraiu texto suficiente. Recomenda-se solicitar uma nova versão digitalizada ou fotografia com melhor qualidade.',
            DocumentAiRiskFlagCode::NifMismatch => 'Foi identificada divergência entre o NIF declarado e o documento submetido. Solicita-se verificação e documentação atualizada, se aplicável.',
            DocumentAiRiskFlagCode::NameMismatch => 'Foi identificada divergência entre o nome declarado e o documento submetido. Solicita-se confirmação documental antes de prosseguir.',
            DocumentAiRiskFlagCode::IncomeIncompatible => 'Foram identificadas diferenças entre rendimentos declarados e dados documentais. Solicita-se análise técnica e eventual pedido de esclarecimento.',
            DocumentAiRiskFlagCode::DuplicateDocument => 'Foi identificado documento tecnicamente coincidente com outro já submetido. Recomenda-se confirmar se a submissão duplicada é intencional.',
            DocumentAiRiskFlagCode::EmptyDocument => 'Não foi identificado conteúdo útil no ficheiro submetido. Solicita-se o reenvio do documento.',
            DocumentAiRiskFlagCode::MissingRequiredFields => 'Campos obrigatórios não foram extraídos com confiança suficiente. Recomenda-se revisão manual e eventual pedido de complemento documental.',
        };
    }
}
