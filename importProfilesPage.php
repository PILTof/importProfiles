<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use \catalogImport;
use \Bitrix\Main\Application;
use \Bitrix\Main\Data\Cache;



// подключим все необходимые файлы:
require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог
Loader::includeModule('controller'); // инициализация модуля
Loader::includeModule('iblock'); // инициализация модуля
// Подготовительная часть
$arIBLOCKS = [];
$rsBlock = CIBlock::GetList(
    $dctOrder = ['SORT' => 'ASC'],
    $dctFilter = [
        'ACTIVE' => 'Y'
    ],
    true
);
while ($dctBlock = $rsBlock->fetch()) {
    $arIBLOCKS[$dctBlock['ID']] = $dctBlock;
}


// Ajax обработчики
if ($_REQUEST['step']) {
    $FILE_PROPS = [];
    switch ($_REQUEST['step']) {
        case '1':
            $cache = Cache::createInstance(); // Служба кеширования
            $cachePath = 'catalog_import_cache'; // папка, в которой лежит кеш
            $cacheTtl = 2000; // срок годности кеша (в секундах)
            $cacheKey = 'catalog_import_cache'; // имя кеша
            if ($_REQUEST['ajax'] == "Y" && $_FILES['file-input']) {
                $cache->cleanDir($cachePath);
                $cache->initCache($cacheTtl, $cacheKey, $cachePath);
                if ($cache->startDataCache()) {
                    $content = file_get_contents($_FILES['file-input']['tmp_name']);
                    $ext = preg_replace("/.*\./", "", $_FILES['file-input']['full_path']);
                    $loadpath = $_SERVER['MODULE']['RODNIKI']['PATH'] . 'lib/controller/tmp/import/';
                    $filename = 'import.tmp_' . time() . '.' . $ext;
                    file_put_contents($loadpath . $filename, $content);
                    $import = new catalogImport($loadpath . $filename);
                    $FILE_PROPS = $import->props()?->get();
                    $cache->endDataCache($FILE_PROPS);
                }
            } else {
                if ($cache->initCache($cacheTtl, $cacheKey, $cachePath)) {
                    $FILE_PROPS = $cache->getVars();
                }
            }
            break;
        case '2':
            if ($_REQUEST['ajax'] == "Y" && $_REQUEST['iblock_id']) {
                ob_clean();
                ob_start();
                header('Content-Type: application/json;');
                $iblockPropDb = CIBlock::GetProperties($_REQUEST['iblock_id'], array(), []);
                $resprops = [];
                while ($res_arr = $iblockPropDb->GetNext()) {
                    $resprops[$res_arr['ID']] = $res_arr;
                }
                echo json_encode($resprops);

                die();

            }
            break;
        case '3':
            # code...
            break;

        default:
            # code...
            break;
    }
}

// Ajax обработчики end


require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
// Видимая часть
?>
<style>
    .table {
        width: 100%;
        margin-bottom: 20px;
        border: 1px solid #dddddd;
        border-collapse: collapse;
    }

    .table th {
        font-weight: bold;
        padding: 5px;
        background: #efefef;
        border: 1px solid #dddddd;
    }

    .table td {
        border: 1px solid #dddddd;
        padding: 5px;
    }

    #step-two-container .constructor {
        background-color: #eee;
        padding: 4px;
        margin: 2px;
        border-radius: 4px;
        cursor: pointer;
    }
</style>

<div style="display:flex;background-color: white;padding: 10px;border-radius: 3px; justify-content:center;flex-direction:column;align-items: center;"
    id="import-container">
    <form id="step-one-form" style="display: flex;flex-direction: column;row-gap: 20px;" action="">
        <input type="hidden" name="ajax" value="Y">
        <input type="hidden" name="step" value="1">
        <div style="display:flex;column-gap:10px;align-items: center;" class="ipnut-wrapper">
            <select onchange="return step_one.onselect(this);" name="file-ext" id="file-ext">
                <option selected value="xlsx">xlsx</option>
                <option value="json">json</option>
                <option value="url">url</option>
            </select>
            <label for="file-input">Файл .xls</label>
            <input name="file-input"
                accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                type="file">
        </div>
        <button type="submit">Загрузить файл</button>
    </form>
