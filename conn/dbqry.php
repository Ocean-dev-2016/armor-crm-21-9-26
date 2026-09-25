<?php

require_once __DIR__ . '/db.php';


/**
 * Execute SELECT query
 */
function db_query($sql)
{
    global $conn;

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die("Database Query Error: " . mysqli_error($conn));
    }

    return $result;
}


/**
 * Get single row
 */
function db_row($sql)
{
    $result = db_query($sql);

    return mysqli_fetch_assoc($result);
}


/**
 * Get multiple rows
 */
function db_rows($sql)
{
    $result = db_query($sql);

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Last inserted ID
 */
function db_insert_id()
{
    global $conn;

    return mysqli_insert_id($conn);
}


/**
 * Escape string
 */
function db_escape($value)
{
    global $conn;

    return mysqli_real_escape_string($conn, $value);
}