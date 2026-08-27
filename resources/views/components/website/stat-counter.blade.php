@props(['value' => 0, 'label' => '', 'icon' => null, 'suffix' => '', 'decimals' => 0])

<div {{ $attributes->class('text-center') }}>
    <div class="flex items-center justify-center">
        @if ($icon)
            <div class="mr-3 inline-flex rounded-lg p-3" style="background: rgba(var(--brand-primary-rgb), 0.1);">
                {!! $icon !!}
            </div>
        @endif

        <div class="text-4xl font-black" data-count="{{ $value }}" data-decimals="{{ $decimals }}">
            {{ number_format($value, $decimals) }}{{ $suffix }}
        </div>
    </div>

    @if ($label)
        <p class="mt-2 text-sm font-medium text-slate-600">{{ $label }}</p>
    @endif
</div>

<script>
    const animateCounter = (element) => {
        const target = parseInt(element.dataset.count);
        const decimals = parseInt(element.dataset.decimals) || 0;
        let current = 0;
        const increment = Math.ceil(target / 60);

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }

                        element.textContent = current.toLocaleString('en-US', {
                            minimumFractionDigits: decimals,
                            maximumFractionDigits: decimals,
                        });
                    }, 16);

                    observer.unobserve(entry.target);
                }
            });
        });

        observer.observe(element);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-count]').forEach(animateCounter);
        });
    } else {
        document.querySelectorAll('[data-count]').forEach(animateCounter);
    }
</script>
