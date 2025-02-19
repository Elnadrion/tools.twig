<?php

namespace Elnadrion\Tools\Twig;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use CBitrixComponentTemplate;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;

/**
 * Class TemplateEngine. Небольшой синглтон, который позволяет в процессе работы страницы несколько раз обращаться к
 * одному и тому же рендереру страниц
 * @package Elnadrion\Twig
 */
class TemplateEngine
{
    private TwigEnvironment $engine;

    private readonly TwigOptionsStorage $options;

    /**
     * Возвращает настроенный инстанс движка Twig
     */
    public function getEngine(): TwigEnvironment
    {
        return $this->engine;
    }

    private static ?self $instance = null;

    public function __construct()
    {
        $this->options = new TwigOptionsStorage();

        $this->engine = new TwigEnvironment(
            new BitrixLoader($_SERVER['DOCUMENT_ROOT']),
            $this->options->asArray()
        );

        $this->initExtensions();
        $this->generateInitEvent();

        self::$instance = $this;
    }

    /**
     * Инициализируется расширения, необходимые для работы
     */
    private function initExtensions(): void
    {
        $this->engine->addExtension(new Extensions\BitrixExtension());
        $this->engine->addExtension(new Extensions\PhpGlobalsExtension());
        $this->engine->addExtension(new Extensions\PluralFormExtension());
        $this->engine->addExtension(new Extensions\DDExtension());
        $this->engine->addExtension(new Extensions\DumpExtension());
    }

    /**
     * Создается событие для внесения в Twig изменения из проекта
     */
    private function generateInitEvent(): void
    {
        $eventName = 'onAfterTwigTemplateEngineInited';
        $event = new Event('', $eventName, ['engine' => $this->engine]);
        $event->send();
        if ($event->getResults()) {
            foreach ($event->getResults() as $evenResult) {
                if ($evenResult->getType() == EventResult::SUCCESS) {
                    $twig = current($evenResult->getParameters());
                    if (!($twig instanceof TwigEnvironment)) {
                        throw new \LogicException(
                            "Событие '{$eventName}' должно возвращать экземпляр класса " .
                            "'\\TwigEnvironment' при успешной отработке"
                        );
                    }

                    $this->engine = $twig;
                }
            }
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ?: (self::$instance = new self());
    }

    /**
     * Собственно сама функция - рендерер. Принимает все данные о шаблоне и компоненте, выводит в stdout данные.
     * Содержит дополнительную обработку для component_epilog.php
     * @throws Twig\Error\Error
     */
    public static function render(
        /** @noinspection PhpUnusedParameterInspection */
        string $templateFile,
        array $arResult,
        array $arParams,
        array $arLangMessages,
        string $templateFolder,
        string $parentTemplateFolder,
        CBitrixComponentTemplate $template
    ): void {
        if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
            throw new TwigError('Пролог не подключен');
        }

        $component = $template->__component;
        /** @var BitrixLoader $loader */
        $loader = self::getInstance()->getEngine()->getLoader();
        if (!($loader instanceof BitrixLoader)) {
            throw new \LogicException(
                "Загрузчиком должен быть 'Elnadrion\\Tools\\Twig\\BitrixLoader' или его наследник"
            );
        }

        $templateName = $loader->makeComponentTemplateName($template);

        $engine = self::getInstance();

        $context = ['result' => $arResult];

        // Битрикс не умеет "лениво" грузить языковые сообщения если они запрашиваются из twig, т.к. ищет вызов
        // GetMessage, а после ищет рядом lang-папки. Т.к. рядом с кешем их конечно нет
        // Кроме того, Битрикс ждёт такое же имя файла, внутри lang-папки. Т.е. например template.twig
        // Но сам includ'ит их, что в случае twig файла конечно никак не сработает. Поэтому подменяем имя
        $templateMess = Loc::loadLanguageFile(
            $_SERVER['DOCUMENT_ROOT'] . preg_replace('/[.]twig$/', '.php', (string) $template->GetFile())
        );

        // Это не обязательно делать если не используется lang, т.к. Битрикс загруженные фразы все равно запомнил
        // и они будут доступны через вызов getMessage в шаблоне. После удаления lang, можно удалить и этот код
        if (is_array($templateMess)) {
            $arLangMessages = array_merge($arLangMessages, $templateMess);
        }

        $context = [
            'params' => $arParams,
            'lang' => $arLangMessages,
            'template' => $template,
            'component' => $component,
            'templateFolder' => $templateFolder,
            'parentTemplateFolder' => $parentTemplateFolder,
            'render' => ['templateName' => $templateName, 'engine' => $engine],
        ] + $context;

        echo self::getInstance()->getEngine()->render($templateName, $context);

        $component_epilog = $templateFolder . '/component_epilog.php';
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $component_epilog)) {
            /** @var \CBitrixComponent $component */
            $component->SetTemplateEpilog([
                'epilogFile' => $component_epilog,
                'templateName' => $template->__name,
                'templateFile' => $template->__file,
                'templateFolder' => $template->__folder,
                'templateData' => false,
            ]);
        }
    }

    /**
     * Рендерит произвольный twig-файл, возвращает результат в виде строки
     */
    public static function renderStandalone(string $src, array $context = []): string
    {
        return self::getInstance()->getEngine()->render($src, $context);
    }

    /**
     * Рендерит произвольный twig-файл, выводит результат в stdout
     */
    public static function displayStandalone(string $src, array $context = []): void
    {
        echo self::renderStandalone($src, $context);
    }

    public function getOptions(): TwigOptionsStorage
    {
        return $this->options;
    }
}
