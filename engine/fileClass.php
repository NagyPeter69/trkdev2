<?php

class file {
	public $filetype;
	public $colorprofile;
	public $uploadFolder = TRKPATH."/uploads/uploadPack";
	public $switchFolder = TRKPATH."/uploads/switchCompleted";
	public $idfile = "counter";
	public $updir = TRKPATH."/uploads";
	public $exp = "14";
	public $user;
	public $settings;
	public $filetype_array;
	
	public function switchFinishedUpload( $post ) {
		$id = $post["id"];
		$jobcode = $post["jobcode"];
		
		$job = sql_aget( "magazines", "code='".$jobcode."'", "*" );
		if( !empty( $job[0]["id"] ) ) {
			$type = "pub";
			$jobname = $job[0]["code"];
			
			
			$xml = simplexml_load_file( TRKPATH.'/xml/pmd.xml' );
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $job[0]["code"] ) {
						break;
						}
					}
				}
				
			$mails = (string) $xml->Item[$x]->Mails;
			$maddress = explode( ";", $mails );
			}
		else {
			$job = sql_aget( "jobs", "code='".$jobcode."'", "*" );
			$type = "job";
			$jobname = $job[0]["name"];
			
			
			$maddress = explode( "<br />", $job[0]["emails"] );
			}
		
		$path = "uploads/switchUpload/".$id;
		
		$names = array( "jobid", "path", "time", "expire", "type", "name" );
		$values = array( $job[0]["id"], $path, time(), strtotime( "+".$this->exp." days" ), $type, $post["name"] );
		$id = sql_add( "filetransfer", $names, $values );
		
		$files = load_dir_files( TRKPATH."/".$path, "" );
		for( $i = 0; $i < count( $files ); $i++ ) {
			if( strpos( $files[$i], "THUMB_" ) === false ) {
				$imagick = new Imagick( TRKPATH."/".$path."/".$files[$i] );
				$imagick->resizeImage( 400, 400, Imagick::FILTER_LANCZOS, "1", "1");
				$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
				$imagick->setImageCompressionQuality( 90 );
				$imagick->stripImage();
				$imagick->writeImage( TRKPATH."/".$path."/THUMB_".substr( $files[$i], 0, -4).".jpg" );
				}
			}
			
		$subject = $post["name"]." ::: feldolgozott képek";
		
		$body = "Tisztelt Ügyfelünk!<br>
		<br>
		A ".$jobname." munkához feldolgozott képeinek letöltéséhez kattintson az alábbi linkre:<br>
		<br>
		<a href='http://tracker.colorcom.hu/client/filetransfer_view.php?transferid=".$id."'>http://tracker.colorcom.hu/client/filetransfer_view.php?transferid=".$id."</a><br>
		<br>
		Fenti link 7 napig él, utána az anyagok törlésre kerülnek.<br>
		<br>
		<br>
		Üdvözlettel:<br>
		Colorcom Media";

