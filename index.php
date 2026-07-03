<?php

header("Location: client/index.php".( !empty($_GET["hash"]) ? "?hash=".$_GET["hash"] : "" )."");

?>