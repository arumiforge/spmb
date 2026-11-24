<?php

use Modules\Situs\Models\SiteQuestionModel;

/**
 * Links in the public website navigation (layouts/public). Lower order comes first.
 * Labels come from the language files, so they follow the page's language (D-109).
 * "Tanya Jawab" only shows when at least one question is published.
 *
 * This file is required inside ModuleRegistry::collect(): keep variables in the
 * closure so they cannot overwrite the caller's.
 */
return (static function (): array {
    $links = [
        ['label' => lang('Situs.navRequirements'), 'url' => 'persyaratan', 'order' => 30],
        ['label' => lang('Situs.navSchedule'), 'url' => 'jadwal', 'order' => 40],
    ];

    if (model(SiteQuestionModel::class)->hasPublished()) {
        $links[] = ['label' => lang('Situs.navQa'), 'url' => 'tanya-jawab', 'order' => 85];
    }

    return $links;
})();
