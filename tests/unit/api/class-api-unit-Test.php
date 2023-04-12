<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass  \BrianHenryIE\WP_Logger\API\API
 */
class API_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		parent::setup();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::get_backtrace
	 */
	public function test_backtrace_excludes_logger_files(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$api = new API( $settings, $logger );

		$result = $api->get_backtrace();

		$this->assertEquals( $result[0]->file, __FILE__ );

	}


	/**
	 * @covers ::parse_log
	 * @covers ::log_lines_to_entry
	 */
	public function test_parse_logs_simple(): void {

		global $project_root_dir;

		$simple_log_file = $project_root_dir . '/tests/_data/simple-log-8-lines.log';

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$sut = new API( $settings, $logger );

		$result = $sut->parse_log( $simple_log_file );

		$this->assertCount( 8, $result );

	}

	/**
	 * A log message could span multiple lines, e.g. fatal error backtrace.
	 *
	 * @covers ::parse_log
	 * @covers ::log_lines_to_entry
	 */
	public function test_parse_logs_multiline_message(): void {

		global $project_root_dir;

		$multiline_message_log_file = $project_root_dir . '/tests/_data/context-not-rendering.log';

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$sut = new API( $settings, $logger );

		$result = $sut->parse_log( $multiline_message_log_file );

		$this->assertCount( 15, $result );

	}

	/**
	 * @covers ::get_last_log_time
	 */
	public function test_get_last_log_time(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test',
			)
		);

		$sut = new class( $settings, $logger ) extends API {
			public function get_log_files( ?string $date = null ): array {
				$logfile_contents = <<<'EOD'
2022-02-23T22:55:46+00:00 ERROR test_backtrace_excludes_logger_files
{"debug_backtrace":[{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/src\/API\/class-bh-wp-psr-logger.php","lineNumber":183,"arguments":["error","test_backtrace_excludes_logger_files",[]],"applicationFrame":true,"method":"log","class":"BrianHenryIE\\WP_Logger\\API\\BH_WP_PSR_Logger"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/src\/API\/class-bh-wp-psr-logger.php","lineNumber":144,"arguments":["test_backtrace_excludes_logger_files"],"applicationFrame":true,"method":"error","class":"BrianHenryIE\\WP_Logger\\API\\BH_WP_PSR_Logger"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/tests\/integration\/API\/class-bh-wp-psr-logger-integration-Test.php","lineNumber":21,"arguments":[],"applicationFrame":true,"method":"test_backtrace_excludes_logger_files","class":"BrianHenryIE\\WP_Logger\\API\\BH_WP_PSR_Logger_Integration_Test"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php","lineNumber":1545,"arguments":[],"applicationFrame":true,"method":"runTest","class":"PHPUnit\\Framework\\TestCase"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php","lineNumber":1151,"arguments":[],"applicationFrame":true,"method":"runBare","class":"PHPUnit\\Framework\\TestCase"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestResult.php","lineNumber":726,"arguments":[{"tester":{}}],"applicationFrame":true,"method":"run","class":"PHPUnit\\Framework\\TestResult"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php","lineNumber":903,"arguments":[{}],"applicationFrame":true,"method":"run","class":"PHPUnit\\Framework\\TestCase"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestSuite.php","lineNumber":677,"arguments":[{}],"applicationFrame":true,"method":"run","class":"PHPUnit\\Framework\\TestSuite"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/phpunit-wrapper\/src\/Runner.php","lineNumber":117,"arguments":[{},{},{"silent":true,"debug":false,"steps":false,"html":false,"xml":false,"phpunit-xml":false,"no-redirect":true,"json":false,"tap":false,"report":true,"colors":true,"coverage":false,"coverage-xml":false,"coverage-html":false,"coverage-text":false,"coverage-crap4j":false,"coverage-cobertura":false,"coverage-phpunit":false,"groups":[],"excludeGroups":[],"filter":"test_backtrace_excludes_logger_files","env":null,"fail-fast":false,"ansi":true,"verbosity":32,"interactive":false,"no-rebuild":false,"quiet":false,"bootstrap":false,"strict_xml":false,"lint":true,"backup_globals":true,"log_incomplete_skipped":false,"report_useless_tests":false,"disallow_test_output":false,"be_strict_about_changes_to_global_state":false,"shuffle":false,"override":["reporters: report: PhpStorm_Codeception_ReportPrinter"],"no-interaction":true,"seed":1493104163,"listeners":[{}],"addUncoveredFilesFromWhitelist":true,"backupGlobals":null,"backupStaticAttributes":null,"beStrictAboutChangesToGlobalState":null,"beStrictAboutResourceUsageDuringSmallTests":false,"cacheResult":true,"cacheTokens":false,"columns":80,"convertDeprecationsToExceptions":true,"convertErrorsToExceptions":true,"convertNoticesToExceptions":true,"convertWarningsToExceptions":true,"crap4jThreshold":30,"disallowTestOutput":false,"disallowTodoAnnotatedTests":false,"defaultTimeLimit":0,"enforceTimeLimit":false,"failOnRisky":false,"failOnWarning":false,"executionOrderDefects":0,"processIsolation":false,"processUncoveredFilesFromWhitelist":false,"randomOrderSeed":1645656946,"registerMockObjectsFromTestArgumentsRecursively":false,"repeat":false,"reportHighLowerBound":90,"reportLowUpperBound":50,"reportUselessTests":true,"reverseList":false,"executionOrder":0,"resolveDependencies":true,"stopOnError":false,"stopOnFailure":false,"stopOnIncomplete":false,"stopOnRisky":false,"stopOnSkipped":false,"stopOnWarning":false,"stopOnDefect":false,"strictCoverage":false,"testdoxExcludeGroups":[],"testdoxGroups":[],"timeoutForLargeTests":60,"timeoutForMediumTests":10,"timeoutForSmallTests":1,"verbose":false}],"applicationFrame":true,"method":"doEnhancedRun","class":"Codeception\\PHPUnit\\Runner"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/SuiteManager.php","lineNumber":161,"arguments":[{},{},{"silent":true,"debug":false,"steps":false,"html":false,"xml":false,"phpunit-xml":false,"no-redirect":true,"json":false,"tap":false,"report":true,"colors":true,"coverage":false,"coverage-xml":false,"coverage-html":false,"coverage-text":false,"coverage-crap4j":false,"coverage-cobertura":false,"coverage-phpunit":false,"groups":[],"excludeGroups":[],"filter":"test_backtrace_excludes_logger_files","env":null,"fail-fast":false,"ansi":true,"verbosity":32,"interactive":false,"no-rebuild":false,"quiet":false,"bootstrap":false,"strict_xml":false,"lint":true,"backup_globals":true,"log_incomplete_skipped":false,"report_useless_tests":false,"disallow_test_output":false,"be_strict_about_changes_to_global_state":false,"shuffle":false,"override":["reporters: report: PhpStorm_Codeception_ReportPrinter"],"no-interaction":true,"seed":1493104163,"listeners":[],"addUncoveredFilesFromWhitelist":true,"backupGlobals":null,"backupStaticAttributes":null,"beStrictAboutChangesToGlobalState":null,"beStrictAboutResourceUsageDuringSmallTests":false,"cacheResult":true,"cacheTokens":false,"columns":80,"convertDeprecationsToExceptions":true,"convertErrorsToExceptions":true,"convertNoticesToExceptions":true,"convertWarningsToExceptions":true,"crap4jThreshold":30,"disallowTestOutput":false,"disallowTodoAnnotatedTests":false,"defaultTimeLimit":0,"enforceTimeLimit":false,"failOnRisky":false,"failOnWarning":false,"executionOrderDefects":0,"processIsolation":false,"processUncoveredFilesFromWhitelist":false,"randomOrderSeed":1645656946,"registerMockObjectsFromTestArgumentsRecursively":false,"repeat":false,"reportHighLowerBound":90,"reportLowUpperBound":50,"reportUselessTests":true,"reverseList":false,"executionOrder":0,"resolveDependencies":true,"stopOnError":false,"stopOnFailure":false,"stopOnIncomplete":false,"stopOnRisky":false,"stopOnSkipped":false,"stopOnWarning":false,"stopOnDefect":false,"strictCoverage":false,"testdoxExcludeGroups":[],"testdoxGroups":[],"timeoutForLargeTests":60,"timeoutForMediumTests":10,"timeoutForSmallTests":1,"verbose":false}],"applicationFrame":true,"method":"run","class":"Codeception\\SuiteManager"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/Codecept.php","lineNumber":208,"arguments":[{"actor":"WpunitTester","modules":{"enabled":["WPLoader","\\Helper\\Wpunit"],"config":{"WPLoader":{"wpRootFolder":"WordPress","dbName":"bh_wp_logger_integration","dbHost":"127.0.0.1","dbUser":"bh-wp-logger","dbPassword":"bh-wp-logger","tablePrefix":"wp_","domain":"localhost","adminEmail":"email@example.org","title":"bh-wp-logger-test-plugin","plugins":["bh-wp-logger-test-plugin\/bh-wp-logger-test-plugin.php"],"activatePlugins":["bh-wp-logger-test-plugin\/bh-wp-logger-test-plugin.php"]}},"depends":[]},"bootstrap":"_bootstrap.php","colors":true,"strict_xml":false,"lint":true,"backup_globals":true,"log_incomplete_skipped":false,"report_useless_tests":false,"disallow_test_output":false,"be_strict_about_changes_to_global_state":false,"shuffle":false,"coverage":{"enabled":true,"include":["src\/*"],"exclude":["src\/dependencies\/*","\/*\/interface*.php","src\/vendor\/*","\/*\/index.php","\/*\/*.txt","src\/autoload.php","\/*\/*.css","\/*\/*.js"]},"namespace":"","groups":[],"gherkin":[],"extensions":{"enabled":["Codeception\\Extension\\RunFailed"],"commands":["Codeception\\Command\\GenerateWPUnit","Codeception\\Command\\GenerateWPRestApi","Codeception\\Command\\GenerateWPRestController","Codeception\\Command\\GenerateWPRestPostTypeController","Codeception\\Command\\GenerateWPAjax","Codeception\\Command\\GenerateWPCanonical","Codeception\\Command\\GenerateWPXMLRPC"],"config":[]},"class_name":null,"step_decorators":"Codeception\\Step\\ConditionalAssertion","path":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/tests\/integration\/","extends":null,"formats":[],"error_level":"E_ALL & ~E_STRICT & ~E_DEPRECATED"},"integration","API\/class-bh-wp-psr-logger-integration-Test.php"],"applicationFrame":true,"method":"runSuite","class":"Codeception\\Codecept"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/Codecept.php","lineNumber":162,"arguments":["integration","API\/class-bh-wp-psr-logger-integration-Test.php",{"actor":"WpunitTester","modules":{"enabled":["WPLoader","\\Helper\\Wpunit"],"config":{"WPLoader":{"wpRootFolder":"WordPress","dbName":"bh_wp_logger_integration","dbHost":"127.0.0.1","dbUser":"bh-wp-logger","dbPassword":"bh-wp-logger","tablePrefix":"wp_","domain":"localhost","adminEmail":"email@example.org","title":"bh-wp-logger-test-plugin","plugins":["bh-wp-logger-test-plugin\/bh-wp-logger-test-plugin.php"],"activatePlugins":["bh-wp-logger-test-plugin\/bh-wp-logger-test-plugin.php"]}},"depends":[]},"bootstrap":"_bootstrap.php","colors":true,"strict_xml":false,"lint":true,"backup_globals":true,"log_incomplete_skipped":false,"report_useless_tests":false,"disallow_test_output":false,"be_strict_about_changes_to_global_state":false,"shuffle":false,"coverage":{"enabled":true,"include":["src\/*"],"exclude":["src\/dependencies\/*","\/*\/interface*.php","src\/vendor\/*","\/*\/index.php","\/*\/*.txt","src\/autoload.php","\/*\/*.css","\/*\/*.js"]},"namespace":"","groups":[],"gherkin":[],"extensions":{"enabled":["Codeception\\Extension\\RunFailed"],"commands":["Codeception\\Command\\GenerateWPUnit","Codeception\\Command\\GenerateWPRestApi","Codeception\\Command\\GenerateWPRestController","Codeception\\Command\\GenerateWPRestPostTypeController","Codeception\\Command\\GenerateWPAjax","Codeception\\Command\\GenerateWPCanonical","Codeception\\Command\\GenerateWPXMLRPC"],"config":[]},"class_name":null,"step_decorators":"Codeception\\Step\\ConditionalAssertion","path":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/tests\/integration\/","extends":null,"formats":[],"error_level":"E_ALL & ~E_STRICT & ~E_DEPRECATED"}],"applicationFrame":true,"method":"run","class":"Codeception\\Codecept"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/Command\/Run.php","lineNumber":402,"arguments":[{},{}],"applicationFrame":true,"method":"execute","class":"Codeception\\Command\\Run"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/symfony\/console\/Command\/Command.php","lineNumber":298,"arguments":[{},{}],"applicationFrame":true,"method":"run","class":"Symfony\\Component\\Console\\Command\\Command"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/symfony\/console\/Application.php","lineNumber":1015,"arguments":[{},{},{}],"applicationFrame":true,"method":"doRunCommand","class":"Symfony\\Component\\Console\\Application"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/symfony\/console\/Application.php","lineNumber":299,"arguments":[{},{}],"applicationFrame":true,"method":"doRun","class":"Symfony\\Component\\Console\\Application"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/symfony\/console\/Application.php","lineNumber":171,"arguments":[{},{}],"applicationFrame":true,"method":"run","class":"Symfony\\Component\\Console\\Application"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/Application.php","lineNumber":117,"arguments":[],"applicationFrame":true,"method":"run","class":"Codeception\\Application"},{"file":"\/private\/var\/folders\/sh\/cygymmqn36714790jj3r33200000gn\/T\/ide-codeception.php","lineNumber":51,"arguments":[],"applicationFrame":false,"method":"[top]","class":null}],"filters":[]}
2022-02-23T22:56:04+00:00 INFO Registered the `private_uploads_check_url_bh-wp-logger-test-plugin_logger` cron job.
[]
2022-02-23T22:56:04+00:00 ERROR test_backtrace_excludes_logger_files
{"debug_backtrace":[{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/tests\/integration\/API\/class-bh-wp-psr-logger-integration-Test.php","lineNumber":21,"arguments":[],"applicationFrame":true,"method":"test_backtrace_excludes_logger_files","class":"BrianHenryIE\\WP_Logger\\API\\BH_WP_PSR_Logger_Integration_Test"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php","lineNumber":1545,"arguments":[],"applicationFrame":true,"method":"runTest","class":"PHPUnit\\Framework\\TestCase"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php","lineNumber":1151,"arguments":[],"applicationFrame":true,"method":"runBare","class":"PHPUnit\\Framework\\TestCase"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestResult.php","lineNumber":726,"arguments":[{"tester":{}}],"applicationFrame":true,"method":"run","class":"PHPUnit\\Framework\\TestResult"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php","lineNumber":903,"arguments":[{}],"applicationFrame":true,"method":"run","class":"PHPUnit\\Framework\\TestCase"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestSuite.php","lineNumber":677,"arguments":[{}],"applicationFrame":true,"method":"run","class":"PHPUnit\\Framework\\TestSuite"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/phpunit-wrapper\/src\/Runner.php","lineNumber":117,"arguments":[{},{},{"silent":true,"debug":false,"steps":false,"html":false,"xml":false,"phpunit-xml":false,"no-redirect":true,"json":false,"tap":false,"report":true,"colors":true,"coverage":false,"coverage-xml":false,"coverage-html":false,"coverage-text":false,"coverage-crap4j":false,"coverage-cobertura":false,"coverage-phpunit":false,"groups":[],"excludeGroups":[],"filter":"test_backtrace_excludes_logger_files","env":null,"fail-fast":false,"ansi":true,"verbosity":32,"interactive":false,"no-rebuild":false,"quiet":false,"bootstrap":false,"strict_xml":false,"lint":true,"backup_globals":true,"log_incomplete_skipped":false,"report_useless_tests":false,"disallow_test_output":false,"be_strict_about_changes_to_global_state":false,"shuffle":false,"override":["reporters: report: PhpStorm_Codeception_ReportPrinter"],"no-interaction":true,"seed":1369841876,"listeners":[{}],"addUncoveredFilesFromWhitelist":true,"backupGlobals":null,"backupStaticAttributes":null,"beStrictAboutChangesToGlobalState":null,"beStrictAboutResourceUsageDuringSmallTests":false,"cacheResult":true,"cacheTokens":false,"columns":80,"convertDeprecationsToExceptions":true,"convertErrorsToExceptions":true,"convertNoticesToExceptions":true,"convertWarningsToExceptions":true,"crap4jThreshold":30,"disallowTestOutput":false,"disallowTodoAnnotatedTests":false,"defaultTimeLimit":0,"enforceTimeLimit":false,"failOnRisky":false,"failOnWarning":false,"executionOrderDefects":0,"processIsolation":false,"processUncoveredFilesFromWhitelist":false,"randomOrderSeed":1645656964,"registerMockObjectsFromTestArgumentsRecursively":false,"repeat":false,"reportHighLowerBound":90,"reportLowUpperBound":50,"reportUselessTests":true,"reverseList":false,"executionOrder":0,"resolveDependencies":true,"stopOnError":false,"stopOnFailure":false,"stopOnIncomplete":false,"stopOnRisky":false,"stopOnSkipped":false,"stopOnWarning":false,"stopOnDefect":false,"strictCoverage":false,"testdoxExcludeGroups":[],"testdoxGroups":[],"timeoutForLargeTests":60,"timeoutForMediumTests":10,"timeoutForSmallTests":1,"verbose":false}],"applicationFrame":true,"method":"doEnhancedRun","class":"Codeception\\PHPUnit\\Runner"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/SuiteManager.php","lineNumber":161,"arguments":[{},{},{"silent":true,"debug":false,"steps":false,"html":false,"xml":false,"phpunit-xml":false,"no-redirect":true,"json":false,"tap":false,"report":true,"colors":true,"coverage":false,"coverage-xml":false,"coverage-html":false,"coverage-text":false,"coverage-crap4j":false,"coverage-cobertura":false,"coverage-phpunit":false,"groups":[],"excludeGroups":[],"filter":"test_backtrace_excludes_logger_files","env":null,"fail-fast":false,"ansi":true,"verbosity":32,"interactive":false,"no-rebuild":false,"quiet":false,"bootstrap":false,"strict_xml":false,"lint":true,"backup_globals":true,"log_incomplete_skipped":false,"report_useless_tests":false,"disallow_test_output":false,"be_strict_about_changes_to_global_state":false,"shuffle":false,"override":["reporters: report: PhpStorm_Codeception_ReportPrinter"],"no-interaction":true,"seed":1369841876,"listeners":[],"addUncoveredFilesFromWhitelist":true,"backupGlobals":null,"backupStaticAttributes":null,"beStrictAboutChangesToGlobalState":null,"beStrictAboutResourceUsageDuringSmallTests":false,"cacheResult":true,"cacheTokens":false,"columns":80,"convertDeprecationsToExceptions":true,"convertErrorsToExceptions":true,"convertNoticesToExceptions":true,"convertWarningsToExceptions":true,"crap4jThreshold":30,"disallowTestOutput":false,"disallowTodoAnnotatedTests":false,"defaultTimeLimit":0,"enforceTimeLimit":false,"failOnRisky":false,"failOnWarning":false,"executionOrderDefects":0,"processIsolation":false,"processUncoveredFilesFromWhitelist":false,"randomOrderSeed":1645656964,"registerMockObjectsFromTestArgumentsRecursively":false,"repeat":false,"reportHighLowerBound":90,"reportLowUpperBound":50,"reportUselessTests":true,"reverseList":false,"executionOrder":0,"resolveDependencies":true,"stopOnError":false,"stopOnFailure":false,"stopOnIncomplete":false,"stopOnRisky":false,"stopOnSkipped":false,"stopOnWarning":false,"stopOnDefect":false,"strictCoverage":false,"testdoxExcludeGroups":[],"testdoxGroups":[],"timeoutForLargeTests":60,"timeoutForMediumTests":10,"timeoutForSmallTests":1,"verbose":false}],"applicationFrame":true,"method":"run","class":"Codeception\\SuiteManager"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/Codecept.php","lineNumber":208,"arguments":[{"actor":"WpunitTester","modules":{"enabled":["WPLoader","\\Helper\\Wpunit"],"config":{"WPLoader":{"wpRootFolder":"WordPress","dbName":"bh_wp_logger_integration","dbHost":"127.0.0.1","dbUser":"bh-wp-logger","dbPassword":"bh-wp-logger","tablePrefix":"wp_","domain":"localhost","adminEmail":"email@example.org","title":"bh-wp-logger-test-plugin","plugins":["bh-wp-logger-test-plugin\/bh-wp-logger-test-plugin.php"],"activatePlugins":["bh-wp-logger-test-plugin\/bh-wp-logger-test-plugin.php"]}},"depends":[]},"bootstrap":"_bootstrap.php","colors":true,"strict_xml":false,"lint":true,"backup_globals":true,"log_incomplete_skipped":false,"report_useless_tests":false,"disallow_test_output":false,"be_strict_about_changes_to_global_state":false,"shuffle":false,"coverage":{"enabled":true,"include":["src\/*"],"exclude":["src\/dependencies\/*","\/*\/interface*.php","src\/vendor\/*","\/*\/index.php","\/*\/*.txt","src\/autoload.php","\/*\/*.css","\/*\/*.js"]},"namespace":"","groups":[],"gherkin":[],"extensions":{"enabled":["Codeception\\Extension\\RunFailed"],"commands":["Codeception\\Command\\GenerateWPUnit","Codeception\\Command\\GenerateWPRestApi","Codeception\\Command\\GenerateWPRestController","Codeception\\Command\\GenerateWPRestPostTypeController","Codeception\\Command\\GenerateWPAjax","Codeception\\Command\\GenerateWPCanonical","Codeception\\Command\\GenerateWPXMLRPC"],"config":[]},"class_name":null,"step_decorators":"Codeception\\Step\\ConditionalAssertion","path":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/tests\/integration\/","extends":null,"formats":[],"error_level":"E_ALL & ~E_STRICT & ~E_DEPRECATED"},"integration","API\/class-bh-wp-psr-logger-integration-Test.php"],"applicationFrame":true,"method":"runSuite","class":"Codeception\\Codecept"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/Codecept.php","lineNumber":162,"arguments":["integration","API\/class-bh-wp-psr-logger-integration-Test.php",{"actor":"WpunitTester","modules":{"enabled":["WPLoader","\\Helper\\Wpunit"],"config":{"WPLoader":{"wpRootFolder":"WordPress","dbName":"bh_wp_logger_integration","dbHost":"127.0.0.1","dbUser":"bh-wp-logger","dbPassword":"bh-wp-logger","tablePrefix":"wp_","domain":"localhost","adminEmail":"email@example.org","title":"bh-wp-logger-test-plugin","plugins":["bh-wp-logger-test-plugin\/bh-wp-logger-test-plugin.php"],"activatePlugins":["bh-wp-logger-test-plugin\/bh-wp-logger-test-plugin.php"]}},"depends":[]},"bootstrap":"_bootstrap.php","colors":true,"strict_xml":false,"lint":true,"backup_globals":true,"log_incomplete_skipped":false,"report_useless_tests":false,"disallow_test_output":false,"be_strict_about_changes_to_global_state":false,"shuffle":false,"coverage":{"enabled":true,"include":["src\/*"],"exclude":["src\/dependencies\/*","\/*\/interface*.php","src\/vendor\/*","\/*\/index.php","\/*\/*.txt","src\/autoload.php","\/*\/*.css","\/*\/*.js"]},"namespace":"","groups":[],"gherkin":[],"extensions":{"enabled":["Codeception\\Extension\\RunFailed"],"commands":["Codeception\\Command\\GenerateWPUnit","Codeception\\Command\\GenerateWPRestApi","Codeception\\Command\\GenerateWPRestController","Codeception\\Command\\GenerateWPRestPostTypeController","Codeception\\Command\\GenerateWPAjax","Codeception\\Command\\GenerateWPCanonical","Codeception\\Command\\GenerateWPXMLRPC"],"config":[]},"class_name":null,"step_decorators":"Codeception\\Step\\ConditionalAssertion","path":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/tests\/integration\/","extends":null,"formats":[],"error_level":"E_ALL & ~E_STRICT & ~E_DEPRECATED"}],"applicationFrame":true,"method":"run","class":"Codeception\\Codecept"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/Command\/Run.php","lineNumber":402,"arguments":[{},{}],"applicationFrame":true,"method":"execute","class":"Codeception\\Command\\Run"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/symfony\/console\/Command\/Command.php","lineNumber":298,"arguments":[{},{}],"applicationFrame":true,"method":"run","class":"Symfony\\Component\\Console\\Command\\Command"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/symfony\/console\/Application.php","lineNumber":1015,"arguments":[{},{},{}],"applicationFrame":true,"method":"doRunCommand","class":"Symfony\\Component\\Console\\Application"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/symfony\/console\/Application.php","lineNumber":299,"arguments":[{},{}],"applicationFrame":true,"method":"doRun","class":"Symfony\\Component\\Console\\Application"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/symfony\/console\/Application.php","lineNumber":171,"arguments":[{},{}],"applicationFrame":true,"method":"run","class":"Symfony\\Component\\Console\\Application"},{"file":"\/Users\/brianhenry\/Sites\/bh-wp-logger\/vendor\/codeception\/codeception\/src\/Codeception\/Application.php","lineNumber":117,"arguments":[],"applicationFrame":true,"method":"run","class":"Codeception\\Application"},{"file":"\/private\/var\/folders\/sh\/cygymmqn36714790jj3r33200000gn\/T\/ide-codeception.php","lineNumber":51,"arguments":[],"applicationFrame":false,"method":"[top]","class":null}],"filters":[]}
EOD;

				$logfilepath = sys_get_temp_dir() . '/get_last_log_time.log';

				file_put_contents( $logfilepath, $logfile_contents );

				return array( $logfilepath );
			}
		};

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'test-last-log-time' ),
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array( 'test-last-log-time', '2022-02-23T22:56:04+00:00', DAY_IN_SECONDS ),
				'times' => 1,
			)
		);

		$result = $sut->get_last_log_time();

		$expected = new \DateTime( '2022-02-23T22:56:04+00:00' );
		$this->assertEquals( $expected->getTimestamp(), $result->getTimestamp() );
	}

	/**
	 * @covers ::is_backtrace_contains_plugin
	 */
	public function test_is_backtrace_contains_plugin(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
			)
		);

		$cache_hash = 'hash1';

		\WP_Mock::userFunction(
			'wp_cache_get',
			array(
				'args'   => array( "test-plugin_{$cache_hash}", 'bh-wp-logger' ),
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_cache_get',
			array(
				'args'   => array( "backtrace_{$cache_hash}", 'bh-wp-logger' ),
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_cache_set',
			array(
				'args'  => array( "backtrace_{$cache_hash}", \WP_Mock\Functions::type( 'array' ), 'bh-wp-logger', 86400 ),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'times'  => 1,
				'return' => 'test-plugin/subfolder/guilty-file.php',
			)
		);

		\WP_Mock::userFunction(
			'wp_cache_set',
			array(
				'args'  => array( "test-plugin_{$cache_hash}", 'yes', 'bh-wp-logger', 86400 ),
				'times' => 1,
			)
		);

		\WP_Mock::passthruFunction( 'sanitize_key' );

		$sut = new API( $settings, $logger );

		$result = $sut->is_backtrace_contains_plugin( $cache_hash );

		$this->assertTrue( $result );
	}
}
