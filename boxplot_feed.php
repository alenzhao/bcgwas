<?php
ini_set('display_errors', "1");
error_reporting(E_ALL);

require "password.php";
require_once "config.php";

// Hardcode the sample ids.
$sample_ids = array("X3014", "B708", "X3111", "B290", "B895", "X3593", "B900", "X4005", "B402", "B996", "X4114", "A492", "B420", "X4279", "A513", "B482", "X5471", "A602", "B499", "X5509", "A707", "B562", "X5716", "A856", "B580", "X5800", "A857", "X5837", "A932", "X5899", "B101", "B625", "N10", "N11", "N18", "N19", "N21", "N25", "N27", "N28", "N29", "N31", "N32", "N34", "N39", "N40", "N41", "N44", "N45", "N47", "N50", "N52", "N54", "N57", "N78", "N80", "N81", "N84", "N88", "N95", "N97", "N104");
$sample_ids_string = implode(", ", $sample_ids);

// Open database.
$dbName = datadir() . "/bcgwas.db";
$conn = new SQLite3($dbName);

// Unpack input.
if (!array_key_exists("snp_id", $_GET) || $_GET["snp_id"] == "")
    die("Need snp_id");
if (!array_key_exists("gene_id", $_GET) || $_GET["gene_id"] == "")
    die("Need gene_id");
$snp_id = $_GET["snp_id"];
$gene_id = $_GET["gene_id"];

// Get stuff from geno.
$query = "
    select snp_name, position, chromosome, $sample_ids_string
    from geno
    where snp_id = :snp_id
";
$stmt = $conn->prepare($query);
$stmt->bindValue("snp_id", $snp_id);
$result = $stmt->execute();
$geno_row = $result->fetchArray();
if (!$geno_row)
    die("Need exactly one result, but got zero.");
if ($result->fetchArray())
    die("Need exactly one result, but got at least two.");

// Get stuff from expr.
$query = "
    select gene_name,
           gene_symbol,
           gene_accession,
           entrez_id,
           chromosome,
           cytoband,
           start,
           stop,
           strand,
           cross_hybridization,
           probeset_type,
           $sample_ids_string
    from expr
    where gene_id = :gene_id
";
$stmt = $conn->prepare($query);
$stmt->bindValue("gene_id", $gene_id);
$result = $stmt->execute();
$expr_row = $result->fetchArray();
if (!$expr_row)
    die("Need exactly one result, but got zero.");
if ($result->fetchArray())
    die("Need exactly one result, but got at least two.");
if ($expr_row["chromosome"] != $geno_row["chromosome"])
    die("SNP and gene chromosome are different.");

// Produce output.
$output = array(
    "snp_name" => $geno_row["snp_name"],
    "position" => $geno_row["position"],
    "gene_name" => $expr_row["gene_name"],
    "gene_symbol" => $expr_row["gene_symbol"],
    "gene_accession" => $expr_row["gene_accession"],
    "entrez_id" => $expr_row["entrez_id"],
    "chromosome" => $expr_row["chromosome"],
    "cytoband" => $expr_row["cytoband"],
    "start" => $expr_row["start"],
    "stop" => $expr_row["stop"],
    "strand" => $expr_row["strand"],
    "cross_hybridization" => $expr_row["cross_hybridization"],
    "probeset_type" => $expr_row["probeset_type"],
    "sample_ids" => array(),
    "snps" => array(),
    "exprs" => array()
);
$conv = array(0 => "AA", 1 => "AB", 2 => "BB", "" => "unknown");
foreach ($sample_ids as $id) {
    $output["sample_ids"] []= $id;
    $output["snps"] []= $conv[$geno_row[$id]];
    $output["exprs"] []= $expr_row[$id];
}

echo json_encode($output);

// vim: sw=4:sts=4:ts=4
?>
