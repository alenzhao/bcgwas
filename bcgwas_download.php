<?php

require "password.php";
require_once "config.php";

header("Content-Type: application/x-zip");
header("Content-Disposition: attachment; filename=bcgwas_download.zip");
header("Pragma: public");
header("Cache-Control: public, must-revalidate");
header("Content-Transfer-Encoding: binary");
readfile(datadir() . "/bcgwas_download.zip");

?>
