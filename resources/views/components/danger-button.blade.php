<button {{ $attributes->merge(['type' => 'submit', 'class' => 'mv-button-danger']) }}>
    {{ $slot }}
</button>
