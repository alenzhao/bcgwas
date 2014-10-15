<?php require "password.php"; ?>
<?php require "header.php"; ?>
<style>
.input-line { margin-top: 0.6ex; margin-bottom: 0.6ex; }
.input-line input { margin-top: 1px; margin-bottom: 1px; }
.first-input-line { margin-top: 0; }
.last-input-line { margin-bottom: 0; }
.input-wrapper { white-space: nowrap; }
</style>

<div class="ui-layout-north">
<form action="#" id="filter-form">
  <div style="float: right"><a href="logout.php">Logout</a></div>
  <div class="input-line first-input-line">
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
      and the Entrez id
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
  <div class="input-line last-input-line">
    <input type="submit" value="Filter">
    <span style="margin-left: 2.4em">(or <a href="bcgwas_download.php">download</a> the raw data)</span>
    <span style="margin-left: 2.4em; display: none; color: red" id="location-msg">
      (to filter by position, please select a chromosome)
    </span>
  </div>
</form>
</div> <! -- ui-layout-north -->

<div class="ui-layout-center">
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

<!-- 120 * 3 (width of 3 boxplots) + 0 (fudge) = 360 -->
<div class="ui-layout-east">
<div id="boxplot-splash" style="width: 360px;">
Click on a gene in the table to see a boxplots of its expression levels,
grouped by genotype.
</div>
<div id="boxplot-header" style="width: 360px; text-align: center; font-weight: bold;"></div>
<div id="boxplot-wrapper" style="width: 360px;"></div>
<table id="boxplot-footer" width="360" cellspacing="0" cellpadding="0">
<tr>
<td id="boxplot-footer-AA" align="center"></td>
<td id="boxplot-footer-AB" align="center"></td>
<td id="boxplot-footer-BB" align="center"></td>
</tr>
</table>
<div id="boxplot-caption" style="width: 360px"></div>
</div> <!-- ui-layout-east -->

<script>
//////////////////////
// Layout functions //
//////////////////////

// Initializes the layout.
function init_layout() {
  nf_globals.layout = $("body").layout({
    "resizable": false,
    "closable": false,
    "north": {
      "size": "auto",
      "closable": true
    },
    "center": {
      "onresize": resize_table
    },
    "east": {
      "size": (360 + 10) + "px",
      "onresize": resize_boxplot
    }
  });
}

////////////////////
// Form functions //
////////////////////

// Returns the selected chromosome.
function get_selected_chromosome() {
  var x = $("#filter_chromosome :selected").text();
  if (x == "(any)")
    return "";
  else
    return x;
}

// Extracts any data entered in the form to filter SNPs.
function get_form_data() {
  var data = []
  data.push({"name": "filter_snp_name",       "value": $("#filter_snp_name").val()});
  data.push({"name": "filter_gene_name",      "value": $("#filter_gene_name").val()});
  data.push({"name": "filter_gene_symbol",    "value": $("#filter_gene_symbol").val()});
  data.push({"name": "filter_gene_accession", "value": $("#filter_gene_accession").val()});
  data.push({"name": "filter_entrez_id",      "value": $("#filter_entrez_id").val()});
  data.push({"name": "filter_chromosome",     "value": get_selected_chromosome()});
  data.push({"name": "filter_start",          "value": $("#filter_start").val()});
  data.push({"name": "filter_stop",           "value": $("#filter_stop").val()});
  return data;
}

// Initializes the form to filter SNPs.
function init_form() {
  $("#filter-form").submit(function() {
    resize_table();
    return false;
  });
  $("#filter-form input, #filter-form select").change(function() {
    var has_chromosome = (get_selected_chromosome() != "");
    var has_start = ($("#filter_start").val() != "");
    var has_stop = ($("#filter_stop").val() != "");
    var need_location_msg = (!has_chromosome && (has_start || has_stop));
    if (need_location_msg)
      $("#location-msg").show();
    else
      $("#location-msg").hide();
  });
}

//////////////////////////
// Data table functions //
//////////////////////////

// Returns the combined height of the data table's header and footer.
function get_table_header_and_footer_height() {
  return ($("#table-wrapper .dataTables_scrollHead").height() + // header
          $("#table-wrapper .dataTables_info").height() +       // footer
          5);                                                   // fudge
}

