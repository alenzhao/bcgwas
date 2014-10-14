<?php

set_time_limit(0);
require_once 'filter.php';
require_once 'zipstream.php';

// Based on http://webcheatsheet.com/php/get_current_page_url.php
function get_current_url() {
  $u = "http";
  if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
    $u .= "s";
  $u .= "://";
  $u .= $_SERVER["SERVER_NAME"];
  if ($_SERVER["SERVER_PORT"] != "80")
    $u .= ":" . $_SERVER["SERVER_PORT"];
  $u .= $_SERVER["REQUEST_URI"];
  return $u;
}

# Open a connection to the sqlite database.
$db_name = "../bcgwas_data/bcgwas.db";
$conn = new SQLite3($db_name);

# Create a new zip stream object.
$archive_name = "bcgwas_download";
$filename = "$archive_name.zip";
$zip = new ZipStream($filename, array("send_http_headers" => false));

# Send headers. The cookie tells jquery.fileDownload that the download
# succeeded. The other headers are copied from ZipStream.
header("Content-Type: application/x-zip");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: public");
header("Cache-Control: public, must-revalidate");
header("Content-Transfer-Encoding: binary");
header("Set-Cookie: fileDownload=true; path=/");

# Add file #1 to the ZIP stream: a file that contains metadata about this
# archive.
$url = get_current_url();
$now = date("Y-m-d H:i:s");
$readme = "This archive was downloaded from $url on $now.\n";
$readme .= "\n";
$readme .= "Filters:\n";
if (count($_GET) == 0)
  $readme .= "(none)\n";
else {
  foreach ($_GET as $k => $v)
    $readme .= "- $k: $v\n";
}
$readme .= "\n";
$readme .= "If you use this data, please cite our paper: TDB.\n";
$zip->add_file("$archive_name/readme.txt", $readme);

# Add file #2 to the ZIP stream: a CSV file containg info about the SNPs.
$bindings = array();
$columns = array("snp_id",
                 "snp_name",
                 "position",
                 "chromosome",
                 "missing_n1",
                 "missing_n2",
                 "X3014", "B708", "X3111", "B290", "B895", "X3593", "B900",
                 "X4005", "B402", "B996", "X4114", "A492", "B420", "X4279",
                 "A513", "B482", "X5471", "A602", "B499", "X5509", "A707",
                 "B562", "X5716", "A856", "B580", "X5800", "A857", "X5837",
                 "A932", "X5899", "B101", "B625", "N10", "N11", "N18", "N19",
                 "N21", "N25", "N27", "N28", "N29", "N31", "N32", "N34", "N39",
                 "N40", "N41", "N44", "N45", "N47", "N50", "N52", "N54", "N57",
                 "N78", "N80", "N81", "N84", "N88", "N95", "N97", "N104");
$columns_seg = implode(", ", $columns);
$where_seg = get_geno_filter_segment($conn, $bindings);
$query = "
  select $columns_seg
  from geno
  $where_seg
";
$zip->large_stream_init("$archive_name/geno.csv");
for ($pass = 1; $pass <= 2; ++$pass) {
  $stmt = $conn->prepare($query);
  foreach($bindings as $key => $value)
    $stmt->bindValue($key, $value);
  $result = $stmt->execute();
  $header = implode(",", $columns) . "\r\n";
  $zip->large_stream_add($header, $pass);
  while ($row = $result->fetchArray(SQLITE3_NUM)) {
    // No values in the geno database contain commas or double quotation marks,
    // so no quoting is necessary here.
    $line = implode(",", $row) . "\r\n";
    $zip->large_stream_add($line, $pass);
  }
}

# Add file #3 to the ZIP stream: a CSV file containg info about the SNPs.

# Write archive footer to stream.
$zip->finish();

?>
