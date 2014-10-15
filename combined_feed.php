<?php
ini_set('display_errors', "1");
error_reporting(E_ALL);

require_once 'filter.php';

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
//$columns = array("gene_name", "gene_symbol", "chromosome", "start", "stop");
$columns = array(
    "snp_name",
    "chromosome",
    "position",
    "containing_gene_ids_and_symbols"
);

/* DB table to use */
//$sTable = "geno";
$sTable = "combined";

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
foreach ($columns as $col) {
    if ($col == "containing_gene_ids_and_symbols") {
        //array_push($segs, "group_concat(expr.gene_id) gene_ids");
        //array_push($segs, "\"7896952,ATAD3A;7911371,C1orf170\" containing_gene_ids_and_symbols");
        array_push($segs, "containing_gene_ids_and_symbols");
    } else {
        array_push($segs, $col);
    }
}
$columns_seg = "snp_id, " . implode(", ", $segs);

/* 
 * Filtering
 */
$where_seg = get_geno_filter_segment($conn, $bindings);

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
            $sort_col = $columns[intval($_GET["iSortCol_$i"])];
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
$query = "select count(*) from $sTable";
$stmt = $conn->prepare($query);
$result = $stmt->execute();
$arr = $result->fetchArray();
$iTotal = $arr[0];

/* Data set length after filtering */
$query = "
    select count(*)
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
    "query" => $query,
    "sEcho" => isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 0,
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => array()
);
while ($db_row = $result->fetchArray()) {
    $row = array();
    foreach ($columns as $col) {
        if ($col == "containing_gene_ids_and_symbols") {
            $val = $db_row["snp_id"] . ";" . $db_row["containing_gene_ids_and_symbols"];
        } else {
            $val = $db_row[$col];
        }
        array_push($row, $val);
    }
    array_push($output["aaData"], $row);
}

echo json_encode($output);

// vim: sw=4:sts=4:ts=4
?>
