<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;

/**
 * partials/stepper: states of the numbered steps (done, current, upcoming, error).
 *
 * @internal
 */
final class StepperPartialTest extends CIUnitTestCase
{
    private function render(int $current, array $errors = []): string
    {
        $steps = [];

        foreach (['Data calon murid', 'Alamat', 'Orang tua', 'Periksa'] as $i => $title) {
            $steps[] = ['id' => 'langkah-' . ($i + 1), 'title' => $title, 'short' => $title, 'error' => in_array($i + 1, $errors, true)];
        }

        return view('partials/stepper', ['stepper' => ['label' => 'Langkah pengisian formulir', 'steps' => $steps, 'current' => $current]]);
    }

    public function testRendersDoneCurrentAndUpcomingSteps(): void
    {
        $html = $this->render(2);

        $this->assertStringContainsString('aria-label="' . esc('Langkah pengisian formulir', 'attr') . '"', $html);
        $this->assertStringContainsString('<ol class="stepper-daftar"', $html);
        $this->assertMatchesRegularExpression('/stepper-item is-selesai" data-stepper-item="1"/', $html);
        $this->assertMatchesRegularExpression('/stepper-item is-aktif" data-stepper-item="2"/', $html);
        $this->assertMatchesRegularExpression('/stepper-item is-nanti" data-stepper-item="3"/', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="step"'));
        $this->assertMatchesRegularExpression('/href="#langkah-2"[^>]*aria-current="step"/', $html);
        $this->assertStringContainsString('Langkah 2 dari 4', $html);
        $this->assertStringContainsString('--kemajuan: 50%', $html);
        // The state is written out for screen readers, not shown by colour only.
        $this->assertStringContainsString(', sudah diisi', $html);
        $this->assertStringContainsString(', langkah sekarang', $html);
        $this->assertStringContainsString(', belum diisi', $html);
        $this->assertStringNotContainsString('has-error', $html);
    }

    public function testMarksStepsWithErrorsAndClampsTheCurrentStep(): void
    {
        $html = $this->render(9, [1, 3]);

        $this->assertMatchesRegularExpression('/stepper-item is-selesai has-error" data-stepper-item="1"/', $html);
        $this->assertMatchesRegularExpression('/stepper-item is-selesai has-error" data-stepper-item="3"/', $html);
        $this->assertMatchesRegularExpression('/stepper-item is-aktif" data-stepper-item="4"/', $html);
        $this->assertSame(2, substr_count($html, ', ada isian yang perlu diperbaiki'));
        $this->assertStringContainsString('Langkah 4 dari 4', $html);
    }
}
