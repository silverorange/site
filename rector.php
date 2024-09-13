<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

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
		\Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector::class,
		\Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class,
		\Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class,
		\Rector\Php80\Rector\FunctionLike\MixedTypeRector::class,
		\Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class,
		\Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector::class,
	])
	->withTypeCoverageLevel(1);
