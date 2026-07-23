<?php

namespace App\Services\ProcedureMinutes\Renderers;

use App\Models\Municipality;
use Illuminate\Support\Carbon;

final class AlcanenaAta01Renderer
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function render(array $payload): string
    {
        $title = $this->value($payload, 'contest.title', 'Concurso de Arrendamento Municipal Acessível');
        $process = $this->value($payload, 'municipal.process_number', '__________');
        $year = now()->format('Y');
        $sequence = $this->value($payload, 'ata.minute_sequence', '1');

        return '<!doctype html>
<html lang="pt">
<head>
<meta charset="utf-8">
<title>Ata n.º '.$this->e($sequence).'/'.$this->e($year).' — Apreciação e seriação de candidaturas</title>
<style>
@page { size: A4; margin: 24mm 22mm 22mm 22mm; }
body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12pt; line-height: 1.45; }
.official-page { max-width: 760px; margin: 0 auto; }
.official-header {
    margin-bottom: 28px;
    text-align: center;
}
.official-logo-image {
    height: 72px;
    width: auto;
    display: block;
    margin: 0 auto;
}
.brand-mark { display: inline-block; }
.official-logo { font-size: 30px; font-weight: 800; letter-spacing: -1px; }
.official-logo-small { font-size: 11px; letter-spacing: 5px; margin-left: 4px; }
.title-block { text-align: center; font-weight: 700; margin-bottom: 42px; }
.title-block h1, .title-block h2, .title-block h3, .title-block p { margin: 0 0 16px; }
.title-block h2 { font-size: 14pt; line-height: 1.35; }
.title-block h1 { font-size: 14pt; }
.title-block h3 { font-size: 13pt; }
.body p { text-align: justify; margin: 0 0 14px; }
.signature-block { margin-top: 48px; text-align: center; break-inside: avoid; }
.signature-line { width: 340px; border-top: 1px solid #111; margin: 44px auto 6px; }
.page-break { page-break-before: always; }
strong { font-weight: 700; }
</style>
</head>
<body>
<div class="official-page">'
            .$this->renderHeader()
            .$this->renderTitleBlock($title, $process, $sequence, $year)
            .'<section class="body">'
            .$this->renderOpeningParagraph($payload)
            .$this->renderJuryParagraphs($payload)
            .$this->renderPurposeParagraphs($payload)
            .$this->renderDeliberationParagraphs($payload)
            .$this->renderClosingParagraph()
            .'</section>'
            .$this->renderSignatureBlock($payload)
            .'</div>
</body>
</html>';
    }

    private function renderHeader(): string
    {
        $logoPath = Municipality::query()->value('official_logo_path');

        if ($logoPath !== null && $logoPath !== '') {
            $absolutePath = storage_path('app/public/'.$logoPath);

            if (is_file($absolutePath)) {
                $url = asset('storage/'.$logoPath);

                return '<header class="official-header">
                    <img src="'.$this->e($url).'" class="official-logo-image" alt="Município de Alcanena">
                </header>';
            }
        }

        return '<header class="official-header">
            <div class="brand-mark">
                <div class="official-logo">ALCANENA</div>
                <div class="official-logo-small">CÂMARA MUNICIPAL</div>
            </div>
        </header>';
    }

    private function renderTitleBlock(string $title, string $process, string $sequence, string $year): string
    {
        return '<section class="title-block">
            <h2>'.$this->e($title).'</h2>
            <p>Processo '.$this->e($process).'</p>
            <h1>Ata n.º '.$this->e($sequence).'/'.$this->e($year).'</h1>
            <h3>- Apreciação e seriação de candidaturas -</h3>
        </section>';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderOpeningParagraph(array $payload): string
    {
        $date = $this->value($payload, 'ata.meeting_date_long')
            ?: $this->dateLong($this->value($payload, 'meeting.date'));
        $time = $this->value($payload, 'ata.meeting_time_long')
            ?: $this->value($payload, 'meeting.time', 'hora a indicar');
        $location = $this->value($payload, 'meeting.location', 'Edifício dos Paços do Concelho de Alcanena');
        $appointment = $this->value($payload, 'ata.jury_appointment_reference', 'deliberação de Câmara');

        return $this->p(
            'Aos '.$date.', pelas '.$time.', reuniu no '.$location.', o júri do concurso nomeado em '.$appointment.', constituído por:'
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderJuryParagraphs(array $payload): string
    {
        $president = $this->juryPresident($payload);
        $vogals = $this->juryVogals($payload);

        return $this->p('<strong>Presidente:</strong> '.$this->e($president))
            .$this->p('<strong>Vogais:</strong> '.$this->e($vogals));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderPurposeParagraphs(array $payload): string
    {
        $notice = $this->value($payload, 'ata.opening_notice_number', '__________');
        $noticeDate = $this->date($this->value($payload, 'ata.opening_notice_date'));
        $platform = $this->value($payload, 'ata.submission_platform_url', 'plataforma eletrónica');
        $range = $this->submissionRange($payload);

        $applicationsTotal = $this->value($payload, 'summary.applications_total', '0');
        $uniqueCandidates = $this->value($payload, 'summary.unique_candidates_total', $applicationsTotal);

        return $this->p(
            'A presente reunião teve por finalidade proceder à seriação das candidaturas apresentadas através de plataforma eletrónica, conforme estipulado no Aviso com registo '.$notice.($noticeDate !== '' ? ' de '.$noticeDate : '').', no site da autarquia '.$platform.', cujo prazo para apresentação decorreu '.$range.'.'
        )
        .$this->p(
            'O júri rececionou <strong>'.$this->e((string) $applicationsTotal).' candidaturas</strong> correspondentes a <strong>'.$this->e((string) $uniqueCandidates).' candidatos</strong>, uma vez que alguns candidatos realizaram candidaturas a várias habitações.'
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderDeliberationParagraphs(array $payload): string
    {
        $completionDeadline = $this->date($this->value($payload, 'ata.document_completion_deadline'));
        $exceptional = $this->value($payload, 'ata.exceptional_application_text');
        $preference = $this->value($payload, 'ata.preference_instruction_text');

        $html = $this->p(
            'Analisadas as candidaturas e verificação dos elementos apresentados pelos candidatos, designadamente os documentos juntos, o júri deliberou, por unanimidade, solicitar documentação em falta para proceder à instrução das candidaturas, ao abrigo do disposto no n.º 1 do artigo 14.º do Regulamento Municipal de Habitação Acessível.'
        );

        if ($exceptional !== '') {
            $html .= $this->p($this->e($exceptional));
        }

        $deadlineText = $completionDeadline !== '' ? ' até ao dia '.$completionDeadline : '';
        $html .= $this->p(
            'Em conformidade com o artigo 117.º do Código do Procedimento Administrativo, aprovado em anexo ao Decreto-Lei n.º 4/2015, de 7 de janeiro, bem como do n.º 1 do artigo 14.º do Regulamento Municipal de Habitação Acessível, o júri deliberou proceder à notificação dos candidatos através de correio eletrónico, para que efetuassem o envio dos documentos em falta'.$deadlineText.'.'
        );

        $html .= $this->p(
            $preference !== ''
                ? $this->e($preference)
                : 'Além do envio dos documentos, deliberou ainda o júri que os candidatos que apresentaram candidatura a várias habitações deverão igualmente indicar a sua ordem de preferência, que será aplicada e tida em consideração, caso obtenham classificação e sejam elegíveis em várias habitações.'
        );

        return $html;
    }

    private function renderClosingParagraph(): string
    {
        return $this->p(
            'E nada mais havendo a tratar, deu-se por encerrada a reunião, da qual se lavrou a presente ata, que depois de lida e aprovada vai ser assinada por todos os elementos do júri.'
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderSignatureBlock(array $payload): string
    {
        $president = $this->juryPresidentName($payload);
        $vogals = $this->juryVogalNames($payload);

        $html = '<section class="signature-block">';
        $html .= '<p>A Presidente do Júri</p>';
        $html .= '<div class="signature-line"></div><p>'.$this->e($president).'</p>';
        $html .= '<p style="margin-top: 34px;">Vogais</p>';

        foreach ($vogals as $name) {
            $html .= '<div class="signature-line"></div><p>'.$this->e($name).'</p>';
        }

        return $html.'</section>';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function juryPresident(array $payload): string
    {
        $manualPresident = data_get($payload, 'ata.manual_jury.president');

        if (is_array($manualPresident)) {
            $name = trim((string) data_get($manualPresident, 'name', ''));
            $role = trim((string) data_get($manualPresident, 'role', ''));

            if ($name !== '' || $role !== '') {
                return trim($name.($role !== '' ? ', '.$role : ''));
            }
        }

        foreach ((array) data_get($payload, 'jury', []) as $member) {
            $role = mb_strtolower((string) data_get($member, 'role_in_jury', ''));

            if (str_contains($role, 'presidente')) {
                return trim((string) data_get($member, 'name', 'Presidente do Júri').' '.(string) data_get($member, 'role_in_jury', ''));
            }
        }

        return 'Presidente do Júri';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function juryVogals(array $payload): string
    {
        $manualVogals = data_get($payload, 'ata.manual_jury.vogals', []);

        if (is_array($manualVogals) && $manualVogals !== []) {
            $vogals = [];

            foreach ($manualVogals as $member) {
                if (! is_array($member)) {
                    continue;
                }

                $name = trim((string) data_get($member, 'name', ''));
                $role = trim((string) data_get($member, 'role', ''));

                if ($name !== '' || $role !== '') {
                    $vogals[] = trim($name.($role !== '' ? ', '.$role : ''));
                }
            }

            if ($vogals !== []) {
                return implode('; ', $vogals);
            }
        }

        $vogals = [];

        foreach ((array) data_get($payload, 'jury', []) as $member) {
            $role = mb_strtolower((string) data_get($member, 'role_in_jury', ''));

            if (! str_contains($role, 'presidente')) {
                $vogals[] = trim((string) data_get($member, 'name', 'Vogal').' '.(string) data_get($member, 'role_in_jury', ''));
            }
        }

        return $vogals !== [] ? implode('; ', $vogals) : 'Vogais do Júri';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function juryPresidentName(array $payload): string
    {
        $manualPresidentName = trim((string) data_get($payload, 'ata.manual_jury.president.name', ''));

        if ($manualPresidentName !== '') {
            return $manualPresidentName;
        }

        foreach ((array) data_get($payload, 'jury', []) as $member) {
            $role = mb_strtolower((string) data_get($member, 'role_in_jury', ''));

            if (str_contains($role, 'presidente')) {
                return (string) data_get($member, 'name', 'Presidente do Júri');
            }
        }

        return 'Presidente do Júri';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function juryVogalNames(array $payload): array
    {
        $manualVogals = data_get($payload, 'ata.manual_jury.vogals', []);

        if (is_array($manualVogals) && $manualVogals !== []) {
            $names = [];

            foreach ($manualVogals as $member) {
                if (! is_array($member)) {
                    continue;
                }

                $name = trim((string) data_get($member, 'name', ''));

                if ($name !== '') {
                    $names[] = $name;
                }
            }

            if ($names !== []) {
                return $names;
            }
        }

        $names = [];

        foreach ((array) data_get($payload, 'jury', []) as $member) {
            $role = mb_strtolower((string) data_get($member, 'role_in_jury', ''));

            if (! str_contains($role, 'presidente')) {
                $names[] = (string) data_get($member, 'name', 'Vogal');
            }
        }

        return $names !== [] ? $names : ['Vogal', 'Vogal', 'Vogal'];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function submissionRange(array $payload): string
    {
        $starts = null;
        $ends = null;

        foreach ((array) data_get($payload, 'deadlines', []) as $deadline) {
            $label = mb_strtolower((string) data_get($deadline, 'label', ''));
            $type = mb_strtolower((string) data_get($deadline, 'type.value', data_get($deadline, 'type', '')));

            if (str_contains($label, 'candidatura') || str_contains($type, 'application')) {
                $starts = (string) data_get($deadline, 'starts_at', '');
                $ends = (string) data_get($deadline, 'ends_at', '');
                break;
            }
        }

        if ($starts === null && $ends === null) {
            return 'no período definido no aviso de abertura';
        }

        return 'no período compreendido entre '.$this->date($starts).' e '.$this->date($ends);
    }

    private function p(string $content): string
    {
        return '<p>'.$content.'</p>';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function value(array $payload, string $key, string $default = ''): string
    {
        $value = data_get($payload, $key, $default);

        if ($value === null) {
            return $default;
        }

        return is_scalar($value) ? (string) $value : $default;
    }

    private function date(?string $value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function dateLong(?string $value): string
    {
        if (! $value) {
            return 'data a indicar';
        }

        try {
            $date = Carbon::parse($value);
            $date->locale('pt_PT');

            return $date->translatedFormat('j \d\e F \d\e Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function e(string $value): string
    {
        return e($value);
    }
}