// Returns the height we should set the table body to be.
function get_table_body_height() {
  var total = nf_globals.layout.center.state.innerHeight;
  var height = total - get_table_header_and_footer_height();
  return height;
}

// Resizes the data table to fill its pane.
function resize_table() {
  // Update the scrollable area's height.
  $("div.dataTables_scrollBody").height(get_table_body_height() + "px");
  
  // Update the columns' (and the table's) width. This fixes a problem on
  // Windows where the table doesn't quite fill the whole width of the pane.
  nf_globals.data_table.columns.adjust();

  // Redraw the table. XXX: Is this necessary?
  nf_globals.data_table.draw();
}

// Initializes the data table. The layout should have already been initialized
// before this function is called.
function init_table() {
  nf_globals.data_table = $("#table-wrapper table").DataTable({

    // Specify which DOM elements to add around the table.
    "dom": "rtiS",

    // Show a "processing" indicator when the table is being updated.
    "processing": true,

    // Get the data to display as needed from the server.
    "serverSide": true,
    "ajaxSource": "combined_feed.php",
    "serverData": function(source, data, callback) {
      // The data object is a dictionary representing the GET variables we'll
      // send to combined_feed.php. DataTables has already added any variables
      // related to sorting and offsets/limits. Below, we "manually" extract
      // additional GET variables from the filter form and add them to this
      // dictionary. Then, we carry out the AJAX request and return control to
      // DataTables by calling "callback".
      data = data.concat(get_form_data());
      $.getJSON(source, data, function (json) { callback(json) });
    },

    // Use infinite scrolling provided by the "Scroller" plugin. It is
    // important but tricky to get the scrollY height correct here. We set a
    // provisional value here (otherwise weird things happen), but it is
    // updated by resize_table, which is called when the table is done being
    // initialized.
    "scroller": {"loadingIndicator": false},
    "scrollY": get_table_body_height() + "px",
    "scrollX": "100%",
    "initComplete": resize_table,

    // Do some fancy stuff to display the "Containing Genes" column.
    "columnDefs": [{
      // The "Containing Genes" column is the fourth one (index 3).
      "targets": 3,

      // Don't allow the user to sort by this column.
      "sortable": false,

      // Render this column in a pretty way. The format of the raw data for
      // this column is
      //
      //    snp_id;gene_id1,gene_symbol1;gene_id2,gene_symbol2...
      //
      //  unless there are no such containing genes, in which case it is
      //
      //    snp_id; (note trailing semicolon)
      //
      // We display each gene as a link with the gene symbol as the linked
      // text, and the link itself being to a javascript function that plots a
      // boxplot for the appropriate gene/SNP pair.
      "render": function(data, type, full, meta) {
        var genes = data.split(";");
        var snp_id = genes.shift();
        if (genes[0] == "") // if there's no containing gene, don't show link
          return "-";
        var links = [];
        for (var i = 0; i < genes.length; ++i) {
          var pieces = genes[i].split(",");
          var gene_id = pieces[0];
          var gene_symbol = pieces[1];
          var href = "javascript:boxplot(" + snp_id + ", " + gene_id + ")";
          links.push("<a href='" + href + "'>" + gene_symbol + "</a>");
        }
        return links.join(", ");
      }
    }]
  });
}

///////////////////////
// Boxplot functions //
///////////////////////

// Returns a function to compute the interquartile range.
// (From http://bl.ocks.org/mbostock/4061502)
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

// Computes the height to make the SVG elements that actually display each
// boxplot. This is the height of the east pane that contains the boxplots,
// minus the heights of the text above and below the plots.
function compute_boxplot_height() {
  var pane_height = nf_globals.layout.east.state.innerHeight;
  var header_height = $("#boxplot-header").outerHeight();
  var footer_height = $("#boxplot-footer").outerHeight();
  var caption_height = $("#boxplot-caption").outerHeight();
  var height = pane_height - header_height - footer_height - caption_height;
  if (height < 25)
    height = 25;
  if (height > 360*2)
    height = 360*2;
  return height;
}

