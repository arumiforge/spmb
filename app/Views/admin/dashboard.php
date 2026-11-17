<?php
/**
 * Temporary dashboard view. Replaced by the full-width Flowbite admin layout (phase 1, UI step).
 *
 * @var string                            $title
 * @var CodeIgniter\Shield\Entities\User  $user
 * @var list<string>                      $roles
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?> · <?= esc(setting('School.name')) ?></title>
</head>
<body>
    <h1>Selamat datang, <?= esc($user->full_name ?: $user->username) ?></h1>
    <p>Peran Anda: <?= esc(implode(', ', $roles)) ?></p>
    <p><a href="<?= url_to('logout') ?>">Keluar</a></p>
</body>
</html>
