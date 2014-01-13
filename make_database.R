require("plyr")

# This script makes an sqlite database from the CSV files provided by Fred.
#
# Usage:
#
#   In R:
#   > datadir = "/tier2/deweylab/scratch/nathanae/mn/bcgwas"
#   > source("make_database.R")
#
#   Then, at the command line:
#   $ cd /tier2/deweylab/scratch/nathanae/mn/bcgwas
#   $ time sqlite3 bcgwas.db < bcgwas.sql
#
#   Optionally:
#   $ rm bcgwas.sql
stopifnot(exists("datadir"))

cat("Load data.\n")
expr      = read.csv(sprintf("%s/expr.c.csv", datadir), row.names=1)
geno      = read.csv(sprintf("%s/geno.ord.csv", datadir), row.names=1)
snp_ann   = read.csv(sprintf("%s/snpAnn.csv", datadir), row.names=1)
trait_ann = read.csv(sprintf("%s/traitAnn.csv", datadir), row.names=1)
stopifnot(all(names(expr) == names(geno)))
stopifnot(all(rownames(geno) == snp_ann$rs.id))
stopifnot(all(rownames(expr) == as.character(trait_ann$TranscriptClusterID)))

cat("Open SQL file.\n")
fo = file(sprintf("%s/bcgwas.sql", datadir), open="w")
sql = function(x) { cat(sprintf("%s;\n", x), file=fo); }
sql("begin transaction")

cat("Create expr table.\n")
expr_patient_cols = paste0(sprintf("%s real", names(expr)), collapse=", ")
create_expr = sprintf("
  create table expr (
    gene_id             integer primary key, 
    gene_name           text,
    gene_symbol         text,
    gene_accession      text,
    entrez_id           integer,
    chromosome          text,
    cytoband            text,
    start               integer,
    stop                integer,
    strand              text,
    cross_hybridization integer,
    probeset_type       text,
    %s
  )", expr_patient_cols)
sql(create_expr)
sql("create index expr_gene_name           on expr (gene_name)")
sql("create index expr_gene_symbol         on expr (gene_symbol)")
sql("create index expr_gene_accession      on expr (gene_accession)")
sql("create index expr_entrez_id           on expr (entrez_id)")
sql("create index expr_chromosome          on expr (chromosome)")
sql("create index expr_cytoband            on expr (cytoband)")
sql("create index expr_start               on expr (start)")
sql("create index expr_stop                on expr (stop)")
sql("create index expr_strand              on expr (strand)")
sql("create index expr_cross_hybridization on expr (cross_hybridization)")
sql("create index expr_probeset_type       on expr (probeset_type)")

cat("Create geno table.\n")
geno_patient_cols = paste0(sprintf("%s integer", names(expr)), collapse=", ")
create_geno = sprintf("
  create table geno (
    snp_id      integer primary key autoincrement,
    snp_name    text,
    position    integer,
    chromosome  text,
    missing_n1  real,
    missing_n2  real,
    %s
  )", geno_patient_cols)
sql(create_geno)
sql("create index geno_snp_name   on geno (snp_name)")
sql("create index geno_position   on geno (position)")
sql("create index geno_chromosome on geno (chromosome)")

cat("Prepare to insert expr data.\n")
meta_cols = "gene_id, gene_name, gene_symbol, gene_accession, entrez_id, chromosome, cytoband, start, stop, strand, cross_hybridization, probeset_type"
expr_chromosome = trait_ann$Chromosome
expr_chromosome = gsub("chr", "", expr_chromosome)
expr_chromosome = gsub("_random", "", expr_chromosome)
meta_data = matrix(c(
  sprintf("\"%s\"", trait_ann$TranscriptClusterID), # These sprintf convert
  sprintf("\"%s\"", trait_ann$GeneName),            # NA to "\"NA\"" or "NA".
  sprintf("\"%s\"", trait_ann$GeneSymbol),
  sprintf("\"%s\"", trait_ann$GeneAccession),
  sprintf("%d",     trait_ann$EntrezID),
  sprintf("\"%s\"", expr_chromosome),
  sprintf("\"%s\"", trait_ann$Cytoband),
  sprintf("%d",     trait_ann$Start),
  sprintf("%d",     trait_ann$Stop),
  sprintf("\"%s\"", trait_ann$Strand),
  sprintf("%d",     trait_ann$CrossHybridization),
  sprintf("\"%s\"", trait_ann$ProbesetType)
), nrow(trait_ann))
meta_data[meta_data == "\"NA\""] = "NULL"
meta_data[meta_data == "NA"    ] = "NULL"
meta_data = apply(meta_data, 1, paste0, collapse=", ")
patient_cols = paste0(names(expr), collapse=", ")
patient_data = apply(expr, 1, paste0, collapse=", ")
stopifnot(all(trait_ann$TranscriptClusterID == names(patient_data)))
stmts = sprintf("insert into expr (%s, %s) values (%s, %s)", meta_cols, patient_cols, meta_data, patient_data)

cat("Insert expr data.\n")
llply(stmts, function(stmt) { sql(stmt) }, .progress="text")

cat("Prepare to insert geno data.\n")
stopifnot(all(snp_ann$rs.id == rownames(geno)))
meta_cols = "snp_name, position, chromosome, missing_n1, missing_n2"
#meta_data = sprintf("\"%s\", %d, %d, %.16f, %.16f", snp_ann$rs.id, snp_ann$position, snp_ann$chromosome, snp_ann$missing.n1, snp_ann$missing.n2)
snp_chromosome = sprintf("%d", snp_ann$chromosome)
snp_chromosome = gsub("23", "X", snp_chromosome)
snp_chromosome = gsub("24", "Y", snp_chromosome)
snp_chromosome = gsub("25", "XY", snp_chromosome)
snp_chromosome = gsub("26", "M", snp_chromosome)
meta_data = matrix(c(
  sprintf("\"%s\"", snp_ann$rs.id),
  sprintf("%d",     snp_ann$position),
  sprintf("\"%s\"", snp_chromosome),
  sprintf("%.16f",  snp_ann$missing.n1),
  sprintf("%.16f",  snp_ann$missing.n2)
), nrow(snp_ann))
meta_data[meta_data == "\"NA\""] = "NULL"
meta_data[meta_data == "NA"    ] = "NULL"
meta_data = apply(meta_data, 1, paste0, collapse=", ")
patient_cols = paste0(names(geno), collapse=", ")
patient_data = as.matrix(geno)
patient_data[which(is.na(patient_data))] = "NULL" # also converts non-na entries to strings
patient_data = apply(patient_data, 1, paste0, collapse=", ")
stmts = sprintf("insert into geno (%s, %s) values (%s, %s)", meta_cols, patient_cols, meta_data, patient_data)

cat("Insert geno data.\n")
llply(stmts, function(stmt) { sql(stmt) }, .progress="text")

cat("Close SQL file.\n")
sql("commit")
close(fo)
