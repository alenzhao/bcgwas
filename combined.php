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
<form action="#" id="filter-form">
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
      that occur within a gene corresponding to the gene symbol
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
    <span class="input-wrapper">
      and whose gene name contains
      <input type="text" id="filter_gene_name">,
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
      <input type="text" id="filter_start">
      (start) and
      <input type="text" id="filter_stop">
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
<table border="0" cellspacing="0" cellpadding="0" class="stripe">
  <thead>
    <tr>
    <th>SNP Name</th>
    <th>Chromosome</th>
    <th>Position</th>
    <th>Containing genes</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>
</div> <!-- table-wrapper -->
</div> <!-- ui-layout-center -->

<div class='ui-layout-east'>
<!-- 120 * 3 (width of 3 boxplots) + 0 (fudge) = 360 -->
<div id="boxplot-wrapper" style="width: 360px;"></div>
</div>

<script>
// Returns a function to compute the interquartile range. (http://bl.ocks.org/mbostock/4061502)
function iqr(k) {
  return function(d, i) {
    var q1 = d.quartiles[0],
        q3 = d.quartiles[2],
        iqr = (q3 - q1) * k,
        i = -1,
        j = d.length;
    while (d[++i] < q1 - iqr);
    while (d[--j] > q3 + iqr);
    return [i, j];
  };
}

function boxplot(snp_id, gene_id) {
  $.ajax({
    "url": "boxplot_feed.php",
    "data": {
      "snp_id": snp_id,
      "gene_id": gene_id
    },
    "dataType": "json"
  }).success(function(json) {
    var gene_display;
    if (json.gene_symbol != "")         gene_display = json.gene_symbol;
    else if (json.gene_accession != "") gene_display = json.gene_accession;
    else if (json.entrez_id != "")      gene_display = json.entrez_id;
    else                                gene_display = json.gene_id;

    // Config.
    var pane_height = nf_the_layout.east.state.innerHeight;
    var margin = {top: 10, right: 50, bottom: 20, left: 50},
        width = 120 - margin.left - margin.right,
        height = pane_height - margin.top - margin.bottom;

    // Parse the data. Along the way, find the min and max values.
    var min = Infinity,
        max = -Infinity,
        num_unknown = 0;
    data = [[], [], []];
    if (json.snps.length != json.exprs.length)
      throw "snps and exprs are not of same length";
    for (var i = 0; i < json.snps.length; ++i) {
      var snp = json.snps[i];
      var expr = json.exprs[i];
      var j;
      if      (snp == "AA") j = 0;
      else if (snp == "AB") j = 1;
      else if (snp == "BB") j = 2;
      else if (snp == "unknown") { ++num_unknown; continue; }
      else throw "Unknown snp: " + snp;
      data[j].push(expr);
      if (expr > max) max = expr;
      if (expr < min) min = expr;
    }
    console.log(data);

    var chart = d3.box()
        .whiskers(iqr(1.5))
        .width(width)
        .height(height);
    chart.domain([min, max]);

    var svg = d3.select("#boxplot-wrapper").selectAll("svg")
        .data(data);
    svg.enter().append("svg")
        .attr("class", "boxplot")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.bottom + margin.top)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")")
        .call(chart)
    svg.select("g").call(chart);

    console.log("made it");
  });
}

var theDataTable;
$(document).ready(function() {

  function init_layout() {
    var layout = $('body').layout({
      'resizable': false,
      'closable': false,
      'north': {'size': 'auto'},
      'east': {'size': '370px'},
      'center__onresize': resize_table,
      'east__onresize': resize_boxplot
    });
    return layout;
  }

  $('#definitions').hide();
  $('#error_message').hide();

  // $('#T')     .change(function() { onchange_T();      update(); });
  // $('#method').change(function() { onchange_method(); update(); });
  // $('select') .change(update);

  var layout = init_layout();
  window.nf_the_layout = layout;
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
    console.log(["resizing table:", theDataTable]);
    theDataTable.draw(); // XXX is this really good?
  }

  function resize_boxplot() {
    console.log(["resizing boxplot:", theDataTable]);
    var pane_height = nf_the_layout.east.state.innerHeight;
    $("#boxplot-wrapper svg").height(pane_height);
  }

  function get_table_header_and_footer_height() {
    // return 2*$("#table-wrapper table").height();
    return ($("#table-wrapper .dataTables_scrollHead").height() + // header
            $("#table-wrapper .dataTables_info").height() +       // footer
            5);                                                   // fudge
  }

  theDataTable = $("#table-wrapper table").DataTable({
    "dom": "rtiS",
    //"bPaginate": false,
    //"aaSorting": [[0, "desc"]],
    // Server side source:
    "processing": true,
    "serverSide": true,
    "ajaxSource": "combined_feed.php",
    "filter": false,
    "serverData": function (source, data, callback) {
      data.push({"name": "filter_snp_name",       "value": $("#filter_snp_name").val()});
      data.push({"name": "filter_gene_name",      "value": $("#filter_gene_name").val()});
      data.push({"name": "filter_gene_symbol",    "value": $("#filter_gene_symbol").val()});
      data.push({"name": "filter_gene_accession", "value": $("#filter_gene_accession").val()});
      data.push({"name": "filter_entrez_id",      "value": $("#filter_entrez_id").val()});
      data.push({"name": "filter_chromosome",     "value": get_selected("filter_chromosome")});
      data.push({"name": "filter_start",          "value": $("#filter_start").val()});
      data.push({"name": "filter_stop",           "value": $("#filter_stop").val()});
      $.getJSON(source, data, function (json) { callback(json) });
    },
    "columnDefs": [{
      "targets": 3, // containing genes
      "data": null, // Use the full data source object for the renderer's source
      "render": function(data, type, full, meta) {
        // The format of the input data[3] is:
        // snp_id;gene_id1,gene_symbol1;gene_id2,gene_symbol2...
        // or:
        // snp_id; (note trailing semicolon)
        // if there are no containing genes.
        var genes = data[3].split(";");
        var snp_id = genes.shift();
        if (genes[0] == "") // if there's no containing gene, don't display link
          return "-";
        var links = [];
        for (var i = 0; i < genes.length; ++i) {
          var tmp = genes[i].split(",");
          var gene_id = tmp[0];
          var gene_symbol = tmp[1];
          links.push("<a href='javascript:boxplot(" + snp_id + ", " + gene_id + ")'>" + gene_symbol + "</a>");
        }
        return links.join(", ");
      }
    }],
    // Scroller-based infinite scrolling: 
    //"scrollY": "200px",
    "scrollY": (layout.center.state.innerHeight - get_table_header_and_footer_height()) + "px", // only provisional, reset by resize_table below
    "scrollX": "100%",
    //"scrollXInner": "120%",
    "scroller": {
      "loadingIndicator": false
    },
    "initComplete": resize_table
  });

  $("#filter-form").submit(function() {
    resize_table();
    //theDataTable.draw();
    return false;
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
