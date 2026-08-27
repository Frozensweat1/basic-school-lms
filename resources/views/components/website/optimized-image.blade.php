@props(['src', 'alt', 'width' => 640, 'height' => 480, 'class' => ''])

@php
    $imageUrl = $src;
    if ($src && ! str($src)->startsWith(['http://', 'https://', '/'])) {
        $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($src);
    }

    // Create a small SVG blur placeholder (data URI)
    $placeholderColor = 'rgba(100, 116, 139, 0.3)'; // slate-500 with transparency
    $placeholderSvg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$width} {$height}'%3E%3Crect fill='{$placeholderColor}' width='{$width}' height='{$height}'/%3E%3C/svg%3E";
@endphp

<img
    src="{{ $placeholderSvg }}"
    data-src="{{ $imageUrl }}"
    alt="{{ $alt }}"
    width="{{ $width }}"
    height="{{ $height }}"
    loading="lazy"
    decoding="async"
    class="blur-up {{ $class }}"
    style="background-size: cover; background-position: center;"
    {{ $attributes }}
/>

<script>
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBlurUp);
    } else {
        initBlurUp();
    }

    function initBlurUp() {
        const images = document.querySelectorAll('img.blur-up');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;

                    if (src) {
                        img.src = src;
                        img.classList.add('loaded');
                        img.addEventListener('load', () => {
                            img.classList.remove('blur-up');
                        });
                        observer.unobserve(img);
                    }
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }
</script>

<style>
    img.blur-up {
        filter: blur(20px);
        transition: filter 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    img.blur-up.loaded {
        filter: blur(0);
    }
</style>
