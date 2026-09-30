<?php

/**
 * Common DataTables Server-Side Processing Handler
 * 
 * @param array $config
 * - table: Main table name (e.g. 'category cat')
 * - joins: Optional JOIN string (e.g. 'LEFT JOIN company c ON c.id = cat.company_id')
 * - select: Optional SELECT columns (e.g. 'cat.*, c.name AS company_name')
 * - base_conditions: Array of conditions always applied (e.g. ["cat.company_id = 1"])
 * - search_columns: Array of columns to search (e.g. ['cat.name', 'c.name'])
 * - order_columns: Array mapping column index to db field (e.g. [1 => 'cat.name', ...])
 * - default_order_column: Default sorting column (default: 'id')
 * - default_order_dir: Default sorting direction (default: 'DESC')
 * - row_callback: Callback function($row, $srNo) returning array of column values for the row
 */
function handle_datatable(array $config)
{
    $table               = $config['table'];
    $joins               = $config['joins'] ?? '';
    $select              = $config['select'] ?? '*';
    $baseConditions      = $config['base_conditions'] ?? [];
    $searchColumns       = $config['search_columns'] ?? [];
    $orderColumns        = $config['order_columns'] ?? [];
    $defaultOrderColumn  = $config['default_order_column'] ?? 'id';
    $defaultOrderDir     = $config['default_order_dir'] ?? 'DESC';
    $rowCallback         = $config['row_callback'];

    $draw   = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
    $start  = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    $search = $_POST['search']['value'] ?? '';

    // Base WHERE clause
    $baseWhere = !empty($baseConditions) ? 'WHERE ' . implode(' AND ', $baseConditions) : '';

    // Total records count (before filtering/search)
    $totalRow = db_row("SELECT COUNT(*) AS total FROM $table $joins $baseWhere");
    $recordsTotal = (int) ($totalRow['total'] ?? 0);

    // Search filter conditions
    $conditions = $baseConditions;
    if ($search !== '') {
        $search = db_escape($search);
        $searchParts = [];
        foreach ($searchColumns as $col) {
            $searchParts[] = "$col LIKE '%$search%'";
        }
        $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
    }

    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Filtered records count
    $filteredRow = db_row("SELECT COUNT(*) AS total FROM $table $joins $where");
    $recordsFiltered = (int) ($filteredRow['total'] ?? 0);

    // Order handling
    $orderColumn    = $defaultOrderColumn;
    $orderDirection = strtoupper($defaultOrderDir);

    if (isset($_POST['order'][0]['column'])) {
        $columnIndex = (int) $_POST['order'][0]['column'];
        $direction   = $_POST['order'][0]['dir'] ?? 'desc';
        if (isset($orderColumns[$columnIndex]) && $orderColumns[$columnIndex] !== null) {
            $orderColumn    = $orderColumns[$columnIndex];
            $orderDirection = (strtolower($direction) === 'asc') ? 'ASC' : 'DESC';
        }
    }

    // Main query
    $sql = "SELECT $select FROM $table $joins $where ORDER BY $orderColumn $orderDirection LIMIT $start, $length";
    $result = db_rows($sql);

    // Format rows via row_callback
    $data = [];
    $srNo = $start + 1;
    if (!empty($result)) {
        foreach ($result as $row) {
            $data[] = $rowCallback($row, $srNo);
            $srNo++;
        }
    }

    // Send JSON Response
    header('Content-Type: application/json');
    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $data
    ]);
    exit;
}

/**
 * Common Drag & Drop Serial Number HTML Renderer
 * 
 * @param int $srNo Serial Number
 * @return string HTML with move icon and centered number
 */
function dt_drag_sr_no($srNo)
{
    return '<div class="d-flex align-items-center justify-content-between px-1" style="min-width: 50px;"><i data-lucide="move" class="text-muted fs-20 row-drag-handle" style="cursor: move;"></i><span class="ms-auto me-auto fw-medium">' . $srNo . '</span></div>';
}

/**
 * Common Status Switch HTML Renderer
 * 
 * @param int|string $id Record ID (plain or encrypted)
 * @param int|bool $status Current status (1 or 0)
 * @param string $tbl Table name
 * @param bool $isEncrypted Whether $id is already encrypted
 * @return string HTML switch
 */
function dt_status_switch($id, $status, $tbl, $isEncrypted = false)
{
    $encId = $isEncrypted ? $id : encrypt_id($id);
    $checked = ($status == 1 || $status === true || $status === '1') ? 'checked' : '';

    return '<div class="form-check form-switch mb-0">
        <input class="form-check-input change_status" type="checkbox" role="switch" ' . $checked . ' data-id="' . $encId . '" data-tbl="' . htmlspecialchars($tbl) . '">
    </div>';
}

/**
 * Common Action Dropdown HTML Renderer
 * 
 * @param int|string $id Record ID
 * @param string $tbl Table name
 * @param array $options Configuration options:
 *   - can_edit: bool (default: true)
 *   - can_delete: bool (default: true)
 *   - edit_url: string (optional, custom URL for edit link, e.g. SITE_URL.'product/edit/'.$id)
 *   - edit_class: string (default: "{$tbl}_edit")
 *   - is_encrypted: bool (default: false)
 *   - extra_items: string / array of custom li HTML
 * @return string HTML dropdown
 */
function dt_action_dropdown($id, $tbl, array $options = [])
{
    $canEdit     = $options['can_edit'] ?? true;
    $canDelete   = $options['can_delete'] ?? true;
    $editUrl     = $options['edit_url'] ?? '';
    $editClass   = $options['edit_class'] ?? ($tbl . '_edit');
    $isEncrypted = $options['is_encrypted'] ?? false;
    $extraItems  = $options['extra_items'] ?? '';

    $encId = $isEncrypted ? $id : encrypt_id($id);
    $actionItems = '';

    // 1. EDIT FIRST
    if ($canEdit) {
        if (!empty($editUrl)) {
            $actionItems .= '<li>
                <a class="dropdown-item text-primary" href="' . htmlspecialchars($editUrl) . '">
                   <i data-lucide="edit" class="fs-14"></i> Edit
                </a>
            </li>';
        } else {
            $actionItems .= '<li>
                <a class="dropdown-item text-primary ' . htmlspecialchars($editClass) . '" href="javascript:void(0);" data-id="' . $encId . '">
                   <i data-lucide="edit" class="fs-14"></i> Edit
                </a>
            </li>';
        }
    }

    // 2. EXTRA CUSTOM ITEMS (like Setting, etc.)
    if (is_array($extraItems)) {
        $actionItems .= implode('', $extraItems);
    } elseif (is_string($extraItems) && !empty($extraItems)) {
        $actionItems .= $extraItems;
    }

    // 3. DELETE AFTER EDIT
    if ($canDelete) {
        $actionItems .= '<li>
            <a class="dropdown-item text-danger delete-record" href="javascript:void(0);" data-id="' . $encId . '" data-tbl="' . htmlspecialchars($tbl) . '">
               <i data-lucide="trash-2" class="fs-14"></i> Delete
            </a>
        </li>';
    }

    if (!empty($actionItems)) {
        return '
        <div class="dropdown">
            <button class="btn btn-icon btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i data-lucide="more-horizontal" class="fs-16"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                ' . $actionItems . '
            </ul>
        </div>';
    }

    return '<span class="text-muted">-</span>';
}
