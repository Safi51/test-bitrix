<?php

use customModules\File;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
require ($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');

$APPLICATION->SetTitle('Главная');

if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
    die('Ошибка: требуется PhpSpreadsheet. Установите через composer: composer require phpoffice/phpspreadsheet');
}

$filePath = 'test_for_grid.xlsx';

//проверка присуствует ли файл
if (!file_exists($filePath)) {
    die('Файл test_for_grid.xlsx  не найден');
}
try {
    //чтение файла
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows  = $sheet->toArray();

    //убираем заголовок xlsx
    $headers = array_shift($rows);


    //даем ключ значение
    $data = [];
    foreach ($rows as $key => $row) {
        foreach ($headers as $i => $header) {
            $data[$key][$header] = $row[$i] ?? '';
        }
    }
    //получение уникальных статусов и тип звонков для сортировки
    $statuses  = array_unique(array_filter(array_column($data, 'Статус')));
    $callTypes = array_unique(array_filter(array_column($data, 'Тип звонка')));

    sort($statuses);
    sort($callTypes);

}catch (\Exception $e){
    die($e->getMessage());
}
?>

    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Грид звонков с фильтром</title>
        <style>
            .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
            .filter-panel { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
            .filter-panel label { font-weight: bold; }
            .filter-panel select, .filter-panel input { padding: 6px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f2f2f2; }
            tr:nth-child(even) { background: #f9f9f9; }
            .error { color: red; font-weight: bold; padding: 10px; background: #ffecec; border: 1px solid #fcc; margin: 15px 0; }
        </style>
    </head>
    <body>

    <div class="container">

        <div class="filter-panel" id="main_ui_filter">
            <form method="GET" id="filterForm">
                <label>Статус:
                    <select name="status">
                        <option value="">Все</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?=htmlspecialchars($s)?>" <?=($_GET['status'] ?? '') === $s
                                ? 'selected'
                                : ''
                            ?>>
                                <?=htmlspecialchars($s)?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Тип звонка:
                    <select name="call_type">
                        <option value="">Все</option>
                        <?php foreach ($callTypes as $t): ?>
                            <option value="<?=htmlspecialchars($t)?>" <?=($_GET['call_type']??'')===$t?'selected':''?>>
                                <?=htmlspecialchars($t)?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Длительность (мин, от):
                    <input type="number" name="duration_min" min="0" value="<?=htmlspecialchars($_GET['duration_min']??'')?>">
                </label>

                <button type="submit">Применить</button>
                <a href="?" style="margin-left:15px;">Сбросить</a>
            </form>
        </div>
        <div id="main_ui_grid">
            <?php
            if (!empty($loadError)) {
                echo '<div class="error">Ошибка загрузки XLSX: ' . htmlspecialchars($loadError) . '</div>';
            }

            $filtered = $data;
            if (!empty($_GET['status'])) {
                $filtered = array_filter($filtered, fn($r) => ($r['Статус'] ?? '') === $_GET['status']);
            }
            if (!empty($_GET['call_type'])) {
                $filtered = array_filter($filtered, fn($r) => ($r['Тип звонка'] ?? '') === $_GET['call_type']);
            }
            if (isset($_GET['duration_min']) && $_GET['duration_min'] !== '') {
                $min = (int)$_GET['duration_min'];
                $filtered = array_filter($filtered, fn($r) => (int)($r['Длительность звонка'] ?? 0) >= $min);
            }
            ?>

            <table>
                <thead>
                <tr>
                    <?php foreach ($headers as $h): ?>
                        <th><?=htmlspecialchars($h)?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($filtered as $row): ?>
                    <tr>
                        <?php foreach ($headers as $h): ?>
                            <td><?=htmlspecialchars($row[$h] ?? '')?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    </body>
    </html>

<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>