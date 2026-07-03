<?php
$image_file = $_GET['path'];
header ('Content-length: ' .filesize($image_file));
header ('Content-type: image/jpeg');
@readfile ($image_file);
@unlink($image_file);
?>