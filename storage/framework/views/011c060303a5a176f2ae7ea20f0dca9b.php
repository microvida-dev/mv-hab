<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label',
    'value',
    'description' => null,
    'currency' => false,
    'icon' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'label',
    'value',
    'description' => null,
    'currency' => false,
    'icon' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="mv-surface p-5">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-ink-500"><?php echo e($label); ?></p>
            <p class="mt-3 text-3xl font-semibold text-ink-900">
                <?php if($currency): ?>
                    <?php echo e(number_format((float) $value, 2, ',', '.')); ?> €
                <?php else: ?>
                    <?php echo e(number_format((int) $value, 0, ',', '.')); ?>

                <?php endif; ?>
            </p>
        </div>

        <?php if($icon): ?>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                <?php if (isset($component)) { $__componentOriginalaa25fa354301adf40df60a26b9586efa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa25fa354301adf40df60a26b9586efa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui-icon','data' => ['name' => $icon,'class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $attributes = $__attributesOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__attributesOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $component = $__componentOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__componentOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if($description): ?>
        <p class="mt-3 text-sm leading-5 text-ink-500"><?php echo e($description); ?></p>
    <?php endif; ?>
</div>
<?php /**PATH /Users/brunocorreia/Documents/CRM HAB/MV-HAB/resources/views/components/stat-card.blade.php ENDPATH**/ ?>