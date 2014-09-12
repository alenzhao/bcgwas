<?php include 'header.php'; ?>
<style>
.input-line { margin-top: 0.6ex; margin-bottom: 0.6ex; }
/* .input-wrapper { margin-right: 1.8em; } */
.rotate {
  -moz-transform: rotate(-90.0deg);  /* FF3.5+ */
  -o-transform: rotate(-90.0deg);  /* Opera 10.5 */
  -webkit-transform: rotate(-90.0deg);  /* Saf3.1+, Chrome */
  filter:  progid:DXImageTransform.Microsoft.BasicImage(rotation=0.083);  /* IE6,IE7 */
  -ms-filter: "progid:DXImageTransform.Microsoft.BasicImage(rotation=0.083)"; /* IE8 */
}
</style>

<div class='ui-layout-north'>
<form>
  <div class="input-line">
  <b>Find SNPs where...</b>
  </div>
  <div class="input-line">
    <span class="input-wrapper">
      the SNP name is
      <input type="text" id="filter_snp_name">,
    </span>
  </div>
  <div class="input-line">
    <span class="input-wrapper">
      and that occur within the gene corresponding to the gene symbol
      <input type="text" id="filter_gene_symbol">,
    </span>
    <span class="input-wrapper">
      the gene accession
      <input type="text" id="filter_gene_accession">,
    </span>
    <span class="input-wrapper">
      and the Entrez id,
      <input type="text" id="filter_entrez_id">,
    </span>
  </div>
  <div class="input-line">
    <span class="input-wrapper">
      and that occur on chromosome
      <select id="filter_chromosome">
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
        <option>XY</option>
        <option>M</option>
      </select>,
    </span>
    <span class="input-wrapper">
      between positions
      <input type="text" id="filter_position_lo">
      (start) and
      <input type="text" id="filter_position_hi">
      (stop),
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
    <th>snp_name</th>
    <th>chromosome</th>
    <th>position</th>
    <th>missing_n1</th>
    <th>missing_n2</th>
    <th>X3014</th> <th>B708</th> <th>X3111</th> <th>B290</th> <th>B895</th> <th>X3593</th> <th>B900</th> <th>X4005</th> <th>B402</th> <th>B996</th> <th>X4114</th> <th>A492</th> <th>B420</th> <th>X4279</th> <th>A513</th> <th>B482</th> <th>X5471</th> <th>A602</th> <th>B499</th> <th>X5509</th> <th>A707</th> <th>B562</th> <th>X5716</th> <th>A856</th> <th>B580</th> <th>X5800</th> <th>A857</th> <th>X5837</th> <th>A932</th> <th>X5899</th> <th>B101</th> <th>B625</th> <th>N10</th> <th>N11</th> <th>N18</th> <th>N19</th> <th>N21</th> <th>N25</th> <th>N27</th> <th>N28</th> <th>N29</th> <th>N31</th> <th>N32</th> <th>N34</th> <th>N39</th> <th>N40</th> <th>N41</th> <th>N44</th> <th>N45</th> <th>N47</th> <th>N50</th> <th>N52</th> <th>N54</th> <th>N57</th> <th>N78</th> <th>N80</th> <th>N81</th> <th>N84</th> <th>N88</th> <th>N95</th> <th>N97</th> <th>N104</th>
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

  function get_selected(id) {
    var x = $("#filter_chromosome :selected").text();
    if (x == "(any)")
      return "";
    else
      return x;
  }

  function resize_table() {
    // Update the scrollable area's height.
    var h = layout.center.state.innerHeight - get_table_header_and_footer_height();
    $("div.dataTables_scrollBody").height(h + "px");
    // // Update the scrollable area's width.
    // var h = $("div.dataTables_scrollHeadInner").width();
    // $("div.dataTables_scrollHead").width(w + "px");
    // $("div.dataTables_scrollBody").width(w + "px");
    datatable.fnDraw();
  }

  function get_table_header_and_footer_height() {
    // return 2*$("#table-wrapper table").height();
    return ($("#table-wrapper .dataTables_scrollHead").height() + // header
            $("#table-wrapper .dataTables_info").height() +       // footer
            5);                                                   // fudge
  }

  $("#table-wrapper table").dataTable({
    "sDom": "rtiS",
    //"bPaginate": false,
    //"aaSorting": [[0, "desc"]],
    // Server side source:
    "bProcessing": true,
    "bServerSide": true,
    "sAjaxSource": "geno_feed.php",
    "bFilter": false,
    "fnServerData": function (sSource, aoData, fnCallback) {
      aoData.push({"name": "filter_snp_name",       "value": $("#filter_snp_name").val()});
      aoData.push({"name": "filter_gene_symbol",    "value": $("#filter_gene_symbol").val()});
      aoData.push({"name": "filter_gene_accession", "value": $("#filter_gene_accession").val()});
      aoData.push({"name": "filter_entrez_id",      "value": $("#filter_entrez_id").val()});
      aoData.push({"name": "filter_chromosome",     "value": get_selected("filter_chromosome")});
      aoData.push({"name": "filter_position_lo",    "value": $("#filter_position_lo").val()});
      aoData.push({"name": "filter_position_hi",    "value": $("#filter_position_hi").val()});
      $.getJSON(sSource, aoData, function (json) { fnCallback(json) });
    },
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
    // // Infinite scrolling:
    // "bScrollInfinite": true,
    // "bScrollCollapse": true,
    // "sScrollY": "800px",
    // Scroller-based infinite scrolling: 
    //"sScrollY": "200px",
    "sScrollY": (layout.center.state.innerHeight - get_table_header_and_footer_height()) + "px", // only provisional, reset by resize_table below
    "sScrollX": "100%",
    "sScrollXInner": "120%",
    "oScroller": {
      "loadingIndicator": false
    },
    "fnInitComplete": resize_table
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
