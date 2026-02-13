<?php
/**
 * Rector rules to automatically refactor code to modern syntax.
 *
 * @package brianhenryie/bh-wp-logger
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

return RectorConfig::configure()
	->withPaths(
		array(
			__DIR__ . '/includes',
			__DIR__ . '/development-plugin',
			__DIR__ . '/tests/integration',
			__DIR__ . '/tests/unit',
			__DIR__ . '/tests/wpunit',
		)
	)
	->withSkip(
		array(
			LongArrayToShortArrayRector::class, // WPCS says to use long array syntax.
			ChangeSwitchToMatchRector::class,
			ArrayToFirstClassCallableRector::class, // I don't know how to test the new syntax with `WP_Mock::expectActionAdded()`.
		)
	)
	->withPhpSets(
		php84: true,
	)
	->withPreparedSets(
		deadCode: false,
		codeQuality: false,
		codingStyle: false,
		typeDeclarations: false,
		privatization: false,
		naming: false,
		instanceOf: false,
		earlyReturn: false,
		strictBooleans: false,
	);
