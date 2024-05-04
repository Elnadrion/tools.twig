<?php

namespace Elnadrion\Tools\Twig\Test;

use Elnadrion\Tools\Twig\TemplateEngine;
use Elnadrion\Tools\Twig\TwigCacheCleaner;
use Exception;
use PHPUnit_Framework_TestCase;

class RenderTest extends PHPUnit_Framework_TestCase
{
    public const TEST_VENDOR_NAME = '__phpunit_elnadrion';
    public const TEST_COMPONENT_NAME = 'tools.twig';

    public const EXPECTED = 'abcd';

    public static function setUpBeforeClass()
    {
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            if (!$_SERVER['DOCUMENT_ROOT'] = self::getDocumentRoot()) {
                throw new Exception("Can't find DOCUMENT_ROOT");
            }
        }

        // При подключении Битрикс очистит arCustomTemplateEngines, нужно будет заполнить его повторно
        include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
        elnadrionRegisterTwigTemplateEngine();

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

    public static function tearDownAfterClass()
    {
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            return;
        }

        $tmpDirs = [$_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/' . self::TEST_VENDOR_NAME];
        array_map('rmdir', array_merge($tmpDirs, glob($tmpDirs[0] . '/*')));
    }

    protected static function getDocumentRoot()
    {
        do {
            $documentRoot = strstr(__DIR__, 'bitrix', true);
            if ($documentRoot) {
                break;
            }

            $localRoot = strstr(__DIR__, 'local/vendor', true);
            if ($localRoot) {
                $documentRoot = $localRoot . '/../';
                break;
            }

            $dir = realpath(__DIR__ . '/../../../../');

            $innerCandidates = ['', 'htdocs', 'public_html'];

            do {
                $candidates = preg_replace('/^/', $dir . '/', $innerCandidates);

                foreach ($candidates as $candidateDir) {
                    if (is_dir($bitrixDir = $candidateDir . '/bitrix')) {
                        $documentRoot = $candidateDir;
                        break 3;
                    }
                }
            } while ($dir = realpath($dir . '/../'));
        } while (false);

        return rtrim($documentRoot, '/');
    }

    /**
     * Проверяет рендер компонентов
     * @dataProvider componentsDataProvider
     * @param $component
     * @param $template
     */
    public function testRenderComponent($component, $template, $additionalContext = [])
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent($component, $template, compact('additionalContext'));
        $output = ob_get_clean();

        $this->assertSame(self::EXPECTED, $output);
    }

    public function componentsDataProvider()
    {
        $data = [];
        $list = glob($this->getTestComponentTemplatesPath() . '/*');
        sort($list);

        foreach ($list as $template) {
            $data[] = [self::TEST_VENDOR_NAME . ':' . self::TEST_COMPONENT_NAME, basename($template, '.twig')];
        }

        return $data;
    }

    /**
     * Проверяет рендер отдельных файлов
     * @dataProvider standaloneTemplatesDataProvider
     * @param string $src
     */
    public function testRenderStandalone($src, $context = [])
    {
        $this->assertSame(self::EXPECTED, TemplateEngine::renderStandalone($src, $context));
    }

    public function standaloneTemplatesDataProvider()
    {
        $data = [];
        foreach (glob($this->getTestComponentTemplatesPath() . '/*') as $template) {
            // standalone не имеют контекста
            if (strpos($template, 'component') !== false || strpos($template, 'result') !== false) {
                continue;
            }
            $data[] = [is_dir($template) ? $template . '/template.twig' : $template];
        }
        return $data;
    }

    protected function getTestComponentTemplatesPath()
    {
        return __DIR__ . '/resources/' . self::TEST_COMPONENT_NAME . '/templates';
    }

    public function testRenderComponentWithExtractResult()
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
