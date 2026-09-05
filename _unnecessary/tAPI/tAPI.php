<?php

class tAPI {
	public $response = array(
		"error" => false,
		"message" => "",
		);
	public $user = array();
	public $perm = array();
	public $pub = array();
	public $post = "";
	
	public function removeUser() {
		$this->getPublisher();
		$check = sql_aget( "accounts", "name='".$this->post[ "remove" ]."' AND publisher='".$this->pub["id"]."'", "id" );
		if( !empty( $check[0]["id"] ) ) {
			
			sql_delete( "accounts", "id='".$check[0]["id"]."'" );
			sql_delete( "userLogSettings", "user='".$check[0]["id"]."'" );			

			$this->response["success"] = true;
			$this->response["message"] = "User successful removed";
			}
		else {
			$this->response["error"] = true;
			$this->response["message"] = "User not exist";			
			}
		}
	
	public function addUser() {
		$this->getPublisher();
		if( empty( $this->post["group"] ) ) {
			$this->post["group"] = 13;
			}

		$check = sql_aget( "accounts", "name='".$this->post[ "newusername" ]."' AND publisher='".$this->pub["id"]."'", "id" );
		if( empty( $check[0]["id"] ) ) {
			$showMagazines = "";
			$names = array( 'name', 'pass', 'publisher', 'group', 'email', 'full_name', 'showMagazines' );
			$values = array( $this->post['newusername'], password_hash($this->post['newuserpass'], PASSWORD_DEFAULT), $this->pub["id"], $this->post['group'], $this->post[ "email" ], $this->post[ "fullname" ], $showMagazines );
			$id = sql_add( 'accounts', $names, $values );

			$names = array( 'user' );
			$values = array( $id );
			sql_add( 'userLogSettings', $names, $values );

			// The password itself was set above from the caller's own newuserpass
			// (external API contract unchanged) - but the human recipient may not
			// know what the calling system chose, so the welcome mail carries a
			// secure set-password link instead of echoing it in clear text.
			$token = bin2hex( random_bytes( 32 ) );
			sql_update( 'accounts', "pwset_token='".hash( 'sha256', $token )."', pwset_expires='".( time() + 172800 )."'", "id='".$id."'" );
			$link = PROTOCOL.URL."/client/index.php?page=set_password&token=".$token;

			$subject = "Tracker belépési adatok";
			$to = $this->post[ "fullname" ]."|".$this->post[ "email" ];
			$body = "
Üdvözlünk a Colorcom Tracker felhasználói között!<br>
<br>
Az alábbi linkre kattintva tudsz jelszót beállítani a fiókodhoz:<br>
<br>
<a href='".$link."'>".$link."</a><br>
<br>
Login név: ".$this->post['newusername']."<br>
<br>
A link 48 óráig érvényes.<br>
<br>
Üdvözlettel:<br>
<br>
Colorcom Media<br>
			";
			produkcioSendmail( $subject, $body, $to );

			$this->response["success"] = true;
			$this->response["message"] = "User successful created";
			$this->response["userID"] = $id;
			}
		else {
			$this->response["error"] = true;
			$this->response["message"] = "User already exist";
			}
		}
		
