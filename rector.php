<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/.dagger/src',
    ])
    ->withSkip([
        __DIR__ . '/vendor',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withSets([
        LevelSetList::UP_TO_PHP_85,
        PHPUnitSetList::PHPUNIT_120,
    ])
    ->withSkip([
        RenameParamToMatchTypeRector::class => [
            // Avoir using undescriptive parameter name in the functions helpers
            __DIR__ . '/src/functions.php',
        ]
    ]);
