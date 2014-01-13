<!DOCTYPE html>
<script src='http://www.biostat.wisc.edu/~nathanae/DataTables/DataTables-1.9.4/media/js/jquery.js'></script>
<script src='http://www.biostat.wisc.edu/~nathanae/DataTables/DataTables-1.9.4/media/js/jquery.dataTables.js'></script>
<script src='http://www.biostat.wisc.edu/~nathanae/DataTables/DataTables-1.9.4/extras/Scroller/media/js/dataTables.scroller.min.js'></script>
<link rel="stylesheet" type="text/css" href="http://www.biostat.wisc.edu/~nathanae/DataTables/DataTables-1.9.4/media/css/jquery.dataTables.css"></link>
<link rel="stylesheet" type="text/css" href="http://www.biostat.wisc.edu/~nathanae/DataTables/DataTables-1.9.4/extras/Scroller/media/css/dataTables.scroller.css"></link>

<!-- <script src='http://www.biostat.wisc.edu/~nathanae/jquery/jquery-ui-1.10.3/jquery-1.9.1.js'></script> -->
<script src='http://www.biostat.wisc.edu/~nathanae/jquery/jquery-ui-1.10.3/ui/minified/jquery-ui.min.js'></script>
<script src='http://www.biostat.wisc.edu/~nathanae/jquery/jquery.layout-latest.js'></script>
<link rel='stylesheet' type='text/css' href='http://www.biostat.wisc.edu/~nathanae/jquery/layout-default-latest.css' />
<link rel='stylesheet' type='text/css' href='http://www.biostat.wisc.edu/~nathanae/jquery/jquery-ui-1.10.3/themes/base/minified/jquery-ui.min.css' />

<script type="text/x-mathjax-config">
MathJax.Hub.Config({
  tex2jax: {inlineMath: [['$','$']]}
});
</script>
<script type="text/javascript"
  src="http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_SVG">
</script>

<style>
body, td { font-family: sans-serif; font-size: 9pt; }
</style>

<div class='ui-layout-north'>
<table cellpadding=10>
  <tr>
    <td>gene_name: <input type="text" id="gene_name_input"></td>
      
      T (num types):<br>
      <select size=6 id='T'>
      <option selected>3</option>
      <option>4</option>
      <option>5</option>
      <option>6</option>
      </select>
    </td>
    <td>
      Constrain slope?<br>
      <select size=6 id='cons_slope'>
      <option selected>no</option>
      <option>yes</option>
      </select>
    </td>
    <td>
      Require nested bbT?<br>
      <select size=6 id='nested_bbT'>
      <option selected>no</option>
      <option>yes</option>
      </select>
    </td>
    <td>
      Database:<br>
      <select size=6 id='godb'>
      <option>KEGG</option>
      <option selected>GO</option>
      </select>
    </td>
    <td>
      Method:<br>
      <select size=6 id='method'>
      <option>type_vs_type</option>
      <option selected>type_vs_stage</option>
      </select>
    </td>
    <td>
      Score:<br>
      <select size=6 id='score'>
      </select>
    </td>
    <td class="type_vs_type_only">
      t1:<br>
      <select size=6 id='t1'>
      </select>
    </td>
    <td class="type_vs_type_only">
      t2:<br>
      <select size=6 id='t2'>
      </select>
    </td>
    <td class="type_vs_stage_only">
      k:<br>
      <select size=6 id='k'>
      <option selected>all</option>
      <option>1</option>
      <option>2</option>
      <option>3</option>
      <option>4</option>
      </select>
    </td>
    <td class="type_vs_stage_only">
      t:<br>
      <select size=6 id='t'>
      </select>
    </td>
  </tr>
</table>
<div><a id='show_definitions' href='javascript:void(0)'>Show definitions</a></div>
<div id='definitions' title='Definitions'>
<b>Constrain slope?</b>
<ul>
<li><b>no</b> - no constraint</li>
<li><b>yes</b> - impose the constraint that the proportion increases or decreases across stages, for each type</li>
</ul>

<b>Require nested bbT?</b>
<ul>
<li><b>no</b> - all differential expression patterns across types are considered</li>
<li><b>yes</b> - only the nested differential expression patterns across types are considered: {{1,2,3}}, {{1,2},{3}}, {{1},{2},{3}} for T=3</li>
</ul>

<b>Method:</b>
<ul>
<li><b>type_vs_type</b> - contrast typewise means against each other</li>
<li><b>type_vs_stage</b> - contrast each typewise mean against each stagewise mean (or the overall mean if $k=$ all)</li>
</ul>

