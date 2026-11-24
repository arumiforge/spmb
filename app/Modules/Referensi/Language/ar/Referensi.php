<?php

/** Public strings of the Referensi module in Arabic (D-109): the school and address pickers of the registration form. */
return [
    'tooMany'             => 'عمليات بحث كثيرة. انتظر قليلًا ثم حاول مرة أخرى.',
    'pickerLabel'         => 'المدرسة السابقة',
    'pickerHelp'          => 'اكتب اسم المدرسة ثم اخترها من القائمة. وإذا لم تكن المدرسة في القائمة، فاكتب اسمها الكامل فقط.',
    'pickerPlaceholder'   => 'مثال: SD Negeri 1 Jati',
    'pickerNotListed'     => 'مدرستي ليست في القائمة',
    'pickerNotListedHint' => 'استخدم الاسم الذي كتبته',
    'pickerNoResult'      => 'لا توجد مدرسة مطابقة.',
    'pickerSearching'     => 'جارٍ البحث…',
    'pickerChosen'        => 'اختيرت من القائمة: {0}',
    'pickerFreeText'      => 'كتبتَ اسم المدرسة بنفسك، وستطابقه لجنة القبول لاحقًا.',
    'pickerResults'       => 'عدد المدارس التي عُثر عليها: {0}. استخدم السهمين للأعلى وللأسفل للاختيار.',

    // Address picker of the registration form (D-111); region names are never translated.
    'regionSearchLabel'       => 'ابحث عن القرية',
    'regionSearchHelp'        => 'اكتب اسم القرية (desa أو kelurahan) ثم اخترها من القائمة، وستُملأ المحافظة والمدينة والناحية والقرية تلقائيًا.',
    'regionSearchPlaceholder' => 'مثال: Ploso, Jati',
    'regionSearchNoResult'    => 'لا توجد نتيجة مطابقة. جرّب كلمة أخرى، أو اختر من القوائم أدناه.',
    'regionSearchResults'     => 'عدد الأماكن التي عُثر عليها: {0}. استخدم السهمين للأعلى وللأسفل للاختيار.',
    'regionChosen'            => 'تم الاختيار: {0}',
    'regionOr'                => 'أو اختر خطوة بخطوة',
    'regionChoose'            => [
        'province' => 'اختر المحافظة',
        'regency'  => 'اختر المنطقة أو المدينة',
        'district' => 'اختر الناحية',
        'village'  => 'اختر القرية',
    ],
    'regionChooseFirst' => [
        'province' => '',
        'regency'  => 'اختر المحافظة أولًا',
        'district' => 'اختر المنطقة أو المدينة أولًا',
        'village'  => 'اختر الناحية أولًا',
    ],
    'regionLevels' => [
        'province' => 'المحافظة',
        'regency'  => 'المنطقة أو المدينة',
        'district' => 'الناحية',
        'village'  => 'القرية',
    ],
    'regionKinds' => [
        'desa'      => 'قرية (desa)',
        'kelurahan' => 'حي (kelurahan)',
        'district'  => 'ناحية (kecamatan)',
    ],
    'regionLoading'    => 'جارٍ تحميل القائمة…',
    'regionLoadFailed' => 'تعذّر تحميل القائمة. تحقّق من اتصال الإنترنت، أو اكتب العنوان بنفسك.',
    'regionTypeOwn'    => 'مكاني ليس في القائمة، سأكتبه بنفسي',
    'regionUseList'    => 'الاختيار من القائمة بدلًا من ذلك',
    'regionTypeNote'   => 'اكتب القرية والناحية والمنطقة أو المدينة والمحافظة كما في بطاقة العائلة (Kartu Keluarga).',
    'regionUnknown'    => '{field} المختارة ليست في القائمة. يرجى الاختيار مرة أخرى.',
    'regionMismatch'   => 'أجزاء العنوان المختارة لا تتوافق معًا. يرجى الاختيار من جديد بدءًا من المحافظة.',
    'regionBadCode'    => 'رمز منطقة غير معروف.',
];
