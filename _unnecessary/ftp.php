<?php
$start = microtime(true);

header('Content-Type: text/html; charset=utf-8');

include('Net/SFTP.php');
include('engine/xml_handler.php');


$sftp = new Net_SFTP('93.189.113.97');
if (!$sftp->login('admin', 'awareness')) {
    exit('Login Failed');
}

$path = 'C_Database';

/*$xml_array = array();

$job_info = array();
$job_info[] = array( 'Próba hirdetés', '2013-11-08T12:00:00', '1/2 álló, tükör: 83 x 237 mm' );
$job_info[] = array( 'Próba hirdetés2', '2013-11-18T13:11:45', '1/3 álló, tükör: 55 x 237 mm' );
$job_info[] = array( 'Próba hirdetés3', '2013-11-24T15:00:00', '1/2 álló, vágott: 98 x 272 mm' );
$job_info[] = array( 'Próba hirdetés4', '2013-11-01T17:40:05', '2/3 álló, vágott: 134 x 272 mm' );
$job_info[] = array( 'Próba hirdetés5', '2013-11-32T11:23:10', '1/3 fekvő, tükör: 170 x 81 mm' );

for( $i = 0; $i < count( $job_info ); $i++ ) {
	$xml_array["Ad|".$i]["name"] = $job_info[$i][0];
	$xml_array["Ad|".$i]["adSize"] = $job_info[$i][2];
	$xml_array["Ad|".$i]["created"] = $job_info[$i][1];
	}

$myxml = array_to_xml( $xml_array, 'Advertisement' );*/

/*$xml_array = array();

$pictures = array();

$pictures[] = array( 'Adventi naptar-NAP.tif', '4', '2013.11.14. 16:18' );
$pictures[] = array( 'avokados rakkoktel02sgy.tif', '12', '2013.11.14. 18:30' );
$pictures[] = array( 'konyakos sparga kremleves01sgy', '12', '2013.11.14. 18:30' );
$pictures[] = array( '1Q2A1986.tif', '44', '2013.11.14. 19:06' );
$pictures[] = array( 'black and white desszert (2).tif', '44', '2013.11.14. 19:06' );
$pictures[] = array( 'Izajanlo kolbaszos sutotokos retes02sgy.tif', '58', '2013.11.18. 17:38' );
$pictures[] = array( 'sajtos brigzskeksz01sgy.tif', '98', '2013.11.18. 17:41' );

for( $i = 0; $i < count( $pictures ); $i++ ) {
	$xml_array["Picture|".$i]["name"] = $pictures[$i][0];
	$xml_array["Picture|".$i]["page"] = $pictures[$i][1];
	$xml_array["Picture|".$i]["processed"] = $pictures[$i][2];
	}

$myxml = array_to_xml( $xml_array, 'Pictures' );*/

/*header ("Content-Type:text/xml");
echo $myxml;*/

/*$xml_array = array();
$pre = array();

$pre[] = array( 'PBManOfYear_01', '1' );
$pre[] = array( 'PRberlitz1p2K_01', '2' );
$pre[] = array( 'PRcanon1p2K_01', '3' );
$pre[] = array( 'PRTeligumiAdvK_01', '4' );
$pre[] = array( 'OrbitPRK_01', '5' );
$pre[] = array( 'PRdeichmannK_01', '6' );
$pre[] = array( 'PRdeich2mannK_01', '7' );
$pre[] = array( 'MHappsK_01', '8' );

for( $i = 0; $i < count( $pre ); $i++ ) {
	$xml_array["Pre|".$i]["uncounted"] = $pre[$i][0];
	$xml_array["Pre|".$i]["located"] = $pre[$i][1];
	}

$myxml = array_to_xml( $xml_array, 'PreList' );

$dom = DOMDocument::loadXML($myxml);
$dom->formatOutput = true;

file_put_contents( 'pre_list.xml', $dom->saveXML() );
$sftp->put( $path.'/pre_list.xml', $dom->saveXML()  );

$end = microtime(true);
echo 'Lefutott: '.number_format($end - $start, 3).' másodperc alatt';*/



$xml=simplexml_load_file("Publications_Master_Data.xml");

$result = $xml->xpath("Names");
$i = 1;
$mCodes = array();

foreach($result[0] as $child) {
	$child->getName();
	if( fmod( $i , 2 ) == 0 ) {
		$data = get_xml_datas( $xml, $child );
		
		magazine_XMLtoSQL( $data );
		}

	$i++;
	}

/*echo "<br><br><br>";

foreach( $mCodes as $key ) {
	echo $key . "<br>";
	}*/
/*
print '<pre>';
print_r($sftp->nlist('C_Database')); // == $sftp->rawlist('.')
print '</pre>';*/

?>