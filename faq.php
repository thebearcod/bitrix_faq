<? if ($USER->IsAuthorized()): ?>
    авторизованный пользователь...
<? else: ?>
    не авторизованный посетитель...
<? endif ?>

<? if (!$USER->IsAdmin()): ?>
    <span>Вы не админ!</span>
<? endif ?>

// лого без ссылки на главной
<? $isIndex = ($APPLICATION->GetCurPage(false) == SITE_DIR) ?>
<? if (!$isIndex): ?>
    <a href="/">
<? endif ?>
    <img src="/logo.png" alt=""/>
<? if (!$isIndex): ?>
    </a>
<? endif ?>


// Вывод свойства типа HTML5/текст
<? if ($arItem["DISPLAY_PROPERTIES"]['свойство']) { ?>
    <?= htmlspecialcharsBack($arItem["PROPERTIES"]["свойство"]["VALUE"]["TEXT"]) ?>
<? } ?>

// Вывод свойства типа строка
<? if ($arItem["DISPLAY_PROPERTIES"]['свойство']) { ?>
    <? print_r($arItem["DISPLAY_PROPERTIES"]["свойство"]["VALUE"]); ?>
<? } ?>

// Вывод свойства типа файл
<? echo $arItem["DISPLAY_PROPERTIES"]["ZNAK"]["FILE_VALUE"]["SRC"] ?>

// множественное свойство типа строка
<? foreach ($arResult["PROPERTIES"]["ZVANIYA"]["VALUE"] as $val): ?>
    <? print_r($val); ?> <br>
<? endforeach; ?>


<?// ресайз?>
<? $img = CFile::ResizeImageGet($arItem["PREVIEW_PICTURE"], array("width" => 280, "height" => 190), BX_RESIZE_IMAGE_EXACT, false); ?>
    <img src="<? echo $img["src"] ?>" alt="<? echo $arItem["NAME"]; ?>">

<?
// Дополнительные фото в новости детально:
// Шаг 1: В result_modifier.php проверяем на существование свойства MORE_PHOTO и добавляем его в массив $arResult["MORE_PHOTO"]
$arResult["MORE_PHOTO"] = array();
if (isset($arResult["PROPERTIES"]["MORE_PHOTO"]["VALUE"]) && is_array($arResult["PROPERTIES"]["MORE_PHOTO"]["VALUE"])) {
    foreach ($arResult["PROPERTIES"]["MORE_PHOTO"]["VALUE"] as $FILE) {
        $FILE = CFile::GetFileArray($FILE);
        if (is_array($FILE))
            $arResult["MORE_PHOTO"][] = $FILE;
    }
}
// Шаг 2: Выводим $arResult["MORE_PHOTO"] в template.php ?>
<? foreach ($arResult["MORE_PHOTO"] as $PHOTO): ?>
    <? $file = CFile::ResizeImageGet($PHOTO, array('width' => 1280, 'height' => 720), BX_RESIZE_IMAGE_PROPORTIONAL, true); ?>
    <div class="post_img">
        <a data-fancybox="gallery" href="<?= $PHOTO["SRC"] ?>">
            <img src="<?= $file["src"] ?>" data-src="<?= $file["src"] ?>" class="img-100 owl-lazy">
        </a>
    </div>
<? endforeach ?>

<?//<--  Дополнительные фото в новости детально ?>


<? if ($APPLICATION->GetCurPage(true) == SITE_DIR . 'index.php'): ?>
    <?php//только на главной?>
<? endif; ?>

<? if (CSite::InDir('/index.php')) { ?>
    <?//только на странице?>
<? } ?>

<?
//инклюд
$APPLICATION->IncludeFile(SITE_DIR . "include/services.php", array(), array("MODE" => "html", "NAME" => "заголовок"));

$APPLICATION->ShowTitle(); // - собственно вывод тайтла в основном шаблоне сайта

// - подключение для вывода в шаблоне сайта основных полей тега : мета-теги Content-Type, robots, keywords, description; стили CSS; скрипты
$APPLICATION->ShowHead();

$APPLICATION->ShowPanel(); // - выводит панель управления администратора

SITE_TEMPLATE_PATH; // - подставляет путь к шаблону

