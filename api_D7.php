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
         * SECTION_META_DESCRIPTION
         * SECTION_META_KEYWORDS
         *
         * ELEMENT_META_TITLE
         * ELEMENT_PAGE_TITLE
         * ELEMENT_META_DESCRIPTION
         * ELEMENT_META_KEYWORDS
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


<?php
// 5. Задача: удалить нулевые цены у товаров
//            нам не понадобится вывод header.php и footer.php,
//            но без пролога (prolog_before.php) ничего на выйдет
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $APPLICATION, $USER;
$GLOBALS['APPLICATION']->RestartBuffer();

if ($USER->isAdmin()):
    \Bitrix\Main\Loader::includeModule('iblock');

    $arFilter = array(
        "IBLOCK_ID" => 17,
        "INCLUDE_SUBSECTIONS" => "Y",
        "PRICE" => 0,

    );
    $arSelect = array(
        "ID",
        "NAME",
        "IBLOCK_ID",
        "PROPERTY_*",
        "PRICE_1",
    );

    $arNavStartParams = false;
    //$arNavStartParams = ["nTopCount" => 10];

    $res = \CIBlockElement::GetList(false, $arFilter, false, $arNavStartParams, $arSelect); ?>
    <table style="border-collapse: collapse;">
        <?
        $counter = 0;
        while ($arFields = $res->GetNext()):
            $counter++;

            /*$resOffers = CCatalogSKU::getOffersList(
                $arFields['ID'],	// массив ID товаров
                17,	// указываете ID инфоблока только в том случае, когда ВЕСЬ массив товаров из одного инфоблока и он известен
                $skuFilter = array(),	// дополнительный фильтр предложений. по умолчанию пуст.
                $fields = array(),  // массив полей предложений. даже если пуст - вернет ID и IBLOCK_ID
                $propertyFilter = array()
            );
            pr($resOffers);
            foreach($resOffers as $key => $arItem){
                $arFields["OFFERS"] = $arItem;
            }*/
            ?>
            <tr>
                <td style="border: 1px solid black;"><?= $counter ?></td>
                <td style="border: 1px solid black;"><?= $arFields['ID'] ?></td>
                <td style="border: 1px solid black;"><?= $arFields['NAME'] ?></td>
                <td style="border: 1px solid black;"><?= $arFields['PRICE_1'] ?></td>
                <? $statusDelete = CPrice::DeleteByProduct( $arFields['ID']);?>
                <td style="border: 1px solid black;"><?= $statusDelete ? 'удалена' : 'ошибка' ?></td>
            </tr>
        <? endwhile; ?>
    </table>

<?php endif; ?>


<?php
// 6. Задача: вывести список товаров:
//              - не снятых с производства
//              - добавить список данными из связанного HL-блока
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

$GLOBALS['APPLICATION']->RestartBuffer();

// получим массив скидок из HL Discount
$arDiscounts = [];
\Bitrix\Main\Loader::includeModule("highloadblock");

$idDiscount = 14; // Discount
$hlblock = HL\HighloadBlockTable::getById($idDiscount)->fetch();

$entity = HL\HighloadBlockTable::compileEntity($hlblock);
$entity_data_class = $entity->getDataClass();

$rsData = $entity_data_class::getList(
    array(
        "select" => array("*"),
        "order" => array("ID" => "ASC"),
        "filter" => array()
    )
);

while ($arData = $rsData->Fetch()) {
    $arDiscounts[$arData['UF_PRODUCT']] = $arData;
}

\Bitrix\Main\Loader::includeModule('iblock');

$arFilter = array(
    "IBLOCK_ID" => 91, // Основной каталог товаров
    "INCLUDE_SUBSECTIONS" => "Y",
    "!PROPERTY_DISCONTINUED" => "Y" // не снятые с производства
);
$arSelect = array(
    "ID",
    "NAME",
    "IBLOCK_ID",
    "PROPERTY_PRICE_DISCOUNT",
    "PROPERTY_RECOM_RETAIL_PRICE",
    "PROPERTY_PRICE_DISCOUNT_CLUB",
    "PROPERTY_ARTROZ",
    "PRICE_1",
);

$arNavStartParams = false;
//$arNavStartParams = ["nTopCount" => 10];

$res = \CIBlockElement::GetList(false, $arFilter, false, $arNavStartParams, $arSelect); ?>
<table style="border-collapse: collapse;">
    <tr>
        <th style="border: 1px solid black; background-color: gray;">#</th>
        <th style="border: 1px solid black; background-color: gray;">ID</th>
        <?/*<th style="border: 1px solid black; background-color: gray;">Name</th>*/?>
        <th style="border: 1px solid black; background-color: gray;">Артикул розницы</th>
        <th style="border: 1px solid black; background-color: gray;">Цена торгового каталога РРЦ</th>
        <th style="border: 1px solid black; background-color: gray;">Промо цена</th>
        <th style="border: 1px solid black; background-color: gray;">Клубная цена</th>
        <th style="border: 1px solid black; background-color: gray;">Размер скидки</th>
        <th style="border: 1px solid black; background-color: gray;">Размер скидки для зарегистрированных</th>
        <th style="border: 1px solid black; background-color: gray;">Максимальная скидка</th>
    </tr>
    <?
    $counter = 0;
    while ($arFields = $res->GetNext()):
        $counter++;
        ?>
        <tr>
            <td style="border: 1px solid black;"><?= $counter ?></td>
            <td style="border: 1px solid black;"><?= $arFields['ID'] ?></td>
            <?/*<td style="border: 1px solid black;"><?= $arFields['NAME'] ?></td>*/?>
            <td style="border: 1px solid black;"><?= $arFields['PROPERTY_ARTROZ_VALUE'] ?></td>
            <td style="border: 1px solid black;"><?= $arFields['PROPERTY_RECOM_RETAIL_PRICE_VALUE'] ?></td>
            <td style="border: 1px solid black;"><?= $arFields['PROPERTY_PRICE_DISCOUNT_VALUE'] ?></td>
            <td style="border: 1px solid black;"><?= $arFields['PROPERTY_PRICE_DISCOUNT_CLUB_VALUE'] ?></td>
            <td style="border: 1px solid black;"><?= $arDiscounts[$arFields['PROPERTY_ARTROZ_VALUE']]['UF_DISCOUNT'] ?></td>
            <td style="border: 1px solid black;"><?= $arDiscounts[$arFields['PROPERTY_ARTROZ_VALUE']]['UF_DISCOUNT_REGISTER'] ?></td>
            <td style="border: 1px solid black;"><?= $arDiscounts[$arFields['PROPERTY_ARTROZ_VALUE']]['UF_MAX_DISCOUNT'] ?></td>
        </tr>
    <?
    endwhile; ?>
</table>

