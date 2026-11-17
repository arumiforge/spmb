<?php

declare(strict_types=1);

use CodeIgniter\CodingStandard\CodeIgniter4;
use Nexus\CsConfig\Factory;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->files()
    ->in([__DIR__ . '/app', __DIR__ . '/tests'])
    ->exclude(['Views', 'ThirdParty'])
    ->append([__FILE__, __DIR__ . '/spark', __DIR__ . '/preload.php']);

return Factory::create(new CodeIgniter4(), [], ['finder' => $finder])->forProjects();
