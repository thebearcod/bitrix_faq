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

// 1. Задача: имеется список XLS с 1.ссылками/URL на раздел и 2.Значением title
//            нужно изменить у этих разделов title на вкладке SEO

