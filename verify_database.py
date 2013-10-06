import sys, argparse, csv, sqlite3, math

# This file doublechecks that the sqlite database made via make_database.R
# matches the CSV files provided by Fred.
#
# Usage:
#   python3 --datadir /tier2/deweylab/scratch/nathanae/mn/bcgwas

p = argparse.ArgumentParser()
p.add_argument("--datadir", required=True)
args = p.parse_args()

def open_csv(fname):
  f = open(fname)
  d = csv.Sniffer().sniff(f.read(1024))
  f.seek(0)
  r = csv.DictReader(f, dialect=d)
  return r

def fetch_exactly_one(c):
  rows = c.fetchall()
  assert len(rows) == 1
  return rows[0]

def check(a, b, type):
  if b is None:
    ok = (a == "NA")
  else:
    if type == "str":
      ok = (str(a) == str(b))
    if type == "int":
      ok = (int(a) == int(b))
    if type == "float":
      ok = (abs(float(a) - float(b)) <= 1e-7)
  if not ok:
    print("\"{}\" != \"{}\"".format(a, b))
    sys.exit(1)

conn = sqlite3.connect("{}/bcgwas.db".format(args.datadir))
conn.row_factory = sqlite3.Row
c = conn.cursor()

print("Compare to expr.c.csv.")
reader = open_csv("{}/expr.c.csv".format(args.datadir))
for rec in reader:
  gene_id = rec[""]
  c.execute("select * from expr where gene_id=?", (gene_id,))
  row = fetch_exactly_one(c)
  for k in rec.keys():
    if k != "":
      check(rec[k], row[k], "float")

print("Compare to traitAnn.csv.")
reader = open_csv("{}/traitAnn.csv".format(args.datadir))
for rec in reader:
  gene_id = rec["TranscriptClusterID"]
  c.execute("select * from expr where gene_id=?", (gene_id,))
  row = fetch_exactly_one(c)
  check(rec["TranscriptClusterID"], row["gene_id"],             "int")
  check(rec["GeneName"],            row["gene_name"],           "str")
  check(rec["GeneSymbol"],          row["gene_symbol"],         "str")
  check(rec["GeneAccession"],       row["gene_accession"],      "str")
  check(rec["EntrezID"],            row["entrez_id"],           "int")
  check(rec["Chromosome"],          row["chromosome"],          "str")
  check(rec["Cytoband"],            row["cytoband"],            "str")
  check(rec["Start"],               row["start"],               "int")
  check(rec["Stop"],                row["stop"],                "int")
  check(rec["Strand"],              row["strand"],              "str")
  check(rec["CrossHybridization"],  row["cross_hybridization"], "int")
  check(rec["ProbesetType"],        row["probeset_type"],       "str")

print("Compare to geno.ord.csv.")
reader = open_csv("{}/geno.ord.csv".format(args.datadir))
for rec in reader:
  snp_name = rec[""]
  c.execute("select * from geno where snp_name=?", (snp_name,))
  row = fetch_exactly_one(c)
  for k in rec.keys():
    if k != "":
      check(rec[k], row[k], "float")

print("Compare to snpAnn.csv.")
reader = open_csv("{}/snpAnn.csv".format(args.datadir))
for rec in reader:
  snp_name = rec["rs.id"]
  c.execute("select * from geno where snp_name=?", (snp_name,))
  row = fetch_exactly_one(c)
  check(rec["rs.id"],      row["snp_name"],   "str")
  check(rec["position"],   row["position"],   "int")
  check(rec["chromosome"], row["chromosome"], "int")
  check(rec["missing.n1"], row["missing_n1"], "float")
  check(rec["missing.n2"], row["missing_n2"], "float")
