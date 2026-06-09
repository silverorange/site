<?php

declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SiteConfigModule::class)]
class SiteConfigModuleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var string[] */
    private array $temp_ini_files = [];

    #[Test]
    public function usesDefaultsWhenConfigFileIsEmpty(): void
    {
        $app = Mockery::mock(SiteApplication::class);

        $module = new SiteConfigModule($app);
        $module->addDefinitions([
            'account.persistent_login_time'    => 2419200,
            'account.persistent_login_enabled' => false,
            'site.meta_description'            => null,
            'site.title'                       => 'default title',
        ]);
        $module->load($this->getTempIniFile(''));

        self::assertEquals(2419200, $module->account->persistent_login_time);
        self::assertFalse($module->account->persistent_login_enabled);
        self::assertEquals(null, $module->site->meta_description);
        self::assertEquals('default title', $module->site->title);
    }

    #[Test]
    public function loadsConfigValuesFromIni(): void
    {
        $app = Mockery::mock(SiteApplication::class);

        $module = new SiteConfigModule($app);
        $module->addDefinitions([
            'account.persistent_login_time'    => 2419200,
            'account.persistent_login_enabled' => false,
            'site.meta_description'            => null,
            'site.title'                       => 'default title',
        ]);
        $module->load($this->getTempIniFile(
            <<<'INI'
                [account]
                persistent_login_time = 123456;
                persistent_login_enabled = On;

                [site]
                meta_description = "Meta description";
                title = "Title";
                INI
        ));

        self::assertEquals(123456, $module->account->persistent_login_time);
        self::assertEquals('1', $module->account->persistent_login_enabled);
        self::assertEquals('Meta description', $module->site->meta_description);
        self::assertEquals('Title', $module->site->title);
    }

    #[After]
    public function cleanUpTempFiles(): void
    {
        foreach ($this->temp_ini_files as $filename) {
            unlink($filename);
        }

        $this->temp_ini_files = [];
    }

    private function getTempIniFile(string $ini_contents): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'ini_test_');
        file_put_contents($filename, $ini_contents);

        $this->temp_ini_files[] = $filename;

        return $filename;
    }
}
