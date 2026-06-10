<?php

declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
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

    /** @var string[] */
    private array $modified_env_vars = [];

    /** @var array<string,string> */
    private array $original_env = [];

    #[Test]
    public function usesDefaultsWhenConfigFileIsEmpty(): void
    {
        $app = Mockery::mock(SiteApplication::class);

        $module = new SiteConfigModule($app);
        $module->addDefinitions([
            'account.persistent_login_time'    => 2419200,
            'account.persistent_login_enabled' => false,
            'site.meta_description'            => null,
            'site.title'                       => 'Default title',
        ]);
        $module->load($this->getTempIniFile(''));

        self::assertEquals(2419200, $module->account->persistent_login_time);
        self::assertFalse($module->account->persistent_login_enabled);
        self::assertEquals(null, $module->site->meta_description);
        self::assertEquals('Default title', $module->site->title);
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
            'site.title'                       => 'Default title',
        ]);
        $module->load($this->getTempIniFile(
            <<<'INI'
                [account]
                persistent_login_time = 123456
                persistent_login_enabled = On

                [site]
                meta_description = "Ini meta description"
                title = "Ini title"
                INI
        ));

        self::assertEquals(123456, $module->account->persistent_login_time);
        self::assertEquals('1', $module->account->persistent_login_enabled);
        self::assertEquals(
            'Ini meta description',
            $module->site->meta_description
        );
        self::assertEquals('Ini title', $module->site->title);
    }

    #[Test]
    public function overridesConfigUsingEnvVars(): void
    {
        $app = Mockery::mock(SiteApplication::class);

        $module = new SiteConfigModule($app);
        $module->addDefinitions([
            'site.title' => 'Default title',
        ]);
        $this->setEnvVar('SITE_TITLE', 'Environment title');
        $module->load($this->getTempIniFile(
            <<<'INI'
                [site]
                title = "Title"
                INI
        ));

        self::assertEquals('Environment title', $module->site->title);
    }

    #[Test]
    public function throwsErrorWhenOverridingArrayWithEnvVar(): void
    {
        $this->expectException(SiteException::class);
        $this->expectExceptionMessage(
            'Environment variables can only override scalar or null config '
            . 'values. Defined config for "site.emails" is array.'
        );

        $app = Mockery::mock(SiteApplication::class);

        $module = new SiteConfigModule($app);
        $module->addDefinitions([
            'site.emails' => ['test@example.com', 'foo@example.com'],
        ]);
        $this->setEnvVar('SITE_EMAILS', 'bar@example.com');
        $module->load($this->getTempIniFile(''));
    }

    #[After]
    public function cleanUpTempFiles(): void
    {
        foreach ($this->temp_ini_files as $filename) {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }

        $this->temp_ini_files = [];
    }

    #[Before]
    public function setOriginalEnv(): void
    {
        $this->original_env = getenv() ?: [];
    }

    #[After]
    public function cleanUpEnvVars(): void
    {
        foreach ($this->modified_env_vars as $name) {
            if (array_key_exists($name, $this->original_env)) {
                putenv("{$name}={$this->original_env[$name]}");
            } else {
                putenv($name);
            }
        }
    }

    private function setEnvVar(string $name, string $value): void
    {
        $this->modified_env_vars[] = $name;
        putenv("{$name}={$value}");
    }

    private function getTempIniFile(string $ini_contents): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'ini_test_');
        file_put_contents($filename, $ini_contents);

        $this->temp_ini_files[] = $filename;

        return $filename;
    }
}
