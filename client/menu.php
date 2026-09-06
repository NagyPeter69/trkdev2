<ul class='nav'>
	<?
	if( isset( $_SESSION['intra_user'] ) && ( $_GET["page"] != "vflatplan" && $_GET["page"] != "vflatplan_preview" ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		
		if( $user[0][4] != 0 ) {
			if( empty( $user[0][11] ) ) {
				$pubs = getShortPubs( $user[0] );
				$mag = sql_aget( "magazines", "id='".$pubs[0][2]."'", "*" );
				sql_update( "accounts", "actual='".$mag[0]["code"]."_".$pubs[0][10]."'", "id='".$user[0][0]."'" );
				$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
				}
			
			$magazines = sql_get( "magazines", "publisher_id='".$user[0][4]."'", "*" );
			$magCodes = array();
			for( $i = 0; $i < count( $magazines ); $i++ ) {
				$magCodes[] = $magazines[$i][3];
				}
			
			$advanced = explode( ",", $user[0][10] );
			for( $i = 0; $i < count( $advanced ); $i++ ) {
				$magazines = sql_get( "magazines", "publisher_id='".$advanced[$i]."'", "*" );
				for( $y = 0; $y < count( $magazines ); $y++ ) {
					$magCodes[] = $magazines[$y][3];
					}
				}
			
			$processes = array();
			$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( in_array( $temp->Item[$x]->Code, $magCodes ) ) {
						$processes[] = (string) $xml->Item[$x]->Workflow;
						}
					}
				}		

			$allow = false;
			for( $i = 0; $i < count( $processes ); $i++ ) {
				if( $processes[$i] != "Softproof" ) {
					$allow = true;
					break;
					}
				}

			// Resize and Auto workflows have no PDF approval, so Flatplan/
			// Pages/Planner (all of which revolve around page approval) are
			// hidden for them. SuperUser (group 2) is exempt from this and
			// every other publication-based gate - they see every menu item
			// regardless of the current publication's workflow.
			$actualCode = explode( "_", $user[0][11] )[0];
			$currentProcess = "";
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $actualCode ) {
						$currentProcess = (string) $xml->Item[$x]->Workflow;
						}
					}
				}
			$noApprovalWorkflow = in_array( $currentProcess, array( 'Resize', 'Auto' ) );
			if( $user[0][8] == 2 ) {
				$noApprovalWorkflow = false;
				}

			if( 0 ) {
				echo "<li class='planner'><a href='#'>Upload</a></li>";
				}
			else {
				echo "<li class='"; if( $_GET['page'] == 'create_magazine' or $_GET['page'] == 'publication' ) { echo 'selected'; } echo "'><a href='?page=create_magazine'>".( isMobile() ? "Pubs" : $lang['menu']['publications'] )."</a></li>";
				/*if( $rights["jobs_menu"] ) {
						echo "<li class='"; if( $_GET['page'] == 'jobs' ) { echo 'selected'; } echo "'><a href='?page=jobs'>".$lang['menu']['jobs']."</a></li>";			
					}	*/
				
				//if( $allow ) {
				if( $rights['ad_view'] && $rights['ad_send'] && $rights['ad_upload'] && $rights['ad_delete'] ) {
					echo "<li class='"; if( $_GET['page'] == 'advertisement' ) { echo 'selected'; } echo "'><a href='?page=advertisement'>".$lang['menu']['advertisement']."</a></li>";
					}
				//	}

				if( $user[0][11] != '' && !$noApprovalWorkflow ) {
					echo "<li class='"; if( $_GET['page'] == 'flatplan' ) { echo 'selected'; } echo "'><a href='?page=flatplan'>".$lang['menu']['flatplan']."</a></li>";
					}

				if( $user[0][11] != '' && !$noApprovalWorkflow ) {
					echo "<li class='"; if( $_GET['page'] == 'flatplan_preview' ) { echo 'selected'; } echo "'><a href='?page=flatplan_preview'>".$lang['menu']['pages']."</a></li>";
					}

				$tr = checkTransferRight( $user[0][0], "" );
					
				if( $tr["up"] ) {
					echo "<li class='planner "; if( $_GET['page'] == 'filetransfer' ) { echo 'selected'; } echo "'><a href='?page=filetransfer&id=".$tr["id"]."&type=".$tr["type"]."'>".$lang['menu']['upload']."</a></li>";
					}
						
				if( !empty( $tr ) && in_array( $user[0][4], $showToPlanner ?? array() ) ) {
					
					
					/*
					if( $tr["down"] ) {
						echo "<li class='planner "; if( $_GET['page'] == 'filetransfer_view' ) { echo 'selected'; } echo "'><a href='?page=filetransfer_view&id=".$tr["id"]."&type=".$tr["type"]."'>".$lang['menu']['download']."</a></li>";
						}
					*/
					}
					
				echo "<li class='planner "; if( $_GET['page'] == 'assets' ) { echo 'selected'; } echo "'><a href='?page=assets'>".$lang['menu']['download']."</a></li>";
				if( !$noApprovalWorkflow ) {
					echo "<li class='planner "; if( $_GET['page'] == 'flatplan_planner' ) { echo 'selected'; } echo "'><a href='?page=flatplan_planner'>".$lang['menu']['planner']."</a></li>";
					}
				//echo "<li><div style='height: 15px; margin-top: 19px; float: left; width:2px; background: rgb( 200, 200, 200 );'>&nbsp;</div></li>";
				//echo "<li class='"; if( $_GET['page'] == 'ad_hoc' ) { echo 'selected'; } echo "'><a href='?page=ad_hoc'>".$lang['menu']['direct']."</a></li>";
				}
			}
		else {
			$process = "";
			$magazines = sql_aget( "magazines", "id='".$user[0][21]."'", "*" );
			$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $magazines[0]["code"] ) {
						$process = (string) $xml->Item[$x]->Workflow;
						}
					}
				}
				
			$extended = array( "Full", "Hybrid" );
			if( in_array( $process, $extended ) ) {
				//echo "<li class='"; if( $_GET['page'] == 'create_magazine' or $_GET['page'] == 'publication' ) { echo 'selected'; } echo "'><a href='?page=create_magazine'>".( isMobile() ? "Pubs" : $lang['menu']['publications'] )."</a></li>";
				
				if( $rights['ad_view'] && $rights['ad_send'] && $rights['ad_upload'] && $rights['ad_delete'] ) {
					echo "<li class='"; if( $_GET['page'] == 'advertisement' ) { echo 'selected'; } echo "'><a href='?page=advertisement'>".$lang['menu']['advertisement']."</a></li>";
					}
				
				if( $user[0][11] != '' ) {
					echo "<li class='"; if( $_GET['page'] == 'flatplan' ) { echo 'selected'; } echo "'><a href='?page=flatplan'>".$lang['menu']['flatplan']."</a></li>";
					}
		
				if( $user[0][11] != '' ) {
					echo "<li class='"; if( $_GET['page'] == 'flatplan_preview' ) { echo 'selected'; } echo "'><a href='?page=flatplan_preview'>".$lang['menu']['pages']."</a></li>";
					}
								
				$tr = checkTransferRight( $user[0][0], "" );
				if( $tr["up"] ) {
					echo "<li class='planner "; if( $_GET['page'] == 'filetransfer' ) { echo 'selected'; } echo "'><a href='?page=filetransfer&id=".$tr["id"]."&type=".$tr["type"]."'>".$lang['menu']['upload']."</a></li>";
					}

				echo "<li class='planner "; if( $_GET['page'] == 'assets' ) { echo 'selected'; } echo "'><a href='?page=assets'>".$lang['menu']['download']."</a></li>";			
				}
			else {
				//echo "<li class='"; if( $_GET['page'] == 'create_magazine' or $_GET['page'] == 'publication' ) { echo 'selected'; } echo "'><a href='?page=create_magazine'>".( isMobile() ? "Pubs" : $lang['menu']['publications'] )."</a></li>";
								
				$tr = checkTransferRight( $user[0][0], "" );
				if( $tr["up"] ) {
					echo "<li class='planner "; if( $_GET['page'] == 'filetransfer' ) { echo 'selected'; } echo "'><a href='?page=filetransfer&id=".$tr["id"]."&type=".$tr["type"]."'>".$lang['menu']['upload']."</a></li>";
					}

				echo "<li class='planner "; if( $_GET['page'] == 'assets' ) { echo 'selected'; } echo "'><a href='?page=assets'>".$lang['menu']['download']."</a></li>";						
				}
			}
		}
	elseif( $_SESSION['standalone_visitor'] == "1" ) {
		echo "<li class='"; if( $_GET['page'] == 'vflatplan' ) { echo 'selected'; } echo "'><a href='?page=vflatplan&hash=".$_GET["hash"]."'>".$lang['menu']['flatplan']."</a></li>";
		echo "<li class='"; if( $_GET['page'] == 'vflatplan_preview' ) { echo 'selected'; } echo "'><a href='?page=vflatplan_preview&hash=".$_GET["hash"]."'>".$lang['menu']['pages']."</a></li>";
		}		
	else {
		echo "	<li class='selected'>";
			echo "<a href='?page='>".$lang["login"]["login_menu"]."</a>";
			if( HOST == "Dev" ) {
				echo "<span style='color: #FFF;padding-left: 30px;'>Developer</span>";
				}
		echo "</li>";
		}
	?>
</ul>