// Actually draws the boxplots.
function draw_boxplot(json) {
  // Hide the splash screen.
  $("#boxplot-splash").hide();

  // Parse the data, spliting up the expression levels among genotypes, and
  // finding the min and max values.
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

  // Determine the main display name of the gene.
  var gene_display;
  if (json.gene_symbol != "")         { gene_display = json.gene_symbol;    used = "gene_symbol"; }
  else if (json.gene_accession != "") { gene_display = json.gene_accession; used = "gene_accession"; }
  else if (json.entrez_id != "")      { gene_display = json.entrez_id;      used = "entrez_id"; }
  else                                { gene_display = json.gene_id;        used = "gene_id"; }

  // Determine how to display details about the gene.
  var pieces = [];
  if (json.gene_name != "")                                  pieces.push(json.gene_name);
  if (json.gene_symbol != ""    && used != "gene_symbol")    pieces.push("Symbol: " + json.gene_symbol);
  if (json.gene_accession != "" && used != "gene_accession") pieces.push("Accession: " + json.gene_accession);
  if (json.entrez_id != ""      && used != "entrez_id")      pieces.push("Entrez id: " + json.entrez_id);
  var gene_details = pieces.join(", ");

  // Add the header.
  $("#boxplot-header").html(gene_display + "'s expression, grouped by " + json.snp_name + "'s genotype");
  $("#boxplot-header").css("padding-bottom", "10px");

  // Add the footer.
  $("#boxplot-footer-AA").html("" + data[0].length + " AA samples");
  $("#boxplot-footer-AB").html("" + data[1].length + " AB samples");
  $("#boxplot-footer-BB").html("" + data[2].length + " BB samples");
  $("#boxplot-footer td").css("padding-bottom", "10px");

  // Add the caption.
  var boxplot_caption = "This figure shows the gene expression of " + gene_display;
  if (gene_details != "")
    boxplot_caption += " (" + gene_details + ")";
  boxplot_caption += ", from samples grouped by their alleles at the SNP " + json.snp_name + ".";
  $("#boxplot-caption").html(boxplot_caption);

  // Determine plot size and margins.
  var plot_width = 120;
  var plot_height = compute_boxplot_height();
  var margin = {top: 10, right: 50, bottom: 20, left: 50};
  var width = plot_width - margin.left - margin.right;
  var height = plot_height - margin.top - margin.bottom;

  // Explicitly resize each boxplot's SVG element, if it exists. The d3 call
  // below will only set the size of the SVG element when it is created, but
  // this size could change if the window is resized or if the caption changes
  // height.
  $("#boxplot-wrapper svg").height(plot_height);

  // Setup a d3 function to draw each boxplot.
  var chart = d3.box()
      .whiskers(iqr(1.5))
      .width(width)
      .height(height);
  chart.domain([min, max]);

  // Produce the boxplots, adding HTML/SVG elements if necessary.
  var svg = d3.select("#boxplot-wrapper").selectAll("svg")
      .data(data);
  svg.enter().append("svg")
      .attr("class", "boxplot")
      .attr("width", plot_width)
      .attr("height", plot_height)
    .append("g")
      .attr("transform", "translate(" + margin.left + "," + margin.top + ")")
      .call(chart)
  svg.select("g").call(chart);
}

// Resizes the boxplots by fetching the cached JSON data from the last request
// (if any) and completely redrawing everything.
function resize_boxplot() {
  var json = nf_globals.boxplot_json;
  if (json)
    draw_boxplot(json);
}

// Loads the genotype and expression data for the given SNP and gene, and when
// it is available, draw the corresponding boxplots.
function boxplot(snp_id, gene_id) {
  $.ajax({
    "url": "boxplot_feed.php",
    "data": {
      "snp_id": snp_id,
      "gene_id": gene_id
    },
    "dataType": "json"
  }).success(function(json) {
    nf_globals.boxplot_json = json; // cache data in case we need to resize
    draw_boxplot(json);
  });
}

//////////
// Main //
//////////

var nf_globals = {
  layout: null,
  data_table: null,
  boxplot_json: null
};

// When the DOM is ready, initialize everything. It is important here that the
// table is initialized after the layout.
$(document).ready(function() {
  init_layout();
  init_table();
  init_form();
});
</script>