<b>Score:</b>
<ul>
<li><b>prob_de</b> - For each gene $g$:
  <ul>
  <li>Let $\mathcal J$ be the subset of $[J]$ such that $t_1$ and $t_2$ are in different elements of $\mathbb T_j$ for all $j\in\mathcal J$.</li>
  <li>Let $\text{prob_de}(g) = \sum_{j \in \mathcal J} \mathbb P(Z_g = j | S_g = s_g)$.</li>
  </ul>
  If $\text{prob_de}(g) < 0.9$, we set it to zero before sending it to allez.
</li>

<li><b>diffs_between_means</b> - For each gene $g$:
  <ul>
  <li>Let $\mathcal J$ be the subset of $[J]$ such that $t_1$ and $t_2$ are in different elements of $\mathbb T_j$ for all $j\in\mathcal J$.</li>
  <li>Let $\mathscr T(t,j)$ be the element of $\mathbb T_j$ such that $t\in\mathscr T$.</li>
  <li>Let $\mu_{g,t} = \mathbb E(M_{g,\mathscr T(t,Z_g)} | S_g=s_g,Z_g\in\mathcal J)
                     = \sum_{j\in\mathcal J} \mathbb P(Z_g=j|Z_g\in\mathcal J,S_g=s_g) \mathbb E(M_{g,\mathscr T(t,j)}|Z_g=j,S_g=s_g)$.</li>
  <li>Let $\text{diffs_between_means}(g) = \mu_{g,t_1} - \mu_{g,t_2}$.</li>
  </ul>
  If $\text{prob_de}(g) < 0.9$, we set $\text{diffs_between_means}(g) = 0$ before sending it to allez.
</li>

<li><b>dists_between_means</b> - For each gene $g$:
  <ul>
  <li>Let $\text{diffs_between_means}(g) = |\text{dists_between_means}(g)|$, the absolute value.</li>
  </ul>
  If $\text{prob_de}(g) < 0.9$, we set $\text{dists_between_means}(g) = 0$ before sending it to allez.
</li>

<li><b>typewise_minus_stagewise</b> - For each gene $g$:
  <ul>
  <li>Let $\mathscr T(t,j)$ be the element of $\mathbb T_j$ such that $t\in\mathscr T$.</li>
  <li>Let $\text{typewise}(g) = \mathbb E(M_{g,\mathscr T(t,Z_g)} | S_g = s_g) = \sum_{j=1}^J \mathbb P(Z_g = j | S_g = s_g) \mathbb E(M_{g,\mathscr T(t,j)} | Z_g = j, S_g = s_g)$.</li>
  <li>Let $\text{stagewise}(g) = \frac{1}{n} \sum_{i=1}^n s_{g,i}$ if $k =$ all, and $\frac{1}{n_k} \sum_{i : k_i=k} s_{g,i}$ otherwise.</li>
  <li>Let $\text{typewise_minus_stagewise}(g) = \text{typewise}(g) - \text{stagewise}(g)$.</li>
  </ul>
</li>

<li><b>stagewise_minus_typewise</b> - For each gene $g$:
  <ul>
  <li>Let $\text{stagewise_minus_typewise}(g) = \text{stagewise}(g) - \text{typewise}(g)$.</li>
  </ul>
</li>

</ul>
</div>
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
    <th>X3014</th> <th>B708</th> <th>X3111</th> <th>B290</th> <th>B895</th> <th>X3593</th> <th>B900</th> <th>X4005</th> <th>B402</th> <th>B996</th> <th>X4114</th> <th>A492</th> <th>B420</th> <th>X4279</th> <th>A513</th> <th>B482</th> <th>X5471</th> <th>A602</th> <th>B499</th> <th>X5509</th> <th>A707</th> <th>B562</th> <th>X5716</th> <th>A856</th> <th>B580</th> <th>X5800</th> <th>A857</th> <th>X5837</th> <th>A932</th> <th>X5899</th> <th>B101</th> <th>B625</th> <th>N10</th> <th>N11</th> <th>N18</th> <th>N19</th> <th>N21</th> <th>N25</th> <th>N27</th> <th>N28</th> <th>N29</th> <th>N31</th> <th>N32</th> <th>N34</th> <th>N39</th> <th>N40</th> <th>N41</th> <th>N44</th> <th>N45</th> <th>N47</th> <th>N50</th> <th>N52</th> <th>N54</th> <th>N57</th> <th>N78</th> <th>N80</th> <th>N81</th> <th>N84</th> <th>N88</th> <th>N95</th> <th>N97</th> <th>N104</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>
</div>
</div>

