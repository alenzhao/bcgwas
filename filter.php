<?php

// Looks at the $_GET variables and returns a segment of an SQL WHERE clause
// that filters appropriately. The $bindings array is passed by reference. If
// appropriate, key/value pairs will be added to it that should be bound to any
// prepared statement that involves the returned WHERE clause.
function get_geno_filter_segment($conn, &$bindings) {
  // We will filter in several steps. First, if the user has requested to
  // filter by gene name, gene symbol, gene accession, or the Entrez id, we
  // will query the database, and for each of these filters, we'll extract a
  // list of (chromosome, start, stop) tuples, where the filtered SNPs need to
  // match at least one such tuple in every list.

  // Step 1 for gene symbol, accession, and Entrez id.
  $coords = array();
  foreach (array("gene_symbol", "gene_accession", "entrez_id") as $f) {
      if (array_key_exists("filter_$f", $_GET) && $_GET["filter_$f"] != "") {
          $coords[$f] = array();
          $query = "select chromosome, start, stop " .
                   "from ${f}_to_coords " .
                   "where $f = :val";
          $stmt = $conn->prepare($query);
          $stmt->bindValue("val", $_GET["filter_$f"]);
          $result = $stmt->execute();
          while ($r = $result->fetchArray()) {
              $coord = array($r["chromosome"], $r["start"], $r["stop"]);
              array_push($coords[$f], $coord);
          }
      }
  }

  // Step 1 for gene name. Split up the given gene name into words. Find the set
  // of gene_ids matching each word. Intersect these sets. Then look up the
  // coordinates that match the gene_ids in these sets.
  if (array_key_exists("filter_gene_name", $_GET)
      && $_GET["filter_gene_name"] != "") {
      // Split up the given gene name into words.
      $words = preg_split("/[^a-zA-Z0-9]+/", $_GET["filter_gene_name"], -1,
                          PREG_SPLIT_NO_EMPTY);
      $num_words = count($words);
      // Find the set of gene_ids matching each word. 
      $gene_ids_per_word = array();
      for ($i = 0; $i < $num_words; ++$i) {
          $query = "select gene_id from gene_name_words_to_gene_ids " .
                   "where gene_name_word=:val";
          $stmt = $conn->prepare($query);
          $stmt->bindValue("val", $words[$i]);
          $result = $stmt->execute();
          $gene_ids_per_word[$i] = array();
          while ($r = $result->fetchArray()) {
              array_push($gene_ids_per_word[$i], $r["gene_id"]);
          }
      }
      // Intersect these sets.
      if ($num_words == 0) {
          $gene_ids = array();
      } else {
          $gene_ids = $gene_ids_per_word[0];
          for ($i = 1; $i < $num_words; ++$i) {
              $gene_ids = array_intersect($gene_ids, $gene_ids_per_word[$i]);
          }
      }
      // Then look up the coordinates that match the gene_ids in these sets.
      if (count($gene_ids) == 0) {
          $coords["gene_name"] = array();
      } else {
          // Note that the gene_ids are integers, so it is safe to concat these
          // in to the query directly.
          $query = "select chromosome, start, stop " .
                   "from gene_id_to_coords " .
                   "where gene_id in (" . implode(", ", $gene_ids) . ")";
          $stmt = $conn->prepare($query);
          $result = $stmt->execute();
          $coords["gene_name"] = array();
          while ($r = $result->fetchArray()) {
              $coord = array($r["chromosome"], $r["start"], $r["stop"]);
              array_push($coords["gene_name"], $coord);
          }
      }
  }

  // Second, make a similar list for the explicit chromosome/start/stop filter,
  // if it is present. We sanitize the input here because it will be convenient
  // below to put these values right into the sql query.
  $has_chromosome = array_key_exists("filter_chromosome", $_GET)
                    && $_GET["filter_chromosome"] != "";
  $has_start = array_key_exists("filter_start", $_GET)
               && $_GET["filter_start"] != "";
  $has_stop = array_key_exists("filter_stop", $_GET)
              && $_GET["filter_stop"] != "";
  if ($has_chromosome) {
      $c = strtoupper(trim($_GET["filter_chromosome"]));
      $ok_chromosomes = array("1", "10", "11", "12", "13", "14", "15", "16",
                              "17", "18", "19", "2", "20", "21", "22", "3", "4",
                              "5", "6", "7", "8", "9", "M", "X", "Y");
      if (array_search($c, $ok_chromosomes) === false) {
          $coords["explicit"] = array(); // nothing can match
      } else {
          $upper_bound = 247180768; // select max(stop) from expr;
          $s = $has_start ? ((int) $_GET["filter_start"]) : 0;
          $t = $has_stop ? ((int) $_GET["filter_stop"]) : $upper_bound;
          $coords["explicit"] = array(array($c, $s, $t));
      }
  }

  // Third: Now we are ready to start constructing the pieces of the where
  // clause. These pieces will be AND'd to produce the full where clause. We'll
  // start by possibly adding a clause to filter by SNP name.
  $where_segs = array();
  if (array_key_exists("filter_snp_name", $_GET)
      && $_GET["filter_snp_name"] != "") {
      array_push($where_segs, "snp_name=:filter_snp_name");
      $bindings["filter_snp_name"] = $_GET["filter_snp_name"];
  }

  // Fourth, add a where clause segments for each groups of coordinates
  // determined above. Note that $coords only contains elements (which are arrays
  // of coordinates) corresponding to filters that actually were requested by the
  // user. Thus, if an element of $coords is an empty array, this implies that
  // the relevant filter didn't match any coordinates. In that case, we add an
  // impossible condition so that no SNPs are matched.
  foreach ($coords as $name => $these_coords) {
      if (count($these_coords) == 0) {
          array_push($where_segs, "(1=2)"); // impossible
      } else {
          $or_segs = array();
          foreach ($these_coords as $coord) {
              $chromosome = $coord[0];
              $start = $coord[1];
              $stop = $coord[2];
              array_push($or_segs, "(chromosome=\"$chromosome\" and " .
                                   "position >= $start and position <= $stop)");
          }
          array_push($where_segs, "(" . implode(" OR ", $or_segs) . ")");
      }
  }

  // Finally, we AND the where clause segments to actually make the where clause.
  $where_seg = "";
  if (count($where_segs) > 0)
      $where_seg = "where " . implode(" AND ", $where_segs);

  return $where_seg;
}

?>
