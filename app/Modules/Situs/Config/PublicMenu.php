<?php

use Modules\Situs\Models\SiteQuestionModel;

/**
 * Links in the public website navigation (layouts/public). Lower order comes first.
 * "Tanya Jawab" only shows when at least one question is published.
 *
 * This file is required inside ModuleRegistry::collect(): keep variables in the
 * closure so they cannot overwrite the caller's.
 */
return (static function (): array {
    $links = [
        ['label' => 'Persyaratan', 'url' => 'persyaratan', 'order' => 30],
        ['label' => 'Jadwal', 'url' => 'jadwal', 'order' => 40],
    ];

    if (model(SiteQuestionModel::class)->hasPublished()) {
        $links[] = ['label' => 'Tanya Jawab', 'url' => 'tanya-jawab', 'order' => 85];
    }

    return $links;
})();
