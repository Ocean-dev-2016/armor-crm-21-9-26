<?php
require_once __DIR__ . '../../conn/db.php';
require_once __DIR__ . '../../conn/dbqry.php';
require_once __DIR__ . '../../conn/helper.php';

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

    if ($id <= 0) {
        $response['message'] = 'Invalid plan ID.';
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

