<?php

/** Links in the public website navigation (layouts/public). Lower order comes first; labels follow the page's language. */
return [
    ['label' => lang('Pendaftaran.navRegister'), 'url' => 'daftar', 'order' => 20],
    ['label' => lang('Pendaftaran.navCheck'), 'url' => 'cek-status', 'order' => 50],
];
