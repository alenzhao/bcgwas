<?php include 'header.php'; ?>
<style>
.input-line { margin-top: 0.6ex; margin-bottom: 0.6ex; }
/* .input-wrapper { margin-right: 1.8em; } */
</style>

<div class='ui-layout-north'>
<form>
  <div class="input-line">
  <b>Find genes where...</b>
  </div>
  <div class="input-line">
    <span class="input-wrapper">
      the gene name contains
      <input type="text" name="gene_name">,
    </span>
    <span class="input-wrapper">
      the gene symbol is
      <input type="text" name="gene_symbol">,
    </span>
    <span class="input-wrapper">
      the gene accession is
      <input type="text" name="gene_accession">,
    </span>
    <span class="input-wrapper">
      and the Entrez id is
      <input type="text" name="entrez_id">,
    </span>
  </div>
  <div class="input-line">
    <span class="input-wrapper">
      that occur on chromosome
      <select name="chromosome">
        <option>(any)</option>
        <option>1</option>
        <option>2</option>
        <option>3</option>
        <option>4</option>
        <option>5</option>
        <option>6</option>
        <option>7</option>
        <option>8</option>
        <option>9</option>
        <option>10</option>
        <option>11</option>
        <option>12</option>
        <option>13</option>
        <option>14</option>
        <option>15</option>
        <option>16</option>
        <option>17</option>
        <option>18</option>
        <option>19</option>
        <option>20</option>
        <option>21</option>
        <option>22</option>
        <option>X</option>
        <option>Y</option>
        <option>M</option>
      </select>,
    </span>
    <span class="input-wrapper">
      on strand
      <select name="strand">
        <option>(any)</option>
        <option>+</option>
        <option>-</option>
        <option>?</option>
      </select>,
    </span>
    <span class="input-wrapper">
      between positions
      <input type="text" name="position_lo">
      (start) and
      <input type="text" name="position_hi">
      (stop),
    </span>
  </div>
  <div class="input-line">
    <span class="input-wrapper">
      where there
      <select name="cross_hybridization">
        <option>(any)</option>
        <option>is</option>
        <option>isn't</option>
      </select>
      cross hybridization,
    </span>
    <span class="input-wrapper">
      and the probeset type is
      <select name="probeset_type">
        <option>(any)</option>
        <option>control->affx</option>
        <option>main</option>
      </select>.
    </span>
  </div>
  <div class="input-line">
    <input type="submit" value="Filter">
  </div>
</form>
</div> <! -- ui-layout-north -->

<div class='ui-layout-center'>
<div id='error_message'></div>
<div id="table-wrapper">
<table border="0" cellspacing="0" cellpadding="0">
  <thead>
    <tr>
      <!--
      <th>gene_name</th>
      <th>gene_symbol</th>
      <th>chromosome</th>
      <th>start</th>
      <th>stop</th>
      -->
    <th>gene_name</th>
    <th>gene_symbol</th>
    <th>gene_accession</th>
    <th>entrez_id</th>
    <th>chromosome</th>
    <th>cytoband</th>
    <th>start</th>
    <th>stop</th>
    <th>strand</th>
    <th>cross_hybridization</th>
    <th>probeset_type</th>
    <!-- <th>X3014</th> <th>B708</th> <th>X3111</th> <th>B290</th> <th>B895</th> <th>X3593</th> <th>B900</th> <th>X4005</th> <th>B402</th> <th>B996</th> <th>X4114</th> <th>A492</th> <th>B420</th> <th>X4279</th> <th>A513</th> <th>B482</th> <th>X5471</th> <th>A602</th> <th>B499</th> <th>X5509</th> <th>A707</th> <th>B562</th> <th>X5716</th> <th>A856</th> <th>B580</th> <th>X5800</th> <th>A857</th> <th>X5837</th> <th>A932</th> <th>X5899</th> <th>B101</th> <th>B625</th> <th>N10</th> <th>N11</th> <th>N18</th> <th>N19</th> <th>N21</th> <th>N25</th> <th>N27</th> <th>N28</th> <th>N29</th> <th>N31</th> <th>N32</th> <th>N34</th> <th>N39</th> <th>N40</th> <th>N41</th> <th>N44</th> <th>N45</th> <th>N47</th> <th>N50</th> <th>N52</th> <th>N54</th> <th>N57</th> <th>N78</th> <th>N80</th> <th>N81</th> <th>N84</th> <th>N88</th> <th>N95</th> <th>N97</th> <th>N104</th> -->
    </tr>
  </thead>
  <tbody></tbody>
</table>
</div> <!-- table-wrapper -->
</div> <!-- ui-layout-center -->

<script>
$(document).ready(function() {

  function init_layout() {
    var layout = $('body').layout({
      'resizable': false,
      'closable': false,
      'north': {'size': 'auto'},
      'east': {'size': '370px'}
    });
    return layout;
  }

  $('#definitions').hide();
  $('#error_message').hide();

  // $('#T')     .change(function() { onchange_T();      update(); });
  // $('#method').change(function() { onchange_method(); update(); });
  // $('select') .change(update);

  var layout = init_layout();
  $('#show_definitions').click(function() { show_definitions(layout); });

  $("#table-wrapper table").dataTable({
    //"bPaginate": false,
    //"aaSorting": [[0, "desc"]],
    // Server side source:
    "bProcessing": true,
    "bServerSide": true,
    "sAjaxSource": "expr_feed.php",
    // // Infinite scrolling:
    // "bScrollInfinite": true,
    // "bScrollCollapse": true,
    // "sScrollY": "800px",
    // // Scroller-based infinite scrolling: 
    // "sScrollY": "200px",
    // "sDom": "frtiS",
    // "oScroller": {
    //   "loadingIndicator": true
    // }
  });                                     
});                                       
</script>                                 


<?php

/*
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
    X3014 real, B708 real, X3111 real, B290 real, B895 real, X3593 real, B900 real, X4005 real, B402 real, B996 real, X4114 real, A492 real, B420 real, X4279 real, A513 real, B482 real, X5471 real, A602 real, B499 real, X5509 real, A707 real, B562 real, X5716 real, A856 real, B580 real, X5800 real, A857 real, X5837 real, A932 real, X5899 real, B101 real, B625 real, N10 real, N11 real, N18 real, N19 real, N21 real, N25 real, N27 real, N28 real, N29 real, N31 real, N32 real, N34 real, N39 real, N40 real, N41 real, N44 real, N45 real, N47 real, N50 real, N52 real, N54 real, N57 real, N78 real, N80 real, N81 real, N84 real, N88 real, N95 real, N97 real, N104 real
*/

?>
