<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );

	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}
	
	if( !empty( $user[0][17] ) ) {	
		include_once('../lang/'.$user[0][17].'.php');	
		}
	else {
		include_once('../lang/en.php');	
		}

	if( $_GET['op'] == 'autoreply' ) {
		$myPublisher = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$myPublisher = sql_get( 'publishers', 'id="'.$myPublisher[0][4].'"', '*' );
		$comment = sql_get( 'comments', 'id="'.$_GET['id'].'"', '*' );
		
		$txt = $lang["flatplan"]["autoreply"];
		
		$names = array( 'pub_id', 'parent', 'left', 'top', 'width', 'height', 'page', 'text', 'user', 'shape' );
		$values = array( $comment[0][1], $_GET['id'], 0, 0, 0, 0, $comment[0][7], $txt, $_SESSION['intra_user'] );
		$id = sql_add( 'comments', $names, $values );
		
		$p_id = sql_get( 'publications', 'id="'.$comment[0][1].'"', '*' );
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_SESSION['intra_user'], 'newCReply', $p_id[0][1], $p_id[0][2],  $p_id[0][10], $_POST['id'], time(), '' );
		sql_add( 'comment_log', $names, $values );
		}
	
	if( $_GET['op'] == 'saveReply' ) {
		$myPublisher = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );
		$myPublisher = sql_get( 'publishers', 'id="'.$myPublisher[0][4].'"', '*' );
		$comment = sql_get( 'comments', 'id="'.$_GET['id'].'"', '*' );
		
		$user = ( $_GET["visitor"] != "" ? $_GET["visitor"] : $_GET['intra_user'] );
		
		$names = array( 'pub_id', 'parent', 'left', 'top', 'width', 'height', 'page', 'text', 'user', 'shape' );
		$values = array( $comment[0][1], $_GET['id'], 0, 0, 0, 0, $comment[0][7], $_GET['txt'], $user, '' );
		
		$id = sql_add( 'comments', $names, $values );		

		$p_id = sql_get( 'publications', 'id="'.$comment[0][1].'"', '*' );
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_GET['intra_user'], 'newCReply', $p_id[0][1], $p_id[0][2],  $p_id[0][10], $_GET['id'], time(), '' );
		sql_add( 'comment_log', $names, $values );
		}
	
	if( $_GET['op'] == 'getComments' ) {
		$txt = "";
		
		$myPublisher = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', 'publisher' );
		$myPublisher = sql_get( 'publishers', 'id="'.$myPublisher[0][0].'"', 'id' );
		$comment = sql_get( 'comments', 'id="'.$_GET['id'].'" LIMIT 1', '*' );
		$user = $firstuser = sql_get( 'accounts', 'id="'.$comment[0][10].'"', '`publisher`, `full_name`, `group`, `id`' );
		if( $user[0][0] == "" ) {
			$firstuser = "visitor";
			$user = sql_get( "ad_hoc_users", "name='".$comment[0][10]."'", "id, name" );
			if( $user[0][1] == "" ) {
				$user[0][1] = $comment[0][10];
				}
			$user[0][2] = "visitor";
			}
			
		else {
			$userPub = sql_get( 'publishers', 'id="'.$user[0][0].'"', 'id' );
			}
		
		$class = ( $user[0][3] == $comment[0][10] ? "creator" : "reply" );
		
		$subComments = sql_get( 'comments', 'parent="'.$comment[0][0].'" order by id ASC', '*' );
		$countSub = count( $subComments );
		$groupName = sql_get( "user_groups", "id='".$user[0][2]."'", "name" );
		if( $groupName[0][0] == "" ) {
			$groupName[0][0] = $lang["hotlinks"]["visitor"];
			}
					
		$txt .= $lang['flatplan']['original']."<br>
				<span class='".$class."'>".$user[0][1]."</span> (<i style='font-weight: 400;'>".$groupName[0][0]."</i>) ".$lang['flatplan']['on']."<br>
				".date( "d F, Y, H:i:" , strtotime( $comment[0][9] ) )."
				<div class='cText'>".$comment[0][8]."</div>
				<div class='line'></div>
				";
		
		for( $sub = 0; $sub < $countSub; $sub++ ) {
			$user = sql_get( 'accounts', 'id="'.$subComments[$sub][10].'"', '`publisher`, `full_name`, `group`, `id`' );
			if( $user[0][0] == "" ) {
				$user = sql_get( "ad_hoc_users", "name='".$subComments[$sub][10]."'", "id, name" );
				if( $user[0][1] == "" ) {
					$user[0][1] = $subComments[$sub][10];
					}
				}
			
			else {
				$userPub = sql_get( 'publishers', 'id="'.$user[0][0].'"', 'id' );
				}
			$groupName = sql_get( "user_groups", "id='".$user[0][2]."'", "name" );
			if( $groupName[0][0] == "" ) {
				$groupName[0][0] = $lang["hotlinks"]["visitor"];
				}
							
			if( $userPub[0][0] != $myPublisher[0][0] )	$class = 'reply';
			else $class = 'creator';

			$txt .= $lang['flatplan']['commenter']."<br>
					<span class='".$class."'>".$user[0][1]."</span> (<i>".$groupName[0][0]."</i>) ".$lang['flatplan']['on']."<br>
					".date( "d F, Y, H:i:" , strtotime( $subComments[$sub][9] ) )."
					<div class='cText'>".$subComments[$sub][8]."</div>
					<div class='line'></div>
					";
			}
		$approve = ( $firstuser[0][3] == $comment[0][10] ? true : false );
			
		$result = array( $txt, $approve, "comment_2_14_text" );
		
		}
	
	if( $_GET['op'] == 'saveComment' ) {
		if( $_GET['pageType'] == "" ) $_GET['pageType'] = "NOR";
		$names = array( 'pub_id', 'parent', 'left', 'top', 'width', 'height', 'page', 'text', 'user', 'shape', 'pageVersion', 'pageType', 'part' );
		$values = array( $_GET['pub'], $_GET['parent'], $_GET['left'], $_GET['top'], $_GET['width'], $_GET['height'], $_GET['page'], $_GET['text'], $_GET['user'], $_GET['shape'], $_GET['PageVersion'], strtoupper( $_GET['pageType'] ), $_GET["part"] );
		
		$id = sql_add( 'comments', $names, $values );

		$p_id = sql_get( 'publications', 'id="'.$_GET['pub'].'"', '*' );
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_GET['intra_user'], 'newComment', $p_id[0][1], $p_id[0][2],  $p_id[0][10], $_GET['page'], time(), '' );
		sql_add( 'comment_log', $names, $values );

		$result = $id;
		}
	
	if( $_GET['op'] == 'approveComment' ) {
		$comment = sql_get( 'comments', 'id="'.$_GET['id'].'"', '*' );
		
		sql_update( 'comments', 'status="approved"', 'id="'.$comment[0][0].'"' );

		$p_id = sql_get( 'publications', 'id="'.$comment[0][1].'"', '*' );
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_GET['intra_user'], 'approveComment', $p_id[0][1], $p_id[0][2],  $p_id[0][10], $comment[0][7], time(), '' );
		sql_add( 'comment_log', $names, $values );
		$result = 'success';
		}
		
	if( $_GET['op'] == 'deleteComment' ) {
		$comments = sql_get( 'comments', 'id="'.$_GET['id'].'"', '*' );
		for( $c = 0; $c < count( $comments ); $c++ ) {
			sql_delete( 'comments', 'id="'.$comments[$c][0].'" OR parent="'.$comments[$c][0].'"' );

			$p_id = sql_get( 'publications', 'id="'.$comments[$c][1].'"', '*' );
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
			$values = array( $_GET['intra_user'], 'deleteComment', $p_id[0][1], $p_id[0][2],  $p_id[0][10], $comments[$c][7], time(), '' );
			sql_add( 'comment_log', $names, $values );
			}

		$result = 'success';
		}
	
print json_encode( $result );
	
?>