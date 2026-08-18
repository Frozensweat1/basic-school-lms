<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
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
        $this->assertStringContainsString('wire:target="save"', $html);
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
}
