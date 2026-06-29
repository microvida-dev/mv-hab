<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">
                    Pagamentos
                </p>

                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    {{ $payment->reference }}
                </h1>
            </div>

            <a href="{{ route('payments.edit', $payment) }}" class="mv-button-secondary">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">
                    Dados do pagamento
                </h2>

                <dl class="mt-6 grid gap-6 md:grid-cols-2">

                    <div>
                        <dt class="text-sm font-semibold text-ink-500">
                            Contrato
                        </dt>

                        <dd class="mt-1 text-sm text-ink-900">
                            #{{ $payment->contract->id }}
                            —
                            {{ $payment->contract->citizen->name }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-ink-500">
                            Habitação
                        </dt>

                        <dd class="mt-1 text-sm text-ink-900">
                            {{ $payment->contract->housingUnit->code }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-ink-500">
                            Valor
                        </dt>

                        <dd class="mt-1 text-lg font-semibold text-ink-900">
                            {{ number_format((float) $payment->amount, 2, ',', '.') }} €
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-ink-500">
                            Estado
                        </dt>

                        <dd class="mt-1">
                            <span class="rounded-2xl bg-ink-100 px-3 py-1 text-xs font-semibold text-ink-700">
                                {{ $payment->status->label() }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-ink-500">
                            Data de vencimento
                        </dt>

                        <dd class="mt-1 text-sm text-ink-900">
                            {{ $payment->due_date?->format('d/m/Y') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-ink-500">
                            Pago em
                        </dt>

                        <dd class="mt-1 text-sm text-ink-900">
                            {{ $payment->paid_at?->format('d/m/Y H:i') ?? 'Ainda não pago' }}
                        </dd>
                    </div>

                </dl>
            </section>
        </div>
    </div>
</x-app-layout>
