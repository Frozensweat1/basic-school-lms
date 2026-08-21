<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UiComponentTest extends TestCase
{
    public function test_button_component_renders_loading_target_markup(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-button variant="primary" size="md" :loading="true" target="save" text="Save changes" />
        BLADE);

        $this->assertStringContainsString('Save changes', $html);
        $this->assertStringContainsString('wire:loading', $html);
        $this->assertStringContainsString('wire:loading.flex', $html);
        $this->assertStringContainsString('wire:target="save"', $html);
        $this->assertStringContainsString('flex-nowrap', $html);
        $this->assertStringContainsString('whitespace-nowrap', $html);
    }

    public function test_modal_component_renders_with_title_and_footer(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-modal title="Add student" :show="true" max-width="lg">
                <p>Form body</p>
                <x-slot:footer>
                    <button>Close</button>
                </x-slot:footer>
            </x-modal>
        BLADE);

        $this->assertStringContainsString('Add student', $html);
        $this->assertStringContainsString('x-data', $html);
        $this->assertStringContainsString('Close', $html);
    }

    public function test_pagination_component_renders_result_summary_and_controls(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 15), 16, 15, 1, ['path' => '/lms/academic-years']);

        $html = Blade::render('<x-pagination :paginator="$paginator" />', compact('paginator'));

        $this->assertStringContainsString('Showing', $html);
        $this->assertStringContainsString('of', $html);
        $this->assertStringContainsString('Next', $html);
    }
}
