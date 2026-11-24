<?php

/** Public strings of the Referensi module in English (D-109): the school and address pickers of the registration form. */
return [
    'tooMany'             => 'Too many searches. Please wait a moment and try again.',
    'pickerLabel'         => 'Previous school',
    'pickerHelp'          => 'Type the name of the school, then choose it from the list. If the school is not in the list, just write its full name.',
    'pickerPlaceholder'   => 'Example: SD Negeri 1 Jati',
    'pickerNotListed'     => 'My school is not in the list',
    'pickerNotListedHint' => 'Use the name I typed',
    'pickerNoResult'      => 'No matching school.',
    'pickerSearching'     => 'Searching…',
    'pickerChosen'        => 'Chosen from the list: {0}',
    'pickerFreeText'      => 'School name typed by you. The admission team will match it later.',
    'pickerResults'       => '{0} schools found. Use the up and down arrows to choose.',

    // Address picker of the registration form (D-111); region names are never translated.
    'regionSearchLabel'       => 'Search for your village',
    'regionSearchHelp'        => 'Type the name of the village (desa or kelurahan), then choose it from the list. The province, regency, district and village are filled in for you.',
    'regionSearchPlaceholder' => 'Example: Ploso, Jati',
    'regionSearchNoResult'    => 'Nothing matches. Try another word, or choose from the lists below.',
    'regionSearchResults'     => '{0} places found. Use the up and down arrows to choose.',
    'regionChosen'            => 'Chosen: {0}',
    'regionOr'                => 'or choose step by step',
    'regionChoose'            => [
        'province' => 'Choose the province',
        'regency'  => 'Choose the regency or city',
        'district' => 'Choose the district',
        'village'  => 'Choose the village',
    ],
    'regionChooseFirst' => [
        'province' => '',
        'regency'  => 'Choose the province first',
        'district' => 'Choose the regency or city first',
        'village'  => 'Choose the district first',
    ],
    'regionLevels' => [
        'province' => 'province',
        'regency'  => 'regency or city',
        'district' => 'district',
        'village'  => 'village',
    ],
    'regionKinds' => [
        'desa'      => 'Village (desa)',
        'kelurahan' => 'Village (kelurahan)',
        'district'  => 'District (kecamatan)',
    ],
    'regionLoading'    => 'Loading the list…',
    'regionLoadFailed' => 'The list could not be loaded. Check your internet connection, or type the address yourself.',
    'regionTypeOwn'    => 'My place is not in the list, I will type it',
    'regionUseList'    => 'Choose from the list instead',
    'regionTypeNote'   => 'Write the village, district, regency or city and province as on the family card (Kartu Keluarga).',
    'regionUnknown'    => 'The chosen {field} is not in the list. Please choose again.',
    'regionMismatch'   => 'The chosen parts of the address do not belong together. Please choose again, starting with the province.',
    'regionBadCode'    => 'Unknown region code.',
];
