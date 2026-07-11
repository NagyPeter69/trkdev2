<?

if( isset( $_GET['opt'] ) && $_GET['opt'] == 'del' ) {
	$_POST['old_code'] = $_GET['code'];
	changeXmlDatabase( 'delete', $_POST );
	unset( $_GET['code'] );
	$xml = simplexml_load_file( 'client/xml/pmd.xml' );
	}

if( isset( $_POST['xmlnew_go'] ) ) {
	changeXmlDatabase( 'add', $_POST );
	$xml = simplexml_load_file( 'client/xml/pmd.xml' );
	
	echo "<br>Az kiadvány sikeresen elmentve<br><br>";
	}

if( isset( $_POST['xml_go'] ) ) {
	$_POST['old_code'] = $_GET['code'];	
	changeXmlDatabase( 'modify', $_POST );
	$xml = simplexml_load_file( 'client/xml/pmd.xml' );
	
	echo "<br>Az adatok sikeresen elmentve<br><br>";
	}
?>
<div>
	
	Kiadvány törlése:
	<select id='pmd_del' name='pmd_del'>
	<?
		$xml = simplexml_load_file( 'client/xml/pmd.xml' );
		
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $i = 0; $i < count( $temp->Item ); $i++ ) {
				echo "<option value='".$temp->Item[$i]->Code."'>".$temp->Item[$i]->Name."</option>";
				}
			}
	?>
	</select><button onclick="redirect()">Törlés</button><br>

	Kiadvány választás:
	<select id='pmd_pub' name='pmd_pub'>
	<?
		$xml = simplexml_load_file( 'client/xml/pmd.xml' );
		
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $i = 0; $i < count( $temp->Item ); $i++ ) {
				echo "<option ";
				if( $temp->Item[$i]->Code == $_GET['code'] ) echo "selected ";
				echo "value='".$temp->Item[$i]->Code."'>".$temp->Item[$i]->Name."</option>";
				}
			if( !isset( $_GET['code'] ) ) {
				$_GET['code'] = $temp->Item[0]->Code;
				}
			}
	?>
	</select>  <a href='?page=pmd&opt=newpub'>Új kiadvány felvitele</a>
	<br><br>
</div>

<?

