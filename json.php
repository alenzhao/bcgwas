<?php
ini_set('display_errors', "1");
error_reporting(E_ALL);

/*
 * Script:    DataTables server-side script for PHP and SQLite3
 * Copyright: 2013, Nathanael Fillmore; based on version for MySQL by Allan Jardine.
 * License:   GPL v2 or BSD (3-point)
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */

/* Array of database columns which should be read and sent back to
 * DataTables. Use a space (" ") where you want to insert a non-database
 * field (for example a counter or static image)
 */
$aColumns = array("gene_name", "gene_symbol", "chromosome", "start", "stop");

/* Indexed column (used for fast and accurate table cardinality) */
$sIndexColumn = "gene_id";

/* DB table to use */
$sTable = "expr";

/* Database connection information */
$dbName = "../bcgwas_data/bcgwas.db";


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP server-side, there is
 * no need to edit below this line
 */

/* 
 * MySQL connection
 */
$conn = new SQLite3($dbName);
$bindings = array();

/*
 * Column selection
 */
$segs = array();
foreach ($aColumns as $col)
    if ($col != " ")
        array_push($segs, $col);
$columns_seg = implode(", ", $segs);

/* 
 * Filtering
 * NOTE this does not match the built-in DataTables filtering which does it
 * word by word on any field. It's possible to do here, but concerned about efficiency
 * on very large tables, and MySQL's regex functionality is very limited
 */
/* Filtering over all columns */
$where_segs = array();
if ($_GET["sSearch"] != "") {
    $segs = array();
    foreach ($aColumns as $col)
        if ($col != " ")
            array_push($segs, "$col like :search");
    $bindings["search"] = "%" . $_GET["sSearch"] . "%";
    array_push($where_segs, "(" . implode(" OR ", $segs) . ")");
}
/* Individual column filtering */
for ($i = 0; $i < count($aColumns); $i++) {
    if ($_GET["bSearchable_$i"] == "true" && $_GET["sSearch_$i"] != "") {
        $col = $aColumns[i];
        if ($col != " ") {
            array_push($where_segs, "$col like :search_$i");
            $bindings["search_$i"] = "%" . $_GET["sSearch_$i"] . "%";
        }
    }
}
/* Combine the above */
$where_seg = "";
if (count($where_segs) > 0)
    $where_seg = "where " . implode(" AND ", $where_segs);

/*
 * Ordering
 */
function get_sort_dir($i) {
    $sort_dir = $_GET["sSortDir_$i"];
    if ($sort_dir != "asc" && $sort_dir != "desc")
        $sort_dir = "asc";
    return $sort_dir;
}
$order_seg = "";
if (isset($_GET["iSortCol_0"])) {
    $order_segs = array();
    for ($i = 0; $i < intval($_GET["iSortingCols"]); $i++) {
        if ($_GET["bSortable_" . intval($_GET["iSortCol_$i"])] == "true") {
            $sort_dir = get_sort_dir($i);
            $sort_col = $aColumns[intval($_GET["iSortCol_$i"])];
            array_push($order_segs, "$sort_col $sort_dir");
        }
    }
    $order_seg = "order by " . implode(", ", $order_segs);
}

/* 
 * Paging
 */
$limit_seg = "";
if (isset($_GET["iDisplayStart"]) && $_GET["iDisplayLength"] != "-1") {
    $limit_seg = "limit :display_start, :display_length";
    $bindings["display_start"]  = $_GET["iDisplayStart"];
    $bindings["display_length"] = $_GET["iDisplayLength"];
}

/*
 * SQL queries
 */

/* Total data set length */
$query = "select count($sIndexColumn) from $sTable";
$stmt = $conn->prepare($query);
$result = $stmt->execute();
$arr = $result->fetchArray();
$iTotal = $arr[0];

/* Data set length after filtering */
$query = "
    select count($sIndexColumn)
    from   $sTable
    $where_seg
";
$stmt = $conn->prepare($query);
foreach($bindings as $key => $value)
    $stmt->bindValue($key, $value);
$result = $stmt->execute();
$arr = $result->fetchArray();
$iFilteredTotal = $arr[0];

/* Get data to display */
$query = "
    select $columns_seg
    from   $sTable
    $where_seg
    $order_seg
    $limit_seg
";
$stmt = $conn->prepare($query);
foreach($bindings as $key => $value)
    $stmt->bindValue($key, $value);
$result = $stmt->execute();

/*
 * Output
 */
$output = array(
    "sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => array()
);
while ($db_row = $result->fetchArray()) {
    $row = array();
    for ($i = 0; $i < count($aColumns); $i++)
        if ($aColumns[$i] != " ")
            array_push($row, $db_row[$aColumns[$i]]);
    array_push($output["aaData"], $row);
}

echo json_encode($output);

// vim: sw=4:sts=4:ts=4
?>
