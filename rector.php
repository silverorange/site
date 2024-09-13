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
	->withPhpSets(php81: true)
	->withRules([
	])
	->withSkip([
		\Rector\Php54\Rector\Array_\LongArrayToShortArrayRector::class,
		\Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
		\Rector\Php70\Rector\Ternary\TernaryToNullCoalescingRector::class,
		\Rector\Php74\Rector\Assign\NullCoalescingOperatorRector::class,
	])
	->withTypeCoverageLevel(1);
