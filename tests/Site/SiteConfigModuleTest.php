<?php

declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class SiteConfigModuleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function usesDefaultsWhenConfigFileIsEmpty(): void
    {
        $app = Mockery::mock(SiteApplication::class);

        $module = new SiteConfigModule($app);
        $module->addDefinitions([
            'account.persistent_login_time'    => 2419200,
            'account.persistent_login_enabled' => false,
            'account.restore_cookie_enabled'   => false,
            'site.meta_description'            => null,
            'site.title'                       => null,
            'site.resource_tag'                => null,
            'site.shortname'                   => null,
        ]);
        $module->load($config_filename);

        self::assertEquals('test', (string) $module);
    }

    #[Test]
    public function loadsConfigValuesFromIni(): void
    {
        $app = Mockery::mock(SiteApplication::class);

        $module = new SiteConfigModule($app);
        $module->addDefinitions([
            'account.persistent_login_time'    => 2419200,
            'account.persistent_login_enabled' => false,
            'account.restore_cookie_enabled'   => false,
            'site.meta_description'            => null,
            'site.title'                       => null,
            'site.resource_tag'                => null,
            'site.shortname'                   => null,
        ]);
        $module->load($config_filename);
    }
}
