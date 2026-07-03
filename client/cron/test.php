<?php

	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( "switchAPI.php" );
	
	include_once('../lang/en.php');
	
	$pub = sql_aget( "publications", "id='2966'", "*" );
	$magazine = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
	$hird = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND status='2' AND fin='1' AND issue='".$pub[0]["code"]."' AND type='ad' GROUP BY page", "*" );
	
	$adProof = 0;
	$proof = 0;
	$coverProof = 0;
	$temp = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND issue='".$pub[0]["code"]."' AND proofCounter != '0'", "*" );
	for( $i = 0; $i < count( $temp ); $i++ ) {
		if( $temp[$i]["type"] == "ad" ) {
			$adProof += $temp[$i]["proofCounter"];
			}
		else {
			if( $temp[$i]["page"] == "1" ) {
				$coverProof += $temp[$i]["proofCounter"];
				}
			else {
				$proof += $temp[$i]["proofCounter"];
				}
			}
		}
	
	$adProofTemp = sql_aget( "pageinfo", "action='adProof' AND magazine='".$pub[0]["magazine_id"]."' AND issue='".$pub[0]["code"]."' ", "*" );
	$adProof += count( $adProofTemp );
	
	$txt = "";
	$csv = "";
	
	$txt .= "Név: ".$magazine[0]["name"]."<br>";
	$csv .= "Név:;".$magazine[0]["name"]."\n";
	
	$txt .= "Megjelenés: ".$magazine[0]["code"]."_".$pub[0]["code"]."<br>";
	$csv .= "Megjelenés:;".$magazine[0]["code"]."_".$pub[0]["code"]."\n";
	
	$txt .= "Lezárva: ".date( "Y-m-d\TH:i:s" , time() )."<br>";
	$csv .= "Lezárva:;".date( "Y-m-d\TH:i:s" , time() )."\n";

	$txt .= "Terjedelem: ".$pub[0]["pages"]."<br>";
	$csv .= "Terjedelem:;".$pub[0]["pages"]."\n";
	
	$txt .= "Hirdetési oldalak: ".count( $hird )."<br>";
	$csv .= "Hirdetési oldalak:;".count( $hird )."\n";	

	$txt .= "Szerkesztőségi oldalak: ".( $pub[0]["pages"] - count( $hird ) )."<br>";
	$csv .= "Szerkesztőségi oldalak:;".( $pub[0]["pages"] - count( $hird ) )."\n";	

	$txt .= "Hirdetés proof: ".$adProof."<br>";
	$csv .= "Hirdetés proof:;".$adProof."\n";	

	$txt .= "Szerkesztőségi proof: ".$proof."<br>";
	$csv .= "Szerkesztőségi proof:;".$proof."\n";
	
	$txt .= "Borító proof: ".$coverProof."<br>";
	$csv .= "Borító proof:;".$coverProof."\n";	

	$txt .= "Retusált képek:<br>";
	$csv .= "Retusált képek\n";	
	
	invoicingTESZT( 2966 );
	
	echo $txt;
?>