$APPLICATION->ShowTitle(false); // - заголовок (в h1 например использовать)?>

<?= $arResult["PICTURE"]["SRC"] ?> - фото раздела в каталоге

<?= $arResult["NAME"]; ?> - имя раздела в каталоге

<?= $arItem["NAME"] ?> - название
<?= $arItem["DETAIL_PAGE_URL"] ?> - ссылка на детальную новость (статью)
<?= $arItem["PREVIEW_TEXT"]; ?> - текст анонса
<?= $arResult["DETAIL_TEXT"]; ?> - детальный текст
<?= $arItem["PREVIEW_PICTURE"]["SRC"] ?> - изображение анонса
<?= $arItem["DETAIL_PICTURE"]["SRC"] ?> - изображение детальное
<?= $arResult['DISPLAY_ACTIVE_FROM'] ?> - дата начала активности
<?= $arItem['DATE_CREATE'] ?> - Дата создания элемента инфолока

    Кол-во просмотров с проверкой
<? if (isset($arResult["SHOW_COUNTER"])): ?>
    <? if ($arResult["SHOW_COUNTER"] == '') $arResult["SHOW_COUNTER"] = 0; ?>
    <?= $arResult["SHOW_COUNTER"] ?>
<? endif; ?>

    Если картинки нет- то вывести "нет картинки"
<? if (strlen($arItem["DETAIL_PICTURE"]["SRC"]) > 0): ?>
    <img src="<?= $arItem["DETAIL_PICTURE"]["SRC"] ?>"/>
<? else: ?>
    нет картинки
<? endif ?>

    подключение скриптов из папки шаблона
<? $this->addExternalJS($this->__folder . "/form_script.js"); ?>
<? $this->addExternalCss($this->__folder . "/form_script.css"); ?>


    //если превью текста нет, то детальный
<? if (strlen($arItem["PREVIEW_TEXT"]) > 0): ?>
    <? echo $arItem["PREVIEW_TEXT"]; ?>
<? else: ?>
    <?= TruncateText($arItem["DETAIL_TEXT"], 250); ?> //обрезка текста
<? endif ?>

    Простейший GetList
<?
\Bitrix\Main\Loader::includeModule('iblock');

$arFilter = array(
    "IBLOCK_ID" => $arParams['IBLOCK_ID'],
    //'PROPERTY_IMPORTANT' => Y
);

$arSelect = array(
    'ID',
    'NAME',
    'PROPERTY_396',
    'PREVIEW_TEXT',
    'PREVIEW_PICTURE',
    'DETAIL_PAGE_URL'
);

$res = CIBlockElement::GetList(false, $arFilter, false, false, /*Array( 'nTopCount' => 1)*/, $arSelect);

if ($arFields = $res->GetNext()) {
    $arResult['ITEMS'][$arFields['ID']] = $arFields;
}
?>

    Выводим свойство ТЕГИ с запятыми
<? if (count($arResult['PROPERTIES']['TAGS_NAME1']['VALUE']) > 1) { ?>
    <div class="tags">
        <span>Теги:</span>
        <?
        $i = 0;
        $count = count($arResult['PROPERTIES']['TAGS_NAME1']['VALUE']);
        foreach ($arResult["PROPERTIES"]["TAGS_NAME1"]["VALUE"] as $arTags):
            $i++;
            ?>
            <a class="tag" href="/search/?q=<?= $arTags ?>"><?= $arTags ?><? if ($i != $count) echo ','; ?></a>
        <? endforeach; ?>
    </div>
<? } ?>

    Буферизация ShowViewContent
<? $this->SetViewTarget('to_aside'); ?>
    Содержимое
<? $this->EndViewTarget(); ?> - записываем содержимое в "to_aside"

<? $APPLICATION->ShowViewContent('to_aside'); ?> -вывод содержимого "to_aside"


    Фильтр для вывода элементов у которых значение свойства равно определенному значению
<?
global $arFilter;

$arFilter = array(
    "IBLOCK_ID" => 9,
    "ACTIVE" => "Y",
    "PROPERTY_NTV_VALUE" => "Да",
);
// в компоненте "FILTER_NAME" => "arFilter",
?>