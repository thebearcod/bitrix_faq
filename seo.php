<?php
/*
 * Извращения по массовому редактирования SEO-свойств (вкладка)
 * ищет по CODE и по ID в элементах
 * ищет сразу разделы и элементы инфоблока
 * где то надо заменить, где то заменить одно слово
 * где то заменить уникод двойных ковычек на ковычки ёлочки
 * задача собрана из нескольких, целостности нет, код сохранен для примера
 * */
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
$GLOBALS['APPLICATION']->RestartBuffer();

// работа с таблицей SEO параметров
use Bitrix\Iblock\InheritedProperty;
\Bitrix\Main\Loader::includeModule("iblock");

function getSection($arFilter = [])
{
    $arSelect = array(
        "ID",
        "NAME",
        "IBLOCK_ID",

    );

    $res = \CIBlockSection::GetList(false, $arFilter, false, $arSelect, false);

    while ($arFields = $res->GetNext()):
        return $arFields['ID'];
    endwhile;

    return false;
}

function getElement($arFilter = [])
{
    //pr($arFilter);
    $arSelect = array(
        "ID",
        "NAME",
        "IBLOCK_ID",

    );

    $res = \CIBlockElement::GetList(false, $arFilter, false, false, $arSelect);

    while ($arFields = $res->GetNext()):
        return $arFields['ID'];
    endwhile;

    return false;
}

$file = 'list.csv';
$IBLOCK_ID = 17;

//$csv = array_map('str_getcsv', file($file)); / разделитель ;
$csv = array_map(
    function ($v) {
        return str_getcsv($v, "\t"); // разделитель табуляция
    },
    file($file)
);
$i = 0; ?>
<style>
    .other {
        background-color: red;
    }

    .section {
        background-color: #0a7ddd;
    }

    .element {
        background-color: green;
    }
</style>
<table style="border-collapse: collapse;">
    <tr>
        <th style="border: 1px solid black; background-color: gray;">#</th>
        <th style="border: 1px solid black; background-color: gray;">CODE</th>
        <th style="border: 1px solid black; background-color: gray;">ID</th>
        <th style="border: 1px solid black; background-color: gray;">Title</th>
        <th style="border: 1px solid black; background-color: gray;">H1</th>
        <th style="border: 1px solid black; background-color: gray;">Description</th>
        <th style="border: 1px solid black; background-color: gray;">Keywords</th>
    </tr>
    <?
    $counter = 0;
    foreach ($csv as $item):
        $counter++;
        $type = 'SECTION';
        $url = explode('/', str_replace('https://', '', trim($item[0], '/')));
        $arFilter = ["IBLOCK_ID" => $IBLOCK_ID, "CODE" => end($url),];
        $id = getSection($arFilter);
        if (!$id):
            $arFilter = ["IBLOCK_ID" => $IBLOCK_ID, "ID" => end($url),];
            $id = getElement($arFilter);
            $type = $id ? 'ELEMENT' : 'other';
        endif;



        $item[1] = mb_str_replace('лучшей','выгодной',$item[1]);
        $item[2] = mb_str_replace('лучшие','доступные',$item[2]);

        $arReplace = [
            ' &quot;' => ' «',
            '&quot; ' => '» ',
            //'&amp;quot;.' => '».',
            '&quot;.' => '».',
            '&quot;:' => '»:',
        ];

        foreach($arReplace as $key => $itemReplace){
            $item[1] = mb_str_replace($key,$itemReplace,$item[1]);
            $item[2] = mb_str_replace($key,$itemReplace,$item[2]);
        }


        ?>
        <tr class="<?= $type ?>">
            <td style="border: 1px solid black;"><?= $counter ?></td>
            <td style="border: 1px solid black;"><?= end($url) ?></td>
            <td style="border: 1px solid black;"><?= $id ?></td>
            <td style="border: 1px solid black;"><?= $item[1] ?></td>
            <td style="border: 1px solid black;"><?= $item[2] ?></td>
            <td style="border: 1px solid black;"><?= $item[3] ?></td>
            <td style="border: 1px solid black;"><?= $item[4] ?></td>
        </tr>
        <?
        if ($type == 'other') {
            continue;
        } // пропустим не найденные

        $arSEO = [];
        if (!empty($item[1]) && $item[1] !== '-') {
            $arSEO["{$type}_META_TITLE"] = $item[1];
        }
        if (!empty($item[2] && $item[2] !== '-')) {
            $arSEO["{$type}_META_DESCRIPTION"] = $item[2];
        }
        /*if (!empty($item[2] && $item[2] !== '-')) {
            $arSEO["{$type}_PAGE_TITLE"] = $item[2];
        }
        if (!empty($item[3] && $item[3] !== '-')) {
            $arSEO["{$type}_META_DESCRIPTION"] = $item[3];
        }
        if (!empty($item[4] && $item[4] !== '-')) {
            $arSEO["{$type}_META_KEYWORDS"] = $item[3];
        }*/

        if (!empty($arSEO)):

            // меняем шаблоны на вкладке SEO
            if ($type == 'SECTION') {
                $ipropTemplates = new InheritedProperty\SectionTemplates($IBLOCK_ID, $id);
            }

            if ($type == 'ELEMENT') {
                $ipropTemplates = new InheritedProperty\ElementTemplates($IBLOCK_ID, $id);
            }

            $ipropTemplates->set($arSEO);

            // чистим кеш SEO (очистка всего кеша не даёт результатов)
            if ($type == 'SECTION') {
                $ipropValues = new InheritedProperty\SectionValues($IBLOCK_ID, $id);
            }

            if ($type == 'ELEMENT') {
                $ipropValues = new InheritedProperty\ElementValues($IBLOCK_ID, $id);
            }

            $ipropValues->clearValues();


            /*
             * некоторые названия свойств
             * SECTION_META_TITLE
             * SECTION_PAGE_TITLE
             * SECTION_META_DESCRIPTION
             * SECTION_META_KEYWORDS
             *
             * ELEMENT_META_TITLE
             * ELEMENT_PAGE_TITLE
             * ELEMENT_META_DESCRIPTION
             * ELEMENT_META_KEYWORDS
             */


        endif;
        ?>


    <?
    endforeach; ?>
</table>


<?php
$arFilter = array(
    "IBLOCK_ID" => 17,
    "INCLUDE_SUBSECTIONS" => "Y",
    "ID" => 5963
);
$arSelect = array(
    "ID",
    "NAME"
);

$res = \CIBlockElement::GetList(false, $arFilter, false, false, $arSelect); ?>
<table style="border-collapse: collapse;">
    <tr>
        <th style="border: 1px solid black; background-color: gray;">#</th>
        <th style="border: 1px solid black; background-color: gray;">ID</th>
        <th style="border: 1px solid black; background-color: gray;">Name</th>
    </tr>
    <?
    $counter = 0;
    while ($arFields = $res->GetNext()):
        $counter++;
        ?>
        <tr>
            <td style="border: 1px solid black;"><?= $counter ?></td>
            <td style="border: 1px solid black;"><?= $arFields['ID'] ?></td>
            <td style="border: 1px solid black;"><?= $arFields['NAME'] ?></td>
        </tr>
    <?
    endwhile; ?>
</table>