</div>
<!-- Второй шаг - вывод таблицы -->
<div id="step-two-container"
    style="margin-top:40px;display:flex;background-color: white;padding: 10px;border-radius: 3px; justify-content:center;flex-direction:column;align-items: center;">
    <? if (($_REQUEST['step'] == 1) && $FILE_PROPS): ?>
        <form action="" style="min-width:100%;display: flex;flex-direction: column;row-gap: 20px;" id="step-two-form">
            <div style="display:flex;justify-content:center;column-gap: 50px;align-items: center;">
                <label for="IB_ID">Инфоблок</label>
                <select onchange="return step_two.onIblockSelect(this)" name="IB_ID">
                    <option value="">...</option>
                    <? foreach ($arIBLOCKS as $id => $iblock): ?>
                        <option value="<?= $id ?>"><?= $iblock['NAME'] ?>
                        </option>
                    <? endforeach; ?>
                </select>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Название</th>
                        <th>Выбрать</th>
                        <th>Множественное (Выбрать контейнер)</th>
                        <th></th>
                    </tr>
                </thead>

                <body>
                    <? foreach ($FILE_PROPS as $key => $arValue): ?>
                        <? if (is_array($arValue)): ?>
                            <? $selectKeys = catalogImport::getMultipleKeys($arValue); ?>
                            <? $propContainerKeys = catalogImport::getMultiplePropertyContainer($arValue); ?>
                        <? endif; ?>
                        <tr <? if (is_array($arValue)): ?> style="background-color:#f7f7f7;border-top: 2px solid #c6c1c1;" <? endif; ?>>
                            <td><input type="checkbox" data-key="<?= $key ?>" name="use_<?= $key ?>"></td>
                            <td>
                                <?= $key ?>
                            </td>
                            <td>
                                <? if (is_array($arValue) && $selectKeys): ?>
                                    <span>
                                        <input type="hidden" name="construct_property_<?= reset($selectKeys) ?>">
                                        <span class="constructor"><?= reset($selectKeys) ?></span>
                                    </span>
                                <? endif; ?>
                            </td>
                            <td align="right">
                                <? if (is_array($arValue)): ?>
                                    <select name="set_multiple_container_<?= $key ?>">
                                        <? if (count(array_keys($arValue)) <= 1): ?>
                                            <option value="">Нет</option>
                                        <? endif; ?>

                                        <option value="<?= $key ?>"><?= $key ?></option>
                                        <? foreach ($propContainerKeys as $conainter): ?>
                                            <option value="<?= $conainter ?>"><?= $conainter ?></option>
                                        <? endforeach; ?>
                                    </select>
                                <? endif; ?>
                            </td>
                            <td align="right">
                                <select data-key="<?= $key ?>" name="set_property_<?= $key ?>">
                                    <option value="">...</option>
                                </select>
                            </td>
                        </tr>
                        <? if (is_array($arValue) && $selectKeys): ?>
                            <? foreach ($selectKeys as $selK => $selKey): ?>
                                <tr <? if ($selK == count($selectKeys) - 1): ?> style="border-bottom: 2px solid #c6c1c1" <? endif; ?>>
                                    <td><input type="checkbox" data-key="<?= $selectKeys ?>" name="use_<?= $selectKeys ?>"></td>
                                    <td></td>
                                    <td style="background-color:#f7f7f7;display:flex;justify-content:space-between;">
                                        <label for="include_<?= $selKey ?>_to_property_<?= $key ?>">
                                            <?= $selKey ?>
                                        </label>
                                        <input <? if ($selK == 0): ?>checked<? endif; ?> type="checkbox"
                                            name="include_<?= $selKey ?>_to_property_<?= $key ?>">
                                    </td>
                                    <td>
                                    </td>
                                    <td align="right">
                                        <select data-key="<?= $selKey ?>" name="set_property_<?= $selKey ?>">
                                            <option value="">...</option>
                                        </select>
                                    </td>
                                </tr>
                            <? endforeach; ?>
                        <? endif; ?>

                    <? endforeach; ?>
                </body>

            </table>
            <button type="submit">Сохранить профиль</button>
        </form>
    <? endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    function convertAjax(e) {
        let formData = new FormData(e.target);
        $.ajax({
            type: "POST",
            url: window.location.href,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                let resFormItems = $(response).find('#step-two-form').children();
                $('#step-two-container').append(resFormItems)
            },
            error: function (xhr, arg, arg2) {
                console.log(xhr)
            }
        });
    }
    let step_one = {
        init: function () {
            $('#step-one-form').submit(function (e) {
                e.preventDefault();
                convertAjax(e);
            });
        },
        onselect: function (dom) {
            let item = $(dom);
            let file_input = item.siblings('input[name="file-input"]');
            file_input.attr('type', 'file')
            switch (item.val()) {
                case 'xlsx':
                    file_input.attr('accept', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel')
                    break;
                case 'json':
                    file_input.attr('accept', 'application/json')
                    break;
                case 'url':
                    file_input.attr('type', 'text')
                    break;

                default:
                    break;
            }
        }
    }
    let step_two = {
        init: function () {
            $('#step-two-form').submit(function (e) {
                e.preventDefault();
                let formData = new FormData(e.target)
                let newFormData = new FormData();
                let sParams = new URLSearchParams(window.location.search);


                for (const pair of formData) {
                    if (pair[0].includes('use_')) {
                        let key = $('#step-two-form').find(`input[name="${pair[0]}"]`).data('key');
                        let selectedBitrixProperty = $('#step-two-form').find(`select[name="set_property_${key}"]`).val();
                        if (selectedBitrixProperty)
                            newFormData.append(key, "PROPERTY_" + selectedBitrixProperty);

                        let selectedContainer = $('#step-two-form').find(`select[name="set_multiple_container_${key}"]`).val();
                        if (selectedContainer)
                            newFormData.append(key + "_CONTAINER", selectedContainer);
                    }
                }
                for (const pair of newFormData) {
                    console.log(pair)
                }

            });

        },
        onIblockSelect: function (dom) {
            let uri = new URL(window.location.href);
            let sParams = new URLSearchParams(uri.search)
            sParams.append('ajax', 'Y');
            sParams.append('iblock_id', $(dom).val())
            sParams.append('step', '2');

            $.ajax({
                type: "POST",
                url: window.location.href + sParams.toString(),
                data: {},
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#step-two-container').find('select[name*="set_property_"]').html($('<option value="">...</option>'))
                    for (const id in response) {
                        if (Object.hasOwnProperty.call(response, id)) {
                            const prop = response[id];
                            $('#step-two-container').find('select[name*="set_property_"]').each((i, el) => {
                                $(el).append($(`<option value=${id}>${prop['NAME']}</option>`))
                            })

                        }
                    }
                    console.log(response)
                },
                error: function (jqXHR, test1, test2) {
                    console.log(jqXHR)
                }
            });
            return false;
        }
    }
    step_one.init();
    step_two.init();

</script>


<?
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
// конец