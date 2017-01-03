<?php
require_once('loadclasses.php');
$page = new Page('Fleet-Yo!');

$html = '<div class="tt-pilot form">
           <input type="text" class="typeahead form-control">
           <input id="inv-id" type="hidden" values="">
           <button type="button" id="inv-button" class="tt-btn btn btn-primary disabled"><span class="glyphicon glyphicon-envelope"></span></button>
         </div>';
$html2 = '<script src="js/typeahead.bundle.min.js"></script>
         <script src="js/esi_autocomplete.js"></script>
         <script src="js/bootstrap-dialog.min.js"></script>
         <link href="css/bootstrap-dialog.min.css" rel="stylesheet">
         <script>
         $(document).ready(function() {
           $( "#inv-button" ).click(function() {
             var char_id = $("#inv-id").val();
             BootstrapDialog.show({message: char_id});
           });
         });
         </script>';
$page->addHeader('<link href="css/typeaheadjs.css" rel="stylesheet">');
$page->addBody($html);
$page->addFooter($html2);
$page->display();
exit;
?>
