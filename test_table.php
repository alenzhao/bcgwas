<!DOCTYPE html>
<script src='http://www.biostat.wisc.edu/~nathanae/DataTables/DataTables-1.9.4/media/js/jquery.js'></script>
<script src='http://www.biostat.wisc.edu/~nathanae/DataTables/DataTables-1.9.4/media/js/jquery.dataTables.js'></script>
<link rel="stylesheet" type="text/css" href="http://www.biostat.wisc.edu/~nathanae/DataTables/DataTables-1.9.4/media/css/jquery.dataTables.css"></link>
<style>
body, td { font-family: sans-serif; font-size: 9pt; }
</style>
<p></p>
<div id="table-wrapper">
<table border="0" cellspacing="0" cellpadding="0">
  <thead>
    <tr>
      <th>gene_name</th>
      <th>gene_symbol</th>
      <th>chromosome</th>
      <th>start</th>
      <th>stop</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>
</div>
<script>
$(document).ready(function() {
  $("#table-wrapper table").dataTable({
    //"bPaginate": false,
    //"aaSorting": [[0, "desc"]],
    "bProcessing": true,
    "bServerSide": true,
    "sAjaxSource": "json.php"
  });
});
</script>
