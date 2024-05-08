<?php

namespace Elnadrion\Tools\Twig\Test;

use Elnadrion\Tools\Twig\TemplateEngine;
use Elnadrion\Tools\Twig\TwigCacheCleaner;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RenderTest extends TestCase
{
    public const TEST_VENDOR_NAME = '__phpunit_twig';
    public const TEST_COMPONENT_NAME = 'tools.twig';

    public const EXPECTED = 'abcd';

    public function setUp(): void
    {
        registerTwigTemplateEngine();
        (new TwigCacheCleaner(TemplateEngine::getInstance()->getEngine()))->clearAll();

        $componentsDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components';
        $tmpVendorDir = $componentsDir . '/' . self::TEST_VENDOR_NAME;
        if (!is_dir($tmpVendorDir) && !mkdir($tmpVendorDir)) {
            throw new Exception("Can't create tmp dir: `{$tmpVendorDir}`");
        }

        foreach (glob(__DIR__ . '/resources/*', GLOB_ONLYDIR) as $componentDir) {
            $symlink = $tmpVendorDir . '/' . basename($componentDir);
            if (!file_exists($symlink) && !symlink($componentDir, $symlink)) {
                throw new Exception("Can't create symlink: `{$symlink}` to `{$componentDir}`");
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            return;
        }

        $tmpDirs = [$_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/' . self::TEST_VENDOR_NAME];
        array_map('rmdir', array_merge($tmpDirs, glob($tmpDirs[0] . '/*')));
    }

    /**
     * Проверяет рендер компонентов
     */
    #[DataProvider('componentsDataProvider')]
    public function testRenderComponent($component, $template, $additionalContext = []): void
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent($component, $template, ['additionalContext' => $additionalContext]);
        $output = ob_get_clean();

        $this->assertSame(self::EXPECTED, $output);
    }

    /**
     * @return string[][]
     */
    public static function componentsDataProvider(): array
    {
        $data = [];
        $list = glob(static::getTestComponentTemplatesPath() . '/*');
        sort($list);

        foreach ($list as $template) {
            $data[] = [self::TEST_VENDOR_NAME . ':' . self::TEST_COMPONENT_NAME, basename($template, '.twig')];
        }

        return $data;
    }

    /**
     * Проверяет рендер отдельных файлов
     */
    #[DataProvider('standaloneTemplatesDataProvider')]
    public function testRenderStandalone(string $src, $context = []): void
    {
        $this->assertSame(self::EXPECTED, TemplateEngine::renderStandalone($src, $context));
    }

    /**
     * @return string[][]
     */
    public static function standaloneTemplatesDataProvider(): array
    {
        $data = [];
        foreach (glob(static::getTestComponentTemplatesPath() . '/*') as $template) {
            // standalone не имеют контекста
            if (str_contains($template, 'component') || str_contains($template, 'result')) {
                continue;
            }
            $data[] = [is_dir($template) ? $template . '/template.twig' : $template];
        }
        return $data;
    }

    protected static function getTestComponentTemplatesPath(): string
    {
        return __DIR__ . '/resources/' . self::TEST_COMPONENT_NAME . '/templates';
    }

    public function testRenderComponentWithExtractResult(): void
    {
        $engine = TemplateEngine::getInstance();
        $options = $engine->getOptions();
        $extractResult = $options->getExtractResult();
        $options->setExtractResult(true);

        $this->testRenderComponent(self::TEST_VENDOR_NAME . ':' . self::TEST_COMPONENT_NAME, 'print.result', [
            'extractResultRequired' => true,
        ]);

        $options->setExtractResult($extractResult);
    }
}
