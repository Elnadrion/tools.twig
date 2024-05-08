<?php

$_SERVER['DOCUMENT_ROOT'] = getDocumentRoot();

const NO_KEEP_STATISTIC = true;
const NOT_CHECK_PERMISSIONS = true;
const LOG_FILENAME = 'php://stderr';

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

function getDocumentRoot(): string
{
    $currentDir = realpath(__DIR__);
    $documentRoot = null;

    while (!file_exists($currentDir . '/bitrix')) {
        if ($currentDir === dirname($currentDir)) {
            break;
        }

        $currentDir = dirname($currentDir);
    }

    if (file_exists($currentDir . '/bitrix')) {
        $documentRoot = $currentDir;
    }

    return $documentRoot;
}
