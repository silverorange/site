<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/po',
        __DIR__ . '/Site',
        __DIR__ . '/tests',
        __DIR__ . '/www',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php82: true)
    ->withRules([
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        MixedTypeRector::class,
        NullToStrictStringFuncCallArgRector::class,
        RemoveUnusedVariableInCatchRector::class,
    ])
    ->withTypeCoverageLevel(1);
