import argparse
import csv
import os
import sqlite3
import subprocess

# Usage:
#   python3 --datadir /tier2/deweylab/scratch/nathanae/mn/bcgwas --step 1

p = argparse.ArgumentParser()
p.add_argument("--datadir", required=True)
p.add_argument("--steps", type=int, nargs="+", choices=(0, 1, 2, 3))
args = p.parse_args()

download_dir = os.path.join(args.datadir, "bcgwas_download")

conn = sqlite3.connect("{}/bcgwas.db".format(args.datadir))
conn.row_factory = sqlite3.Row
c = conn.cursor()

if 0 in args.steps:
  os.mkdir(download_dir)

if 1 in args.steps:
  columns = ["snp_id",
             "snp_name",
             "position",
             "chromosome",
             "missing_n1",
             "missing_n2",
             "X3014", "B708", "X3111", "B290", "B895", "X3593", "B900",
             "X4005", "B402", "B996", "X4114", "A492", "B420", "X4279", "A513",
             "B482", "X5471", "A602", "B499", "X5509", "A707", "B562", "X5716",
             "A856", "B580", "X5800", "A857", "X5837", "A932", "X5899", "B101",
             "B625", "N10", "N11", "N18", "N19", "N21", "N25", "N27", "N28",
             "N29", "N31", "N32", "N34", "N39", "N40", "N41", "N44", "N45",
             "N47", "N50", "N52", "N54", "N57", "N78", "N80", "N81", "N84",
             "N88", "N95", "N97", "N104"]
  columns_str = ", ".join("geno." + c for c in columns)
  c.execute("""
  SELECT {}, combined.containing_gene_ids_and_symbols
  FROM geno 
  JOIN combined ON geno.snp_id=combined.snp_id
  """.format(columns_str))
  with open(os.path.join(download_dir, "geno.csv"), "w", newline="") as f:
    writer = csv.writer(f)
    writer.writerow(columns + ["containing_gene_ids"])
    for dbrow in c:
      row = [dbrow[c] for c in columns]
      tmp = dbrow["containing_gene_ids_and_symbols"].split(";")
      tmp = [x.split(",")[0] for x in tmp]
      containing_gene_ids = "|".join(tmp)
      row.append(containing_gene_ids)
      writer.writerow(row)

if 2 in args.steps:
  columns = ["gene_id",
             "gene_name",
             "gene_symbol",
             "gene_accession",
             "entrez_id",
             "chromosome",
             "cytoband",
             "start",
             "stop",
             "strand",
             "cross_hybridization",
             "probeset_type",
             "X3014", "B708", "X3111", "B290", "B895", "X3593", "B900",
             "X4005", "B402", "B996", "X4114", "A492", "B420", "X4279", "A513",
             "B482", "X5471", "A602", "B499", "X5509", "A707", "B562", "X5716",
             "A856", "B580", "X5800", "A857", "X5837", "A932", "X5899", "B101",
             "B625", "N10", "N11", "N18", "N19", "N21", "N25", "N27", "N28",
             "N29", "N31", "N32", "N34", "N39", "N40", "N41", "N44", "N45",
             "N47", "N50", "N52", "N54", "N57", "N78", "N80", "N81", "N84",
             "N88", "N95", "N97", "N104"]
  columns_str = ", ".join("expr." + c for c in columns)
  c.execute("SELECT {} FROM expr".format(columns_str))
  with open(os.path.join(download_dir, "expr.csv"), "w", newline="") as f:
    writer = csv.writer(f)
    writer.writerow(columns)
    for dbrow in c:
      row = [dbrow[c] for c in columns]
      writer.writerow(row)

if 3 in args.steps:
  #subprocess.check_call(["tar", "cvfz", "bcgwas_download.tar.gz",
  #                       "bcgwas_download"], cwd=args.datadir)
  subprocess.check_call(["zip", "bcgwas_download.zip",
                         "bcgwas_download/geno.csv",
                         "bcgwas_download/expr.csv"], cwd=args.datadir)