	public function removeMagazine() {
		$this->getPublisher();	
		$check = sql_aget( "magazines", "code='".$this->post["code"]."' AND publisher_id='".$this->pub["id"]."'", "id" );
		if( !empty( $check[0]["id"] ) ) {
			changeXmlDatabase( 'delete', array( "old_code" => $this->post["code"] ), XMLPATH.'/pmd.xml' );
			
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'info' );
			$values = array( $this->user["id"], 'removeMagazine', $this->pub["id"], $check[0]["id"], '', $check[0]["code"], time(), 'API' );
			sql_add( 'system_log', $names, $values );				
			
			$this->response["success"] = true;
			}
		else {
			$this->response["error"] = true;
			$this->response["message"] = "Magazine not exist: ".$this->post["code"];			
			}
		}
	
	public function createMagazine() {
		$this->getPublisher();	
		$params = $this->setDefParams( "magDefParams" );
		//var_dump( $params );
		
		if( count( $params ) > 0 ) {
			$check = sql_aget( "magazines", "code='".$this->post["code"]."'", "id" );
			if( empty( $check[0]["id"] ) ) {
				$names = array( "publisher_id", "name", "code" );
				$values = array( $this->pub["id"], $this->post["name"], $this->post["code"] );				
				$id = sql_add( "magazines", $names, $values );
				if( !empty( $id ) ) {
					$array = array(
						"event" => "publication_created",
						"client" => $params["Publisher"],
						"pubName" => $params['Name'],
						"jobCode" => $params['Code'],
						);
					//$result = SwitchSend( $array );	
					$result[0] = "true";			
					if( $result[0] == "true" ) {
						changeXmlDatabase( 'add', $params, XMLPATH.'/pmd.xml' );
						
						$allowedMags = explode( ",", $this->user["showMagazines"] );
						if( $allowedMags[0]!= "" ) {
							if( !in_array( $id, $allowedMags ) ) $allowedMags[] = $id;
							$amags = implode( ",", $allowedMags );
							}
						else {
							$amags = $id;
							}
						sql_update( "accounts", "showMagazines='".$amags."'", "id='".$this->user["id"]."'" );
						
						$admins = sql_aget( "accounts", "`group`='2'", "id, showMagazines" );
						for( $i = 0; $i < count( $admins ); $i++ ) {
							if( $admins[$i]["id"] != $this->user["id"] ) {
								sql_update( "accounts", "showMagazines='".$admins[$i]["showMagazines"].",".$id."'", "id='".$admins[$i]["id"]."'" );
								}
							}
							
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'info' );
						$values = array( $this->user["id"], 'newMagazine', $this->pub["id"], $id, '', '', time(), 'API' );
						sql_add( 'system_log', $names, $values );							
						
						$this->response["success"] = true;
						$this->response["magazineID"] = $id;
						}
					else {
						$this->response["error"] = true;
						$this->response["message"] = "Error during creating magazine (SwitchSend)";
						}
					}
				else {
					$this->response["error"] = true;
					$this->response["message"] = "Can't create magazine";
					}
				}
			else {
				$this->response["error"] = true;
				$this->response["message"] = "Magazine code already exist: ".$this->post["code"];				
				}
			}
		}	
	
	public function setDefParams( $var ) {
		$default = array();
		
		$default["Name"] = $this->post["name"];
		$default["Code"] = $this->post["code"];
		$default["Publisher"] = $this->post["publisher"];
		
		foreach( $this->{$var} as $key=>$value ) {
			$default[ $key ] = $value[0];
			}
		
		foreach( $this->post["xml"] as $key => $value ) {
			if( empty( $this->{$var}[$key] ) ) {
				$this->response["error"] = true;
				$this->response["message"] = "Invalid XML Parameter: ".$key;
				
				$default = array();
				break;				
				}
			elseif( !in_array( $value, $this->{$var}[$key] ) ) {
				$this->response["error"] = true;
				$this->response["message"] = "Invalid XML Parameter value: ".$key."->".$value;
				
				$default = array();
				break;				
				}
			else {
				$default[$key] = $value;
				}
			}
			
		return $default;
		}
	
	public function getPublisher() {
		$sql = sql_aget( "publishers", "name='".$this->post["publisher"]."'", "*" );
		
		if( !empty( $sql[0]["id"] ) ) {
			$this->pub = $sql[0];
			}
		else {
			$this->response["error"] = true;
			$this->response["message"] = "Not a valid publisher value";
			}
		}
		
	public function checkParams( $type = "defParams" ) {
		$have_all = true;
		$check = $this->{$type};

		for( $i = 0; $i < count( $check ); $i++ ) {
			if( empty( $this->post[ $check[$i] ] ) ) {
				$have_all = false;
				break;
				}
			}
		
		if( !$have_all ) {
			$this->response["error"] = true;
			$this->response["message"] = "Missing parameter: ".$check[$i];			
			}	
			
		return $have_all;
		}
	
	public function login( $user, $pass ) {
		global $con;
		$sql = sql_aget( "accounts", "name='".mysqli_real_escape_string( $con, $user )."'", "*" );
		if( !empty( $sql[0]["id"] ) && !checkPassword( $pass, $sql[0]["pass"], $sql[0]["id"] ) ) {
			$sql = array();
			}

		if( !empty( $sql[0]["id"] ) ) {
			$this->user = $sql[0];
			
			$perm = sql_aget( "user_groups", "id='".$this->user["group"]."'", "*" );
			foreach( $perm[0] as $key => $val ) {
				$this->perm[ $key ] = $val;
				}
				
			$sql = sql_aget( "publishers", "id='".$this->user["publisher"]."'", "*" );
			if( empty( $this->post["publisher"] ) ) {
				$this->post["publisher"] = $sql[0]["name"];
				}
				
			else {
				if( $this->post["publisher"] != $sql[0]["name"] ) {
					if( $sql[0]["name"] != "Colorcom" ) {
						$this->response["error"] = true;
						$this->response["message"] = "Permission denied";						
						}
					}
				}
			}
		else {
			$this->response["error"] = true;
			$this->response["message"] = "Wrong username or password";
			}
		}	
	
	public function __construct( $POST ) {
		$this->post = $POST;
		$this->login( $this->post["username"], $this->post["password"] );

		// magDefParams below is a property default, which PHP requires to
		// be a constant expression - can't query the DB there. Cover/
		// Content/Insert need to validate against whatever's actually
		// defined in color_standards, so that list is fetched and swapped
		// in here instead, once the object exists.
		$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "name" );
		if( count( $standards ) > 0 ) {
			$colorNames = array();
			for( $s = 0; $s < count( $standards ); $s++ ) {
				$colorNames[] = $standards[$s][0];
				}
			$this->magDefParams["Cover"] = $colorNames;
			$this->magDefParams["Content"] = $colorNames;
			$this->magDefParams["Insert"] = $colorNames;
			}
		}
		
	public $magDefParams = array(
		"LocalStorage" => array( "PubFolder", "Root", "Parent" ),
		"AdAutoProof" => array( "No", "Yes" ),
		"CustomCode" => array( "No", "1", "2", "3" ),
		"CustomCode_2" => array( "R", "L" ),
		"Workflow" => array( "Full", "Hybrid", "Repack", "Resize", "Enhance" ),
		"FlatplanStages" => array( "1", "2", "3" ),
		"Enhance" => array( "Skintone", "Food", "Jewellery", "Vivid", "Paintings", "Minimal", "General" ),
		"PDFstandard" => array( "PDFX1", "PDFX4" ),
		"ImageRename" => array( "No", "Yes" ),
		"IDversion" => array( "CS6", "CS3", "CS4", "CS5", "CS55", "CC" ),
		"Language" => array( "EN", "HU" ),
		"Period" => array( "1", "2", "3", "4", "undefined" ),
		"TrimSize_x" => array( "0" ),
		"TrimSize_y" => array( "0" ),
		"Cover" => array( "FOGRA_39", "FOGRA_41", "FOGRA_45", "FOGRA_46", "FOGRA_47", "IFRA_26", "PSR_LWC_PLUS_V2_PT", "PSR_LWC_STD_V2_PT", "PSR_SC_PLUS_V2_PT", "PSR_SC_STD_V2_PT", "RGB" ),
		"Content" => array( "FOGRA_39", "FOGRA_41", "FOGRA_45", "FOGRA_46", "FOGRA_47", "IFRA_26", "PSR_LWC_PLUS_V2_PT", "PSR_LWC_STD_V2_PT", "PSR_SC_PLUS_V2_PT", "PSR_SC_STD_V2_PT", "RGB" ),
		"Insert" => array( "FOGRA_39", "FOGRA_41", "FOGRA_45", "FOGRA_46", "FOGRA_47", "IFRA_26", "PSR_LWC_PLUS_V2_PT", "PSR_LWC_STD_V2_PT", "PSR_SC_PLUS_V2_PT", "PSR_SC_STD_V2_PT", "RGB" ),
		"ArchiveMode" => array( "RGB", "CMYK" ),
		"OutputFormat" => array( "TIFF", "JPEG", "Original" ),
		"Resolution" => array( "300", "450", "600" ),
		);
		
	public $adduserParams = array( "newusername", "newuserpass", "fullname", "email" );
	public $removeuserParams = array( "remove" );
	public $defParams = array( "type" );
	public $magParams = array( "publisher", "name", "code" );
	public $removemagParams = array( "publisher", "code" );	
	}
	
?>