<?php

namespace Elnadrion\Tools\Twig;

use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\Loader\LoaderInterface as TwigLoaderInterface;
use Twig\Template;

/**
 * Class BitrixLoader. Класс загрузчик файлов шаблонов. Понимает специализированный синтаксис
 * @package Elnadrion\Twig
 */
class BitrixLoader extends TwigFilesystemLoader implements TwigLoaderInterface
{
    /** Статическое хранилище для уже отрезолвленных путей для ускорения */
    private static array $resolved = [];
    /** Статическое хранилище нормализованных имен шаблонов для ускорения */
    private static array $normalized = [];

    /**
     * {@inheritdoc}
     *
     * Принимает на вход имя компонента и шаблона в виде<br>
     * <b>vendor:componentname[:template[:specifictemplatefile]]</b><br>
     * Например bitrix:news.list:.default, или bitrix:sale.order:show:step1
     */
    public function getSource(string $name): string
    {
        return file_get_contents($this->getSourcePath($name));
    }

    protected function findTemplate(string $name, bool $throw = true): string
    {
        return $this->getSourcePath($name);
    }

    /** {@inheritdoc} */
    public function getCacheKey(string $name): string
    {
        return $this->normalizeName($name);
    }

    /**
     * {@inheritdoc}
     * Не использовать в продакшене!!
     * Метод используется только в режиме разработки или при использовании опции auto_reload = true
     * @param string $name Путь к шаблону
     * @param int    $time Время изменения закешированного шаблона
     * @return bool  Актуален ли закешированный шаблон
     */
    public function isFresh(string $name, int $time): bool
    {
        return filemtime($this->getSourcePath($name)) <= $time;
    }

    /**
     * Получает путь до файла с шаблоном по его имени
     *
     * @throws TwigLoaderError
     */
    public function getSourcePath(string $name): string
    {
        $name = $this->normalizeName($name);

        if (isset(static::$resolved[ $name ])) {
            return static::$resolved[ $name ];
        }

        $resolved = '';
        if (str_contains($name, ':')) {
            $resolved = $this->getComponentTemplatePath($name);
        } elseif (($firstChar = substr($name, 0, 1)) === DIRECTORY_SEPARATOR) {
            $resolved = is_file($name) ? $name : $_SERVER['DOCUMENT_ROOT'] . $name;
        }

        if (!file_exists($resolved)) {
            throw new TwigLoaderError("Не удалось найти шаблон '{$name}'");
        }

        return static::$resolved[ $name ] = $resolved;
    }

    protected function getLastRenderedTemplate(): false|string
    {
        $trace = debug_backtrace();
        foreach ($trace as $point) {
            if (isset($point['object']) && ($obj = $point['object']) instanceof Template) {
                /** @var Template $obj */
                return $obj->getSourceContext()->getPath();
            }
        }

        return false;
    }

    /**
     * По Битрикс-имени шаблона возвращает путь к его файлу
     */
    private function getComponentTemplatePath(string $name): string
    {
        $name = $this->normalizeName($name);

        [$siteTemplate, $namespace, $component, $template, $page] = explode(':', $name);

        // Относительный путь, например: vendor:component:template:inc/area.twig
        $isRelative = $page !== basename($page);

        $dotExt = '.twig';
        if ($isRelative) {
            if (pathinfo($page, PATHINFO_EXTENSION) !== 'twig') {
                $page .= $dotExt;
            }
        } else {
            $page = basename($page, $dotExt);
        }

        $componentName = "{$namespace}:{$component}";

        $component = new \CBitrixComponent();
        $component->setSiteTemplateId($siteTemplate);
        $component->InitComponent($componentName, $template);
        if (!$isRelative) {
            $component->__templatePage = $page;
        }

        $obTemplate = new \CBitrixComponentTemplate();
        $obTemplate->Init($component);

        return $_SERVER['DOCUMENT_ROOT'] . (
            $isRelative ? ($obTemplate->GetFolder() . DIRECTORY_SEPARATOR . $page) : $obTemplate->GetFile()
        );
    }

    /**
     * На основании шаблона компонента создает полное имя для Twig
     */
    public function makeComponentTemplateName(\CBitrixComponentTemplate $template): string
    {
        if ($template->__fileAlt) {
            return $template->__fileAlt;
        }

        $component = $template->getComponent();

        if (!empty($component->getParent())) {
            return $template->__file;
        }

        $siteTemplateName = $template->__siteTemplate;
        $templatePage = $template->__page;
        $templateName = $template->__name;
        $componentName = $component->getName();

        return "{$siteTemplateName}:{$componentName}:{$templateName}:{$templatePage}";
    }

    /**
     * Преобразует имя в максимально-полное начертание
     */
    public function normalizeName(string $name): string
    {
        if (str_contains($name, DIRECTORY_SEPARATOR)) {
            $name = preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name));
        }

        $isComponentPath = str_contains((string)$name, ':');
        $isGlobalPath = str_starts_with((string)$name, '/');

        if (($isComponentPath || $isGlobalPath) && isset(static::$normalized[ $name ])) {
            return static::$normalized[ $name ];
        }

        if ($isComponentPath) {
            [$siteTemplate, $namespace, $component, $template, $file] = explode(':', (string) $name);

            if (empty($template)) {
                $template = '.default';
            }

            if (empty($file)) {
                $file = 'template';
            }

            $normalizedName = "{$siteTemplate}:{$namespace}:{$component}:{$template}:{$file}";
        } elseif ($isGlobalPath) {
            $normalizedName = $name;
        } else {
            $lastRendered = $this->getLastRenderedTemplate();
            $normalizedName = $lastRendered ? dirname($lastRendered) . '/' . $name : $name;
        }

        return static::$normalized[$name] = $normalizedName;
    }
}
