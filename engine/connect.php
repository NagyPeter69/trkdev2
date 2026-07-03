<?php

// PHP 8.1+ defaults mysqli to throwing mysqli_sql_exception on query errors.
// The whole sql_* wrapper layer in engine.php expects the pre-8.1 behavior
// of mysqli_query() returning false on error, so restore that here.
mysqli_report(MYSQLI_REPORT_OFF);

$con = mysqli_init();

$link = mysqli_real_connect ($con, 'localhost', 'trkapp', getenv('TRKDEV_DB_PASSWORD'), 'nyomadake_intra', 3306, NULL);
if (!$link)
{
    die ('Connect error (' . mysqli_connect_errno() . '): ' . mysqli_connect_error() . "\n");
}

mysqli_query( $con, "set names 'utf8'");
?>