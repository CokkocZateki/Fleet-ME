<?php
$files = glob('classes/*.php');

foreach ($files as $file) {
    require_once($file);   
}
?>