<div class='ui-layout-east'>
<img id='p_plot'  src="T_3_cons_slope_0_nested_bbT_1_p.svg"  width='350'>
<br>
<img id='pi_plot' src="T_3_cons_slope_0_nested_bbT_1_pi.svg" width='350'>
</div>

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

  function onchange_T() {
    var T   = parseInt($('#T :selected').text());

    // Add t options.
    var html = '';
    for (var t = 1; t <= T; ++t)
      html += "<option>" + t + "</option>";
    $('#t').html(html);
    $('#t1').html(html);
    $('#t2').html(html);
    $('#t').find("option").first().attr('selected', true);
    $('#t1').find("option").first().attr('selected', true);
    $('#t2').find("option").eq(1).attr('selected', true);

    // Enable or disable cons_slope options.
    if (T > 3) {
      $('#cons_slope').val('no').attr('selected', true);
      $('#cons_slope').attr('disabled', 'disabled');
    } else {
      $('#cons_slope').removeAttr('disabled');
    }

    // Enable or disable nested_bbT options.
    if (T > 4) {
      $('#nested_bbT').val('yes').attr('selected', true);
      $('#nested_bbT').attr('disabled', 'disabled');
    } else {
      $('#nested_bbT').removeAttr('disabled');
    }
  }

  function onchange_method() {
    var method = $('#method     :selected').text();
    if (method == "type_vs_type") {
      $(".type_vs_type_only").show();
      $(".type_vs_stage_only").hide();
      $("#score").html(
        "<option selected>prob_de</option>" +
        "<option>diffs_between_means</option>" +
        "<option>dists_between_means</option>");
    } else {
      $(".type_vs_type_only").hide();
      $(".type_vs_stage_only").show();
      $("#score").html(
        "<option selected>typewise_minus_stagewise</option>" +
        "<option>stagewise_minus_typewise</option>");
    }
  }

  function show_pdf(fname) {
    $('#error_message').hide();
    //$('#pdf').show();
    //$('#pdf')[0].src = fname;
  }

  function show_definitions(layout) {
    $('#definitions').dialog({'width': 0.75*layout.north.state.innerWidth});
  }

  function update() {
    var method     = $('#method     :selected').text();
    if (method == "type_vs_type")
      update_type_vs_type();
    else
      update_type_vs_stage();
    update_plots();
  }

  function update_type_vs_type() {
    var T          = $('#T          :selected').text();
    var cons_slope = $('#cons_slope :selected').text();
    var nested_bbT = $('#nested_bbT :selected').text();
    var godb       = $('#godb       :selected').text();
    var method     = $('#method     :selected').text();
    var score      = $('#score      :selected').text();
    var t1         = $('#t1         :selected').text();
    var t2         = $('#t2         :selected').text();
    if (t1 == t2) {
      $('#pdf').hide();
      $('#error_message').html(
        "<p>Contrast " + t1 + " and " + t2 + ", based on no patterns.</p>" +
        "<p>No results, because there is no contrast.</p>");
      $('#error_message').show();
    } else {
      var fname = [
        "T",          T,
        "cons_slope", cons_slope == "no" ? "0" : "1",
        "nested_bbT", nested_bbT == "no" ? "0" : "1",
        "godb",       godb,
        "method",     method,
        "score",      score,
        "t1",         t1,
        "t2",         t2
        ].join("_") + ".html"
      show_pdf(fname);
    }
  }

  function update_type_vs_stage() {
    var T          = $('#T          :selected').text();
    var cons_slope = $('#cons_slope :selected').text();
    var nested_bbT = $('#nested_bbT :selected').text();
    var godb       = $('#godb       :selected').text();
    var method     = $('#method     :selected').text();
    var score      = $('#score      :selected').text();
    var t          = $('#t          :selected').text();
    var k          = $('#k          :selected').text();
    var fname = [
      "T",          T,
      "cons_slope", cons_slope == "no" ? "0" : "1",
      "nested_bbT", nested_bbT == "no" ? "0" : "1",
      "godb",       godb,
      "method",     method,
      "score",      score,
      "k",          k,
      "t",          t
      ].join("_") + ".html"
    show_pdf(fname);
  }

  function update_plots() {
    var T          = $('#T          :selected').text();
    var cons_slope = $('#cons_slope :selected').text();
    var nested_bbT = $('#nested_bbT :selected').text();
    var prefix = [
      "T", T,
      "cons_slope", cons_slope == "no" ? "0" : "1",
      "nested_bbT", nested_bbT == "no" ? "0" : "1"
    ].join("_");
    $('#p_plot' )[0].src = prefix + "_p.svg";
    $('#pi_plot')[0].src = prefix + "_pi.svg";
  }

  onchange_T();
  onchange_method()
  update();

  $('#definitions').hide();
  $('#error_message').hide();

  $('#T')     .change(function() { onchange_T();      update(); });
  $('#method').change(function() { onchange_method(); update(); });
  $('select') .change(update);

  var layout = init_layout();
  $('#show_definitions').click(function() { show_definitions(layout); });

  $("#table-wrapper table").dataTable({
    //"bPaginate": false,
    //"aaSorting": [[0, "desc"]],
    // Server side source:
    "bProcessing": true,
    "bServerSide": true,
    "sAjaxSource": "json.php",
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
