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
        pr($arFields['ID'].' => '.end($url).' => '.$arFields["NAME"].' => '.$item[1]);
        $ipropTemplates = new InheritedProperty\SectionTemplates($IBLOCK_ID, $arFields['ID']);
        //Установить шаблон SEO Title для раздела
        $ipropTemplates->set(array(
            "SECTION_META_TITLE" => $item[1],
        ));
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