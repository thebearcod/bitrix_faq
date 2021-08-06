<?php
// Распространненые задачи по выборкам API или GetList наше всё

// 1. Задача: выбрать разделы/категории инфоблока с подсчетом количества элементов
$arSort = array();
$arFilter = array("IBLOCK_ID" => 18);
$arGroupBy = true;
$arSelect = Array("ID", "IBLOCK_ID", "CODE", "NAME", "PROPERTY_*");

$res = CIBlockSection::GetList($arSort, $arFilter, $arGroupBy,$arSelect);

while($arFields = $res->GetNext()) {
    print_r($arFields['ID'].' =>  '.$arFields['NAME'].' => '.$arFields['ELEMENT_CNT'].'<br>');
}

// 2. Задача: имеется список XLS с 1.ссылками/URL на раздел и 2.Значением title
//            нужно изменить у этих разделов title на вкладке SEO
//    Решение: сохраняем XLS в CSV закачиваем на сервер откуда будем запускать следующий скрипт php
//             скрипт бежит по файлу выберает название конечного раздела и меняет Title
//             !!! идеально работает только с уникальным свойством CODE !!!

use Bitrix\Iblock\InheritedProperty;
$file = 'list.csv';
$csv = array_map('str_getcsv', file($file));
$i=0;
foreach ($csv as $item) {
    // разберем URL на массив, очистив от лишнего
    $url = explode('/',str_replace('https://','',trim($item[0],'/')));
    $IBLOCK_ID = 18; // ID нужного каталога/инфоблока
    $arSort = array();
    // фильтр сортируе
    $arFilter = array("IBLOCK_ID" => $IBLOCK_ID,"CODE" => end($url) );
    $arGroupBy = false;
    $arSelect = Array("ID", "IBLOCK_ID", "CODE", "NAME", "PROPERTY_*");
    $res = CIBlockSection::GetList($arSort, $arFilter, $arGroupBy, $arSelect);
    while($arFields = $res->GetNext()) {
        $i++;
        pr($i.' => '.$arFields['ID'].' => '.end($url).' => '.$arFields["NAME"].' => '.$item[1]);
        $arSEO = [];
        if(!empty($item[1])) {
            $arSEO["ELEMENT_META_TITLE"] = $item[1];
        }
        if(!empty($item[2])) {
            $arSEO["ELEMENT_META_DESCRIPTION"] = $item[2];
        }
        if(!empty($item[3])) {
            $arSEO["ELEMENT_PAGE_TITLE"] = $item[3];
        }
        pr($arSEO);

        if (!empty($arSEO)) {
            //ООП  ElementTemplates или SectionTemplates или IblockTemplates ))
            $ipropTemplates = new InheritedProperty\ElementTemplates($IBLOCK_ID, $arFields['ID']);
            $ipropTemplates->set($arSEO);
        }
        /*
         * некоторые названия свойств
         * SECTION_META_TITLE
         * SECTION_PAGE_TITLE
         * ELEMENT_META_TITLE
         * ELEMENT_PAGE_TITLE
         * SECTION_META_DESCRIPTION
         * ELEMENT_META_DESCRIPTION
         */
    }
}


// 3. Задача: выбрать ошибочные торговые предложения с незаполненными свойствами-связками
//            и проставить любое свойство, например SIZE или деактивировать ТП
//            такое часто бывает при корявом парсинге товаров
$elements = [];
$props = [];
$IBLOCK_ID = 18; // ID каталога товаров (можно не указывать)

$arSort = array();
$arFilter = array("IBLOCK_ID" => $IBLOCK_ID);
$arGroupBy = false;
$arSelect = array("ID", "IBLOCK_ID", "CODE", "NAME", "DETAIL_PAGE_URL");
$res = CIBlockElement::GetList($arSort, $arFilter, $arGroupBy, false, $arSelect);
while ($arFields = $res->GetNext()) {
    $elements[] = $arFields;
}

$productID = array_column($elements, 'ID');

$arSKU = CCatalogSKU::getOffersList(
    $productID,
    $IBLOCK_ID,
    array(
        'ACTIVE' => 'Y',
        'PROPERTY_SIZES' => false,
        'PROPERTY_COLOR_REF' => false,
        'PROPERTY_VOLUME' => false,
        'PROPERTY_TOLSCHINA_METALLA' => false,
        'PROPERTY_TOLSCHINA_LISTA' => false,
        'PROPERTY_VYSOTA' => false,
    ),
    array('ID', 'NAME', 'CODE'),
    array("CODE" => array('SIZES', 'COLOR_REF', 'VOLUME', 'TOLSCHINA_METALLA', 'TOLSCHINA_LISTA', 'VYSOTA'))
);
$i = 1;
foreach ($elements as $key => $element):
    $arSKUforID = CCatalogSKU::getOffersList(
        $element['ID'],
        0,
        array('ACTIVE' => 'Y'),
        array('ID', 'NAME', 'CODE'),
        array()
    );

    if ($arSKU[$element['ID']] && (count($arSKUforID[$element['ID']]) == 1) ): // тут выбираем товары с одним ТП / ошибочные ?>
        <span><?= $i++ ?>.</span> <a target="_blank" href="<?= $element['DETAIL_PAGE_URL'] ?>"><?= $element['NAME'] ?> Кол-во: <?=count($arSKUforID[$element['ID']]) ?></a><br>
        <?
        foreach ($arSKU[$element['ID']] as $offer):
            // изменяет только одно свойство, не затрагивая остальные
            CIBlockElement::SetPropertyValueCode(
                $offer['ID'],
                "SIZES",
                ["VALUE" => 149] // ID из списка свойства со значением -
            );
            // или деактивируем торговое предложение
            /*$el = new CIBlockElement;
            $arLoadProductArray = Array("ACTIVE" => "N");
            $res = $el->Update($offer['ID'], $arLoadProductArray);*/
            pr($offer['CODE'].' => '.$offer['ID']);
        endforeach;
    endif;
endforeach;


// 4. Задача: простая выборка GetList с выводом DETAIL_PICTURE (пропускаем если не заполнено)
//            использовалась для поиска водяных знаков на изображениях
if ($USER->isAdmin()):

    \Bitrix\Main\Loader::includeModule('iblock');
    $arFilter = array(
        "IBLOCK_ID" => 2,
        "SECTION_ID" => Array(99),
        "INCLUDE_SUBSECTIONS" => "Y",
        "!DETAIL_PICTURE" => false
    );
    $arSelect = array(
        'NAME',
        "DETAIL_PAGE_URL",
        "DETAIL_PICTURE",
        "PREVIEW_PICTURE",
    );
    $pic = false; // меняем на true для просмотра изображений
    $res = CIBlockElement::GetList(false, $arFilter, false, false, $arSelect);?>
    <table>
        <?while($arFields = $res->GetNext()):?>

            <tr>
                <? if($pic): ?>
                    <td style="border: 1px solid black;"><img width="250" src="<?=CFile::GetPath($arFields['DETAIL_PICTURE'])?>" alt=""></td>
                <? endif; ?>
                <td style="border: 1px solid black;"><?=$arFields['NAME']?></td>
                <? if(!$pic): ?>
                    <td style="border: 1px solid black;">https://site.ru<?=$arFields['DETAIL_PAGE_URL']?></td>
                <? endif; ?>
            </tr>

            <?//print_r($arFields)?>
        <?endwhile;?>
    </table>
<?endif;?>