/*
Letölthető egyben <a href='http://tracker.colorcom.hu/client/filetransfer_view.php?transferid=".$id."&op=mass'>IDE</a> kattintva,<br>
vagy<br>
Kijelölve <a href='http://tracker.colorcom.hu/client/filetransfer_view.php?transferid=".$id."'>IDE</a> kattintva.	
*/
		
		for( $i = 0; $i < count( $maddress ); $i++ ) {
			$to = trim( $maddress[$i] )."|".trim( $maddress[$i] );
			wfSendMail( $subject, $body, $to, "" );
			}
		}
	
	public function cancelledID( $id ) {
		delTree( $this->updir."/switchUpload/".$id );
		}
	
	public function createUploadDir( $name ) {
		$oldmask = umask(0);
		mkdir( $this->updir."/switchUpload/".$name, 0777);
		umask($oldmask);
		}
	
	public function getNextID() {
		$c = file_get_contents( $this->updir."/".$this->idfile );
		file_put_contents( $this->updir."/".$this->idfile, $c+1 );
		$this->createUploadDir( $c );
		
		return $c;
		}
	
	public function getSelectList( $var, $name ) {
		if( $var == "parts") {
			// error_log( print_r( $this->{$var."_array"}, true ) );
			}
			
		$html = "<select name='".$name."' id='".$name."'>";
		
		foreach( $this->{$var."_array"} as $key => $value ) {
			$html .= "<option value='".$key."' ".( ( $this->settings["upload"][$name] == $key ) ? "selected" : "" ).">";
			if( gettype( $value ) == "array" ) {
				$html .= $value["name"];
				}
			else {
				$html .= $value;
				};
			$html .= "</option>";
			}
		
		$html .= "</select>";
		return $html;
		}
	
	/*
	public $parts_array = array(
		"Cover" => "Borító",
		"Content" => "Belív",
		"Melléklet" => "Melléklet",
		);
	*/

	public $colorprofile_array = array(
		"FOGRA_39" => "FOGRA 39",
		"FOGRA_45" => "FOGRA 45",
		"FOGRA_46" => "FOGRA 46",
		"FOGRA_47" => "FOGRA 47",
		"FOGRA_51" => "FOGRA 51",
		"FOGRA_52" => "FOGRA 52",
		);

	function __construct( $parts, $uid ) {
		// colorprofile_array above is a property default, which PHP
		// requires to be a constant expression - can't query the DB
		// there, so it's swapped in here instead once the object exists.
		$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "name" );
		if( count( $standards ) > 0 ) {
			$this->colorprofile_array = array();
			for( $s = 0; $s < count( $standards ); $s++ ) {
				$this->colorprofile_array[ $standards[$s][0] ] = str_replace( "_", " ", $standards[$s][0] );
				}
			}

		$user = sql_aget( "accounts", "id='".$uid."'", "*" );
		$this->user = $user[0];
		$this->settings["upload"] = json_decode( $this->user["uploadsettings"], true );

		if( !empty( $user[0]["lang"] ) ) {	
			include( TRKPATH.'/lang/'.$user[0]["lang"].'.php' );
			}
		else {
			include( TRKPATH.'/lang/en.php' );
			}
		
		$p = array();
		for( $i = 0; $i < count( $parts ); $i++ ) {
			$part = $parts[$i]["name"];
			$temp = array_search( $part, PARTS );
			if( $temp !== false ) {
				if( empty( $p[ $temp ] ) ) {
					$p[] = $temp;
					}
				}
			}			
		
		foreach( $lang["parts"] as $key => $value ) {
			$find = array_search( $key, $p );
			if( $find !== false ) {
				$this->parts_array[$key] = $value;
				}
			}
		
		$this->filetype_array = array(
			"indesign_pack" => array(
				"name" => $lang["filetype"]["inddpack"],
				"ext" => array( "zip" ),
				),
				
			"indesign_file" => array(
				"name" => $lang["filetype"]["inddfile"],
				"ext" => array( "indd" ),
				),
				
			"pdf" => array(
				"name" => $lang["filetype"]["pdf"],
				"ext" => array( "pdf" ),
				),

			"pdf_to_flatplan" => array(
				"name" => $lang["filetype"]["pdf_to_flatplan"],
				"ext" => array( "pdf" ),
				),

			"picture" => array(
				"name" => $lang["filetype"]["pic"],
				"ext" => array( "tif", "tiff", "jpeg", "jpg", "psd", "png" ),
				),
			);

		$this->filetypefull_array = array(
			"indesign_pack" => array(
				"name" => $lang["filetype"]["inddpack"],
				"ext" => array( "zip" ),
				),
				
			"indesign_file" => array(
				"name" => $lang["filetype"]["inddfile"],
				"ext" => array( "indd" ),
				),
				
			"pdf" => array(
				"name" => $lang["filetype"]["pdf"],
				"ext" => array( "pdf" ),
				),

			"pdf_to_flatplan" => array(
				"name" => $lang["filetype"]["pdf_to_flatplan"],
				"ext" => array( "pdf" ),
				),

			"picture" => array(
				"name" => $lang["filetype"]["pic"],
				"ext" => array( "tif", "tiff", "jpeg", "jpg", "psd", "png" ),
				),

			"picture_pack" => array(
				"name" => $lang["filetype"]["pic_pack"],
				"ext" => array( "tif", "tiff", "jpeg", "jpg", "png", "psd" ),
				),
			);	
		}	
	}

?>