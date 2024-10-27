<?php

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\EventManager;
use Elnadrion\Tools\Twig\TemplateEngine;
use Elnadrion\Tools\Twig\TwigCacheCleaner;
use Elnadrion\Tools\Twig\TwigOptionsStorage;
use Twig\Error\LoaderError as TwigLoaderError;

if (!function_exists('elnadrionRenderTwigTemplate')) {
    function elnadrionRenderTwigTemplate(
        $templateFile,
        $arResult,
        $arParams,
        $arLangMessages,
        $templateFolder,
        $parentTemplateFolder,
        \CBitrixComponentTemplate $template
    ): void {
        TemplateEngine::render(
            $templateFile,
            $arResult,
            $arParams,
            $arLangMessages ?? [],
            $templateFolder,
            $parentTemplateFolder,
            $template
        );
    }

    function registerTwigTemplateEngine(): void
    {
        if (!class_exists('CMain')) {
            return;
        }

        $options = new TwigOptionsStorage();

        global $arCustomTemplateEngines;
        $arCustomTemplateEngines['twig'] = [
            'templateExt' => ['twig'],
            'function' => 'elnadrionRenderTwigTemplate',
            'sort' => $options->getUsedByDefault() ? 1 : 500,
        ];
    }

    registerTwigTemplateEngine();
} else {
    throw new TwigLoaderError('Необходимо, чтобы функция с именем registerTwigTemplateEngine не была определена');
}

if (class_exists(EventManager::class)) {
    EventManager::getInstance()->addEventHandler('main', 'onProlog', 'clearTwigCache');

    function clearTwigCache(): void
    {
        $request = Context::getCurrent()->getRequest();

        if (
            $request->getRequestedPage() !== '/bitrix/admin/cache.php' ||
            $request->get('clearcache') !== 'Y' ||
            !$request->isPost() ||
            !in_array($request->get('cachetype'), ['all', 'html']) ||
            !check_bitrix_sessid() ||
            !CurrentUser::get()->isAdmin()
        ) {
            return;
        }

        (new TwigCacheCleaner(TemplateEngine::getInstance()->getEngine()))->clearAll();
    }
}
