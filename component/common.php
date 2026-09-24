<?php
require_once __DIR__ . '../../conn/db.php';
require_once __DIR__ . '../../conn/dbqry.php';
require_once __DIR__ . '../../conn/helper.php';

$proTypes = [
    '1' => 'With Variant',
    '2' => 'Without Variant'
];
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id  = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
    $tbl = isset($_GET['tbl']) ? trim($_GET['tbl']) : '';

    if ($id <= 0) {

        $response['message'] = 'Invalid record ID.';
        echo json_encode($response);
        exit;
    }

    if ($tbl === '') {

        $response['message'] = 'Invalid table name.';
        echo json_encode($response);
        exit;
    }

    if ($tbl === 'plan') {
        $checkSql = "SELECT id FROM company WHERE plan_id = {$id} AND status = 1 LIMIT 1";
        $checkResult = db_row($checkSql);

        if (!empty($checkResult)) {
            echo json_encode([
                'status' => false,
                'message' => 'This plan cannot be deleted because it is assigned to an active company.'
            ]);
            exit;
        }
    }

    $imageUploadFolders = [
        'category'         => 'category',
        'sub_category'     => 'subcategory',
        'product'          => 'product',
        'expense_category' => 'expense_category'
    ];

    if (isset($imageUploadFolders[$tbl])) {
        $folder = $imageUploadFolders[$tbl];
        $row = db_row("SELECT image FROM $tbl WHERE id = $id LIMIT 1");
        if (!empty($row['image'])) {
            $filePath = BASE_PATH . '/uploads/' . $folder . '/' . $row['image'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    $deleteSql = "DELETE FROM $tbl WHERE id = $id";
    $deleteResult = db_query($deleteSql);
    if ($deleteResult) {
        $response['status'] = true;
        $response['message'] = 'Record deleted successfully.';
    } else {
        $response['message'] = 'Failed to delete record.';
    }

    echo json_encode($response);
    exit;
}


if (isset($_POST['action']) && $_POST['action'] === 'status') {
    $id  = isset($_POST['id']) ? decrypt_id($_POST['id']) : 0;
    $tbl = isset($_POST['tbl']) ? trim($_POST['tbl']) : '';

    if ($id <= 0) {

        $response['message'] = 'Invalid record ID.';
        echo json_encode($response);
        exit;
    }

    if ($tbl === '') {

        $response['message'] = 'Invalid table name.';
        echo json_encode($response);
        exit;
    }

    if ($id <= 0) {
        $response['message'] = 'Invalid plan ID.';
        echo json_encode($response);
        exit;
    }

    $deleteSql = "UPDATE $tbl SET status = '" . $_POST['status'] . "' WHERE id = " . (int) $id;
    $deleteResult = db_query($deleteSql);
    if ($deleteResult) {
        $response['status'] = true;
        $response['message'] = 'Status update successfully.';
    } else {
        $response['message'] = 'Failed to delete record.';
    }

    echo json_encode($response);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'country_base_state') {
    $country_id  = isset($_GET['country_id']) ? $_GET['country_id'] : 0;
    $stateResult = db_query("SELECT id, name FROM state WHERE country_id = " . (int) $country_id . " AND status = 1 ORDER BY name ASC");
    $response['status'] = true;
    if ($stateResult && $stateResult->num_rows > 0) {
        while ($stateRow = $stateResult->fetch_assoc()) {
            $response['data'][] = ["id" => $stateRow['id'], "name" => $stateRow['name']];
        }
    } else {
        $response['message'] = 'State not found.';
    }
    echo json_encode($response);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'state_base_city') {
    $state_id   = isset($_GET['state_id']) ? $_GET['state_id'] : 0;
    $cityResult = db_query("SELECT id, name FROM city WHERE state_id = " . (int) $state_id . " AND status = 1 ORDER BY name ASC");
    $response['status'] = true;
    if ($cityResult && $cityResult->num_rows > 0) {
        while ($cityRow = $cityResult->fetch_assoc()) {
            $response['data'][] = ["id" => $cityRow['id'], "name" => $cityRow['name']];
        }
    } else {
        $response['message'] = 'City not found.';
    }
    echo json_encode($response);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $tbl = isset($_GET['tbl']) ? trim($_GET['tbl']) : '';
    $module = isset($_GET['module']) && trim($_GET['module']) !== '' ? trim($_GET['module']) : $tbl;
    $pageNm = isset($_GET['pageNm']) && trim($_GET['pageNm']) !== '' ? trim($_GET['pageNm']) : ucfirst(str_replace('_', ' ', $tbl));
    $fieldsParam = isset($_GET['fields']) ? trim($_GET['fields']) : 'name';

    if ($tbl === '') {
        die('Invalid table name.');
    }

    if (!hasPermission($module, 'views')) {
        die('Permission denied.');
    }

    $isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
    $sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

    // Check if table has company_id column
    $hasCompanyCol = false;
    $colCheck = db_rows("SHOW COLUMNS FROM `$tbl` LIKE 'company_id'");
    if (!empty($colCheck)) {
        $hasCompanyCol = true;
    }

    $baseConditions = [];
    if (!$isSuperadmin && $sessionCompanyId > 0 && $hasCompanyCol) {
        $baseConditions[] = "t.company_id = " . $sessionCompanyId;
    }

    $knownRelations = [
        'category_id'     => ['tbl' => 'category', 'col' => 'name', 'alias' => 'cat', 'field' => 'category_name'],
        'sub_category_id' => ['tbl' => 'sub_category', 'col' => 'name', 'alias' => 'subcat', 'field' => 'sub_category_name'],
        'country_id'      => ['tbl' => 'country', 'col' => 'name', 'alias' => 'cntry', 'field' => 'country_name'],
        'state_id'        => ['tbl' => 'state', 'col' => 'name', 'alias' => 'st', 'field' => 'state_name'],
        'city_id'         => ['tbl' => 'city', 'col' => 'name', 'alias' => 'ct', 'field' => 'city_name'],
        'role_id'         => ['tbl' => 'roles', 'col' => 'name', 'alias' => 'rl', 'field' => 'role_name'],
        'plan_id'         => ['tbl' => 'plan', 'col' => 'name', 'alias' => 'pln', 'field' => 'plan_name'],
        'department_id'   => ['tbl' => 'department', 'col' => 'name', 'alias' => 'dept', 'field' => 'department_name'],
        'designation_id'  => ['tbl' => 'designation', 'col' => 'name', 'alias' => 'desg', 'field' => 'designation_name'],
        'tax_id'          => ['tbl' => 'tax', 'col' => 'name', 'alias' => 'tx', 'field' => 'tax_name'],
        'sales_unit_id'        => ['tbl' => 'unit', 'col' => 'name', 'alias' => 'su', 'field' => 'sales_unit_name'],
        'customer_unit_id'     => ['tbl' => 'unit', 'col' => 'name', 'alias' => 'cusu', 'field' => 'customer_unit_name'],
        'complain_category_id' => ['tbl' => 'complain_category', 'col' => 'name', 'alias' => 'compcat', 'field' => 'complain_category_name'],
        'transport_by_id'      => ['tbl' => 'transport_by', 'col' => 'name', 'alias' => 'transby', 'field' => 'transport_by_name'],
    ];

    $fieldsList = array_filter(array_map('trim', explode(',', $fieldsParam)));
    $parsedFields = [];
    $extraJoins = [];
    $extraSelects = [];

    foreach ($fieldsList as $f) {
        if (str_contains($f, ':')) {
            [$colName, $colLabel] = explode(':', $f, 2);
            $colName = trim($colName);
            $colLabel = trim($colLabel);
        } else {
            $colName = trim($f);
            $colLabel = ucwords(str_replace('_', ' ', $colName));
        }

        // If field has dot like category.name
        if (str_contains($colName, '.')) {
            [$relTbl, $relCol] = explode('.', $colName, 2);
            $alias = 'rel_' . $relTbl;
            $fieldAlias = $relTbl . '_' . $relCol;
            $extraJoins[$alias] = "LEFT JOIN `$relTbl` $alias ON $alias.id = t.`{$relTbl}_id`";
            $extraSelects[] = "$alias.`$relCol` AS `$fieldAlias`";
            $parsedFields[$fieldAlias] = [
                'label' => $colLabel,
                'raw_col' => "$alias.`$relCol`",
                'type' => 'text'
            ];
        } elseif (isset($knownRelations[$colName])) {
            $rel = $knownRelations[$colName];
            $alias = $rel['alias'];
            $relCol = $rel['col'];
            $fieldAlias = $rel['field'];
            $extraJoins[$alias] = "LEFT JOIN `{$rel['tbl']}` $alias ON $alias.id = t.`$colName`";
            $extraSelects[] = "$alias.`$relCol` AS `$fieldAlias`";
            $parsedFields[$fieldAlias] = [
                'label' => $colLabel,
                'raw_col' => "$alias.`$relCol`",
                'type' => 'text'
            ];
        } else {
            $parsedFields[$colName] = [
                'label' => $colLabel,
                'raw_col' => "t.`$colName`",
                'type' => ($colName === 'image' || str_ends_with($colName, '_image')) ? 'image' : 'text'
            ];
        }
    }

    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $searchEsc = db_escape($search);
        $searchConditions = [];
        if ($isSuperadmin && $hasCompanyCol) {
            $searchConditions[] = "c.name LIKE '%$searchEsc%'";
        }
        foreach ($parsedFields as $info) {
            $searchConditions[] = "{$info['raw_col']} LIKE '%$searchEsc%'";
        }
        if (!empty($searchConditions)) {
            $baseConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        }
    }

    $where = !empty($baseConditions) ? "WHERE " . implode(" AND ", $baseConditions) : "";

    $joinsSql = "";
    $selectSql = "";
    if ($hasCompanyCol) {
        $selectSql .= ", c.name AS company_name";
        $joinsSql .= " LEFT JOIN company c ON c.id = t.company_id";
    }
    if (!empty($extraJoins)) {
        $joinsSql .= " " . implode(" ", $extraJoins);
    }
    if (!empty($extraSelects)) {
        $selectSql .= ", " . implode(", ", $extraSelects);
    }

    $sql = "SELECT t.* $selectSql FROM `$tbl` t $joinsSql $where ORDER BY t.id DESC";
    $rows = db_rows($sql);

    $filename = $pageNm . '_List_' . date('Y-m-d_H-i-s') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Image folders map per table
    $imageUploadFolders = [
        'category'     => 'category',
        'sub_category' => 'subcategory',
        'product'      => 'product'
    ];
    // Check if any field is an image field
    $hasImageField = false;
    foreach ($parsedFields as $info) {
        if (($info['type'] ?? '') === 'image') {
            $hasImageField = true;
            break;
        }
    }
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <!--[if gte mso 9]>
        <xml>
            <x:ExcelWorkbook>
                <x:ExcelWorksheets>
                    <x:ExcelWorksheet>
                        <x:Name><?= htmlspecialchars($pageNm) ?></x:Name>
                        <x:WorksheetOptions>
                            <x:DisplayGridlines/>
                        </x:WorksheetOptions>
                    </x:ExcelWorksheet>
                </x:ExcelWorksheets>
            </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
            }
            th, td {
                border: 0.5pt solid #000000;
                padding: 6px 10px;
                vertical-align: middle;
                font-family: Arial, sans-serif;
                font-size: 11pt;
            }
            th {
                background-color: #d9edf7;
                font-weight: bold;
                text-align: left;
            }
            .text-center {
                text-align: center;
            }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">Sr No.</th>
                    <?php if ($isSuperadmin && $hasCompanyCol): ?>
                        <th style="width: 180px;">Company Name</th>
                    <?php endif; ?>
                    <?php foreach ($parsedFields as $colKey => $info): ?>
                        <th <?= ($info['type'] === 'image' || $colKey === 'status') ? 'style="text-align:center;"' : '' ?>>
                            <?= htmlspecialchars($info['label']) ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php $sr = 1; foreach ($rows as $r): ?>
                        <?php 
                            $rowHeightStyle = "";
                            if ($hasImageField) {
                                // Check if this row has an image
                                $rowHasImg = false;
                                foreach ($parsedFields as $cKey => $inf) {
                                    if (($inf['type'] ?? '') === 'image' && !empty($r[$cKey])) {
                                        $rowHasImg = true;
                                        break;
                                    }
                                }
                                if ($rowHasImg) {
                                    $rowHeightStyle = ' style="height: 50px;"';
                                }
                            }
                        ?>
                        <tr<?= $rowHeightStyle ?>>
                            <td class="text-center" style="text-align: center;"><?= $sr++ ?></td>
                            <?php if ($isSuperadmin && $hasCompanyCol): ?>
                                <td><?= htmlspecialchars($r['company_name'] ?? '-') ?></td>
                            <?php endif; ?>
                            <?php foreach ($parsedFields as $colKey => $info): ?>
                                <?php 
                                    $cellVal = $r[$colKey] ?? '';
                                    if ($colKey === 'type' && $tbl == 'product') {
                                        $cellVal = $proTypes[$cellVal] ?? $cellVal;
                                    } elseif ($colKey === 'status') {
                                        $cellVal = ($cellVal == 1) ? 'Active' : 'Inactive';
                                    }
                                ?>
                                <?php if ($info['type'] === 'image'): ?>
                                    <td class="text-center" style="text-align: center; vertical-align: middle;">
                                        <?php 
                                            $imgFolder = $imageUploadFolders[$tbl] ?? $tbl;
                                            $fullPath = BASE_PATH . '/uploads/' . $imgFolder . '/' . $cellVal;
                                            if (!empty($cellVal) && file_exists($fullPath)): 
                                                $imgUrl = SITE_URL . 'uploads/' . $imgFolder . '/' . $cellVal;
                                        ?>
                                            <a href="<?= $imgUrl ?>" target="_blank" style="text-decoration:none;">
                                                <img src="<?= $imgUrl ?>" width="40" height="40" border="0" style="display:inline-block; border:1px solid #ccc; width:40px; height:40px;" />
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($colKey === 'status'): ?>
                                    <td class="text-center" style="text-align: center;"><?= htmlspecialchars($cellVal) ?></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($cellVal) ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= 1 + count($parsedFields) + (($isSuperadmin && $hasCompanyCol) ? 1 : 0) ?>" style="text-align: center;">No records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// Common Print Data (HTML layout)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'print_data') {
    $tbl = isset($_GET['tbl']) ? trim($_GET['tbl']) : '';
    $module = isset($_GET['module']) && trim($_GET['module']) !== '' ? trim($_GET['module']) : $tbl;
    $pageNm = isset($_GET['pageNm']) && trim($_GET['pageNm']) !== '' ? trim($_GET['pageNm']) : ucfirst(str_replace('_', ' ', $tbl));
    $fieldsParam = isset($_GET['fields']) ? trim($_GET['fields']) : 'name';

    if ($tbl === '') {
        die('Invalid table name.');
    }

    if (!hasPermission($module, 'views')) {
        die('Permission denied.');
    }

    $isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
    $sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

    // Check if table has company_id column
    $hasCompanyCol = false;
    $colCheck = db_rows("SHOW COLUMNS FROM `$tbl` LIKE 'company_id'");
    if (!empty($colCheck)) {
        $hasCompanyCol = true;
    }

    $baseConditions = [];
    if (!$isSuperadmin && $sessionCompanyId > 0 && $hasCompanyCol) {
        $baseConditions[] = "t.company_id = " . $sessionCompanyId;
    }

    // Known relation mappings: col => [table, display_col, join_alias, label_alias]
    $knownRelations = [
        'category_id'     => ['tbl' => 'category', 'col' => 'name', 'alias' => 'cat', 'field' => 'category_name'],
        'sub_category_id' => ['tbl' => 'sub_category', 'col' => 'name', 'alias' => 'subcat', 'field' => 'sub_category_name'],
        'country_id'      => ['tbl' => 'country', 'col' => 'name', 'alias' => 'cntry', 'field' => 'country_name'],
        'state_id'        => ['tbl' => 'state', 'col' => 'name', 'alias' => 'st', 'field' => 'state_name'],
        'city_id'         => ['tbl' => 'city', 'col' => 'name', 'alias' => 'ct', 'field' => 'city_name'],
        'role_id'         => ['tbl' => 'roles', 'col' => 'name', 'alias' => 'rl', 'field' => 'role_name'],
        'plan_id'         => ['tbl' => 'plan', 'col' => 'name', 'alias' => 'pln', 'field' => 'plan_name'],
        'department_id'   => ['tbl' => 'department', 'col' => 'name', 'alias' => 'dept', 'field' => 'department_name'],
        'designation_id'  => ['tbl' => 'designation', 'col' => 'name', 'alias' => 'desg', 'field' => 'designation_name'],
        'tax_id'          => ['tbl' => 'tax', 'col' => 'name', 'alias' => 'tx', 'field' => 'tax_name'],
        'sales_unit_id'   => ['tbl' => 'unit', 'col' => 'name', 'alias' => 'su', 'field' => 'sales_unit_name'],
        'customer_unit_id'=> ['tbl' => 'unit', 'col' => 'name', 'alias' => 'cusu', 'field' => 'customer_unit_name'],
        'complain_category_id'=> ['tbl' => 'complain_category', 'col' => 'name', 'alias' => 'compcat', 'field' => 'complain_category_name'],
        'transport_by_id'     => ['tbl' => 'transport_by', 'col' => 'name', 'alias' => 'transby', 'field' => 'transport_by_name'],
    ];

    // Image folders map per table
    $imageUploadFolders = [
        'category'     => 'category',
        'sub_category' => 'subcategory',
        'product'      => 'product'
    ];

    // Parse requested fields: e.g. "name" or "name:Name"
    $fieldsList = array_filter(array_map('trim', explode(',', $fieldsParam)));
    $parsedFields = [];
    $extraJoins = [];
    $extraSelects = [];

    foreach ($fieldsList as $f) {
        if (str_contains($f, ':')) {
            [$colName, $colLabel] = explode(':', $f, 2);
            $colName = trim($colName);
            $colLabel = trim($colLabel);
        } else {
            $colName = trim($f);
            $colLabel = ucwords(str_replace('_', ' ', $colName));
        }

        if (str_contains($colName, '.')) {
            [$relTbl, $relCol] = explode('.', $colName, 2);
            $alias = 'rel_' . $relTbl;
            $fieldAlias = $relTbl . '_' . $relCol;
            $extraJoins[$alias] = "LEFT JOIN `$relTbl` $alias ON $alias.id = t.`{$relTbl}_id`";
            $extraSelects[] = "$alias.`$relCol` AS `$fieldAlias`";
            $parsedFields[$fieldAlias] = [
                'label' => $colLabel,
                'raw_col' => "$alias.`$relCol`",
                'type' => 'text'
            ];
        } elseif (isset($knownRelations[$colName])) {
            $rel = $knownRelations[$colName];
            $alias = $rel['alias'];
            $relCol = $rel['col'];
            $fieldAlias = $rel['field'];
            $extraJoins[$alias] = "LEFT JOIN `{$rel['tbl']}` $alias ON $alias.id = t.`$colName`";
            $extraSelects[] = "$alias.`$relCol` AS `$fieldAlias`";
            $parsedFields[$fieldAlias] = [
                'label' => $colLabel,
                'raw_col' => "$alias.`$relCol`",
                'type' => 'text'
            ];
        } else {
            $parsedFields[$colName] = [
                'label' => $colLabel,
                'raw_col' => "t.`$colName`",
                'type' => ($colName === 'image' || str_ends_with($colName, '_image')) ? 'image' : 'text'
            ];
        }
    }

    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $searchEsc = db_escape($search);
        $searchConditions = [];
        if ($isSuperadmin && $hasCompanyCol) {
            $searchConditions[] = "c.name LIKE '%$searchEsc%'";
        }
        foreach ($parsedFields as $info) {
            if ($info['type'] !== 'image') {
                $searchConditions[] = "{$info['raw_col']} LIKE '%$searchEsc%'";
            }
        }
        if (!empty($searchConditions)) {
            $baseConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        }
    }

    $where = !empty($baseConditions) ? "WHERE " . implode(" AND ", $baseConditions) : "";

    $joinsSql = "";
    $selectSql = "";
    if ($hasCompanyCol) {
        $selectSql .= ", c.name AS company_name";
        $joinsSql .= " LEFT JOIN company c ON c.id = t.company_id";
    }
    if (!empty($extraJoins)) {
        $joinsSql .= " " . implode(" ", $extraJoins);
    }
    if (!empty($extraSelects)) {
        $selectSql .= ", " . implode(", ", $extraSelects);
    }

    $sql = "SELECT t.* $selectSql FROM `$tbl` t $joinsSql $where ORDER BY t.id DESC";
    $rows = db_rows($sql);

    $printedBy = strtoupper($_SESSION['username'] ?? 'ADMIN');
    $printDate = date('d-m-Y h:i A');
    $masterTitle = strtoupper($pageNm) . " MASTER " . $printDate . " PRINTED BY : " . htmlspecialchars($printedBy);
    $totalCols = 1 + count($parsedFields) + (($isSuperadmin && $hasCompanyCol) ? 1 : 0);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $masterTitle ?></title>
        <style>
            @page {
                size: portrait;
                margin: 8mm;
            }
            * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body {
                font-family: Arial, Helvetica, sans-serif;
                margin: 0;
                padding: 10px;
                color: #000;
                background: #fff;
            }
            .print-table {
                width: 100%;
                border-collapse: collapse;
                border: 2px solid #000;
            }
            .print-table th, 
            .print-table td {
                border: 1px solid #000;
                padding: 4px 8px;
                font-size: 11px;
                color: #000;
            }
            .title-header {
                text-align: center;
                font-weight: bold;
                font-size: 12px;
                padding: 6px 4px;
                border-bottom: 2px solid #000 !important;
                letter-spacing: 0.3px;
            }
            .col-header {
                font-weight: bold;
                font-size: 11px;
                background-color: transparent;
            }
            .text-center {
                text-align: center;
            }
            .text-left {
                text-align: left;
            }
            .no-print {
                margin-bottom: 12px;
                text-align: right;
            }
            .btn {
                display: inline-block;
                padding: 5px 14px;
                font-size: 12px;
                cursor: pointer;
                border-radius: 3px;
                text-decoration: none;
                border: 1px solid #6c757d;
                background: #6c757d;
                color: #fff;
            }
            .btn-primary {
                background: #0d6efd;
                border-color: #0d6efd;
            }
            @media print {
                .no-print {
                    display: none !important;
                }
                body {
                    padding: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="no-print">
            <button class="btn btn-primary" onclick="window.print()">Print</button>
            <button class="btn" onclick="window.close()">Close</button>
        </div>

        <table class="print-table">
            <thead>
                <tr>
                    <th colspan="<?= $totalCols ?>" class="title-header">
                        <?= $masterTitle ?>
                    </th>
                </tr>
                <tr>
                    <th class="col-header text-center" style="width: 55px;">Sr No.</th>
                    <?php if ($isSuperadmin && $hasCompanyCol): ?>
                        <th class="col-header text-left" style="width: 180px;">Company Name</th>
                    <?php endif; ?>
                    <?php foreach ($parsedFields as $colKey => $info): ?>
                        <th class="col-header <?= ($info['type'] === 'image' || $colKey === 'status') ? 'text-center' : 'text-left' ?>" <?= ($info['type'] === 'image') ? 'style="width: 60px;"' : '' ?>><?= htmlspecialchars($info['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php $sr = 1; foreach ($rows as $r): ?>
                        <tr>
                            <td class="text-center"><?= $sr++ ?></td>
                            <?php if ($isSuperadmin && $hasCompanyCol): ?>
                                <td class="text-left"><?= htmlspecialchars($r['company_name'] ?? '-') ?></td>
                            <?php endif; ?>
                            <?php foreach ($parsedFields as $colKey => $info): ?>
                                <?php
                                    $cellVal = $r[$colKey] ?? '';
                                    if ($colKey === 'type' && $tbl == 'product') {
                                        $cellVal = $proTypes[$cellVal] ?? $cellVal;
                                    }else if ($colKey === 'status') {
                                        $cellVal = ($cellVal == 1) ? 'Active' : 'Inactive';
                                    }
                                ?>
                                <?php if ($info['type'] === 'image'): ?>
                                    <td class="text-center">
                                        <?php 
                                            $imgFolder = $imageUploadFolders[$tbl] ?? $tbl;
                                            if (!empty($cellVal) && file_exists(BASE_PATH . '/uploads/' . $imgFolder . '/' . $cellVal)): 
                                        ?>
                                            <img src="<?= SITE_URL ?>uploads/<?= $imgFolder ?>/<?= htmlspecialchars($cellVal) ?>" alt="Img" style="width: 30px; height: 30px; object-fit: cover; border-radius: 3px; border: 1px solid #ccc;">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($colKey === 'status'): ?>
                                    <td class="text-center"><?= htmlspecialchars($cellVal) ?></td>
                                <?php else: ?>
                                    <td class="text-left"><?= htmlspecialchars($cellVal) ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $totalCols ?>" class="text-center" style="padding: 12px;">No records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 300);
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}


