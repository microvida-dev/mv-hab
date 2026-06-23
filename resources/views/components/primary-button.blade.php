<button {{ $attributes->merge(['type' => 'submit', 'class' => 'mv-button-primary']) }}>
    {{ $slot }}
</button>