if( isset( $_GET['code'] ) && $_GET['code'] != '' ) {
	if( isset( $_GET['opt'] ) && $_GET['opt'] == 'newpub' ) {
		
		echo "<form method='post' id='new_data' name='new_data' action='?page=pmd'><table width='100%' cellspacing='0' cellpadding='0'>";
		
		$array = array(
					"Name" => '',
					"Code" => '',
					"Publisher" => '',
					"Language" => 'HU',
					"Period" => 'undefined',
					"Current" => '',
					"AdAutoProof" => 'No',
					"Workflow" => 'Enhance',
					"Resolution" => '300',
					"IDversion" => 'CS4',
					"Enhance" => 'Vivid',
					"ColorManagement" => array( 
						"Cover" => 'FOGRA_39',
						"Content" => 'FOGRA_39',
						"Insert" => 'FOGRA_39'
						),
					"PDFstandard" => 'PDFX1',
					"OutputFormat" => 'TIFF',
					"CustomCode" => 'No',
					"ImageRename" => 'No',
					"TrimSize" => 'undefined x undefined mm',
					"LocalStorage" => 'PubFolder',
					"RemoteStorage" => '',
					"Parent" => 'Self',
					"FinalOutput" => '',
					"ArchiveMode" => 'CMYK',
					"Mails" => '',
					"MailComm" => 'Yes',
					"AdSizes" => array(
						"fullpage" => array(
							"value" => ''
							),
						"partial" => array(
							"value" => ''
							)
						)
					);
		
		$array = array_to_xml2( $array, 'teszt' );
		$array = get_xml_datas( $array, '//teszt' );
		

		
		foreach( $array as $key => $value ) {
			$key = explode( "_", $key );
			$key = $key[0];
		
			if( $key != 'value' ) {
				echo "<tr>";
			
				$temp = '';
				switch( $key ){
					case 'Parent':
						$temp = array( 'Self', $array['Name_0'] );
						break;	
					case 'LocalStorage':
						$temp = array( 'Root', 'PubFolder', 'Parent' );
						break;				
					case 'IDversion':
						$temp = array( 'CS3', 'CS4', 'CS5','CS55' ,'CS6' );
						break;
					case 'Language':
						$temp = array( 'HU' );
						break;				
					case 'OutputFormat':
						$temp = array( 'TIFF', 'JPEG', 'Original' );
						break;
					case 'ArchiveMode':
						$temp = array( 'RGB', 'CMYK' );
						break;
					case 'TrimSize':
						$temp = explode( " ", $value );
						break;
					case 'PDFstandard':
						$temp = array( 'PDFX1', 'PDFX4' );
						break;
					case 'ImageRename':
					case 'AdAutoProof':
					case 'MailComm':
						$temp = array( 'Yes', 'No' );
						break;
					case 'Enhance':
						$temp = array( 'Skintone', 'Food', 'JeWellery', 'Vivid' );
						break;
					case 'Workflow':
						$temp = array( 'Full', 'Hybrid', 'Repack', 'Resize', 'Enhance' );
						break;
					case 'Period':
						$temp = array( '1', '2', '3', 'undefined' );
						break;
					case 'Cover':
					case 'Content':
					case 'Insert':
						$temp = array();
						$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "name" );
						for( $s = 0; $s < count( $standards ); $s++ ) {
							$temp[] = $standards[$s][0];
							}
						break;
					}
			
				echo "<td class='xml_name'>".$key."</td>";
				if( $temp == '' ) {
					echo "<td class='xml_value'><input type='text' name='".$key."' value='".$value."'></td>";
					}
				elseif( $key == 'TrimSize' ) {
					echo "<td class='xml_value'><input type='text' name='".$key."_x' value='".$temp[0]."'> x <input type='text' name='".$key."_y' value='".$temp[2]."'> mm";
					}
				else {
					echo "<td class='xml_value'><select name='".$key."'>";
					foreach( $temp as $t ) {
						echo "<option ";
						if( $value == $t ) echo "selected ";
						echo "value='".$t."'>".$t."</option>";
						}
					echo "</select>";
					echo "</td>";	
					}
				echo "</tr>";
				}
			}
		echo "<tr><td colspan='2'><input type='submit' name='xmlnew_go' disabled value='Hozzáadás'></td></tr>";
		echo "</table></form>";

		}
	else {
		$array = get_xml_datas( $xml, '//Publications/Item[Code = "'.$_GET['code'].'"]' );

		echo "<form method='post' action='?page=pmd&code=".$_GET['code']."'><table width='100%' cellspacing='0' cellpadding='0'>";
	
		foreach( $array as $key => $value ) {
			$key = explode( "_", $key );
			$key = $key[0];
		
			if( $key != 'value' ) {
				echo "<tr>";
			
				$temp = '';
				switch( $key ){
					case 'Publisher':
						$pubs = sql_get( 'publishers', '1 ORDER BY `name` ASC ', '*' );
						for( $x = 0; $x < count( $pubs ); $x++ ) {
							$temp[] = $pubs[$x][1];
							}
						break;
					case 'Parent':
						$temp = array( 'Self', $array['Name_0'] );
						break;	
					case 'LocalStorage':
						$temp = array( 'Root', 'PubFolder', 'Parent' );
						break;				
					case 'IDversion':
						$temp = array( 'CS3', 'CS4', 'CS5','CS55' ,'CS6' );
						break;
					case 'Language':
						$temp = array( 'HU' );
						break;				
					case 'OutputFormat':
						$temp = array( 'TIFF', 'JPEG', 'Original' );
						break;
					case 'ArchiveMode':
						$temp = array( 'RGB', 'CMYK' );
						break;
					case 'TrimSize':
						$temp = explode( " ", $value );
						break;
					case 'PDFstandard':
						$temp = array( 'PDFX1', 'PDFX4' );
						break;
					case 'ImageRename':
					case 'AdAutoProof':
					case 'MailComm':
						$temp = array( 'Yes', 'No' );
						break;
					case 'Enhance':
						$temp = array( 'Skintone', 'Food', 'JeWellery', 'Vivid' );
						break;
					case 'Workflow':
						$temp = array( 'Full', 'Hybrid', 'Repack', 'Resize', 'Enhance' );
						break;
					case 'Period':
						$temp = array( '1', '2', '3', 'undefined' );
						break;
					case 'Cover':
					case 'Content':
					case 'Insert':
						$temp = array();
						$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "name" );
						for( $s = 0; $s < count( $standards ); $s++ ) {
							$temp[] = $standards[$s][0];
							}
						break;
					}
			
				echo "<td class='xml_name'>".$key."</td>";
				if( $temp == '' ) {
					echo "<td class='xml_value'><input type='text' name='".$key."' value='".$value."'></td>";
					}
				elseif( $key == 'TrimSize' ) {
					echo "<td class='xml_value'><input type='text' name='".$key."_x' value='".$temp[0]."'> x <input type='text' name='".$key."_y' value='".$temp[2]."'> mm";
					}
				else {
					echo "<td class='xml_value'><select name='".$key."'>";
					foreach( $temp as $t ) {
						echo "<option ";
						if( $value == $t ) echo "selected ";
						echo "value='".$t."'>".$t."</option>";
						}
					echo "</select>";
					echo "</td>";	
					}
				echo "</tr>";
				}
			}
		echo "<tr><td colspan='2'><input type='submit' name='xml_go' value='Mentés'></td></tr>";
		echo "</table></form>";
		}
	}
?>

<script>
$('select[name="Publisher"]').change(function(){
	var pub = $('select[name="Publisher"]').val();
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=loadFTP&pub='+pub+'&type=Content&name=RemoteStorage',
		dataType: 'json',
		success:function( data ) {
			$('[name="RemoteStorage"]').parent('.xml_value').html( data );
			}
		});
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=loadFTP&pub='+pub+'&type=Final&name=SoftProof',
		dataType: 'json',
		success:function( data ) {
			$('[name="SoftProof"]').parent('.xml_value').html( data );
			}
		});
	});
$('select[name="Publisher"]').change();

function redirect() {
	if( confirm('Biztosan véglegesen törlöd a kiválasztott kiadványt? ( A visszaállításra NINCS lehetőség )') ) {
		var url = "?page=pmd&opt=del&code="+$('#pmd_del').val();
		window.location.href = url;
		}
	}

$(':input').keyup(function() {
	console.log( $(':input[name="Name"]').val()+", "+$(':input[name="Code"]').val() );
	if( $(':input[name="Name"]').val() != '' && $(':input[name="Code"]').val() != '' && $(':input[name="Publisher"]').val() != '' ) {
		$(':input[name="xmlnew_go"]').removeAttr('disabled');
		}
	else {
		$(':input[name="xmlnew_go"]').attr('disabled', 'disabled');
		}
	});

$('#pmd_pub').change(function() {
	window.location.href = "?page=pmd&code="+$('#pmd_pub').val();
	});

</script>