<?php

function datadir() {
  if ($_SERVER["SERVER_NAME"] == "localhost") {
    $datadir = "../bcgwas_data/";
  } else {
    $datadir = "../../public/bcgwas_data";
  }
  return $datadir;
}

?>
