import argparse
import re
import sqlite3

# Usage:
#   python3 --datadir /tier2/deweylab/scratch/nathanae/mn/bcgwas --step 1

p = argparse.ArgumentParser()
p.add_argument("--datadir", required=True)
p.add_argument("--step", type=int, required=True, choices=(0, 1, 2, 3, 4, 5))
args = p.parse_args()

conn = sqlite3.connect("{}/bcgwas.db".format(args.datadir))
conn.row_factory = sqlite3.Row
c = conn.cursor()

if args.step == 0:
  with conn:
    # Make gene-id-to-coordinates database.
    conn.execute("""
    CREATE TABLE gene_id_to_coords (
        gene_id     integer,
        chromosome  text,
        start       integer,
        stop        integer
    )
    """)
    conn.execute("""
    INSERT INTO gene_id_to_coords
    SELECT gene_id, chromosome, start, stop
    FROM expr
    WHERE gene_id is not null
    """)
    conn.execute("""
    CREATE INDEX gene_id_to_coords_index
    on gene_id_to_coords (gene_id)
    """)

if args.step == 1:
  with conn:
    # Make gene-name-to-id database.
    # Each word of the gene name is stored in the database.
    conn.execute("""
    CREATE TABLE gene_name_words_to_gene_ids (
        gene_name_word text collate nocase,
        gene_id        integer
    )
    """)

    # Populate gene-name-to-id database.
    c.execute("""
    SELECT gene_name, gene_id
    FROM expr
    WHERE gene_name is not null
    """)
    rows = c.fetchall()
    stmts = []
    for gene_name, gene_id in rows:
      for word in re.split(r'[^a-zA-Z0-9]+', gene_name):
        stmts.append("INSERT into gene_name_words_to_gene_ids "
                     "VALUES (\"{}\", {});".format(word, gene_id))
    conn.executescript("\n".join(stmts))

    # Make index for gene-name-to-id database.
    conn.execute("""
    CREATE INDEX gene_name_words_to_gene_ids_index
    on gene_name_words_to_gene_ids (gene_name_word collate nocase)
    """)

if args.step == 2:
  with conn:
    # Make gene-symbol-to-coordinates database.
    conn.execute("""
    CREATE TABLE gene_symbol_to_coords (
        gene_symbol text collate nocase,
        chromosome  text,
        start       integer,
        stop        integer
    )
    """)
    conn.execute("""
    INSERT INTO gene_symbol_to_coords
    SELECT gene_symbol, chromosome, start, stop
    FROM expr
    WHERE gene_symbol is not null
    """)
    conn.execute("""
    CREATE INDEX gene_symbol_to_coords_index
    on gene_symbol_to_coords (gene_symbol collate nocase)
    """)

if args.step == 3:
  with conn:
    # Make gene-accession-to-coordinate database.
    conn.execute("""
    CREATE TABLE gene_accession_to_coords (
        gene_accession text collate nocase,
        chromosome     text,
        start          integer,
        stop           integer
    )
    """)
    conn.execute("""
    INSERT INTO gene_accession_to_coords
    SELECT gene_accession, chromosome, start, stop
    FROM expr
    WHERE gene_accession is not null and chromosome is not null
    """)
    conn.execute("""
    CREATE INDEX gene_accession_to_coords_index
    on gene_accession_to_coords (gene_accession collate nocase)
    """)

if args.step == 4:
  with conn:
    # Make entrez-to-coordinate database.
    conn.execute("""
    CREATE TABLE entrez_id_to_coords (
        entrez_id  integer,
        chromosome text,
        start      integer,
        stop       integer
    )
    """)
    conn.execute("""
    INSERT INTO entrez_id_to_coords
    SELECT entrez_id, chromosome, start, stop
    FROM expr
    WHERE entrez_id is not null AND entrez_id != -1
    """)
    conn.execute("""
    CREATE INDEX entrez_id_to_coords_index
    on entrez_id_to_coords (entrez_id)
    """)

if args.step == 5:
  with conn:
    # Make database with just the stuff we want to show in the main table.
    conn.execute("""
    CREATE TABLE combined (
        snp_id                          integer,
        snp_name                        text collate nocase,
        chromosome                      text,
        position                        integer,
        containing_gene_ids_and_symbols text
    )
    """)
    # Populate the database.
    c.execute("""
    SELECT snp_id, snp_name, chromosome, position
    FROM geno
    """)
    snp_rows = c.fetchall()
    values = []
    pct = 0
    for i, (snp_id, snp_name, chromosome, position) in enumerate(snp_rows):
      if int(100.0*i/len(snp_rows)) != pct:
        pct = int(100.0*i/len(snp_rows))
        print("{} percent done".format(pct))
      c.execute("""
      SELECT gene_id, gene_symbol
      FROM expr
      WHERE chromosome = ? and start <= ? and stop >= ?
      """, (chromosome, position, position))
      gene_rows = c.fetchall()
      containing = ";".join("{},{}".format(gene_id, gene_symbol)
                            for gene_id, gene_symbol in gene_rows)
      values.append((snp_id, snp_name, chromosome, position, containing))
    c.executemany("""
    INSERT into combined
    VALUES (?, ?, ?, ?, ?)
    """, values)
    # Index the database.
    conn.execute("""
    CREATE INDEX combined_snp_name on combined (snp_name collate nocase);
    """)
    conn.execute("""
    CREATE INDEX combined_location on combined (chromosome, position);
    """)
