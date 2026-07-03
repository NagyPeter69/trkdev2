<?
$pack = sql_get( 'packages', 'id="'.$_GET['id'].'"', '*' );
$pub = sql_get( 'publications', 'id="'.$pack[0][1].'"', '*' );
			
$mag = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
$checker = sql_get( 'package_info', 'package_id="'.$_GET['id'].'" AND event="lowres"', '*' );

echo "<div id='pub_id' pub_id='".$_GET['id']."' style='display:none;' ></div>";
if( count( $checker ) > 0 ) {
?>

<div class='setting_row2'>
	<div class='settings_row_header'>Alacsony felbontású képek</div>

	<div style='color: #777; float:right; margin-right: 10px; line-height: 40px;'><span class='close' onclick='hide_pubs2( <?= $_GET['id'] ?> ); return false;'>Bezárás</span></div>
	<div style='clear:both;'></div>
	
	<div class='settings_row_content'>
		<table width='100%' cellspacing='0' cellpadding='0'>
			<?
			$counter = 0;
			for( $x = 0; $x < count( $checker ); $x++ ) {
				if( $counter == 0 ) { echo '<tr>'; }
				echo "<td align='center' valign='top'>";
				if( strlen( $checker[$x][3] ) > 18 ) {
					echo "<div class='longname' title='".$checker[$x][3]."'>".substr( $checker[$x][3], 0, 10 )."[...]".substr( $checker[$x][3], -4 )."</div>";
					}
				else {
					echo "<div>".$checker[$x][3]."</div>";
					}
				echo "<img src='packages/".$mag[0][3]."/".$pub[0][10]."/".$pack[0][4]."/lowres_".substr( $checker[$x][3], 0, -4 ).".jpg' width='150px'>";
				echo "</td>";
				$counter ++;
				if( $counter == 3 ) {
						echo "</tr>";
						$counter = 0;
						}
				}
			?>
		</table>
	</div>
</div>
<?
	}
	
$checker = sql_get( 'package_info', 'package_id="'.$_GET['id'].'" AND event="corrupt"', '*' );
if( count( $checker ) > 0 ) {
?>
<div class='setting_row2'>
	<div class='settings_row_header'>Hibás képek</div>

	<div style='color: #777; float:right; margin-right: 10px; line-height: 40px;'><span class='close' onclick='hide_pubs2( <?= $_GET['id'] ?> ); return false;'>Bezárás</span></div>
	<div style='clear:both;'></div>
	
	<div class='settings_row_content'>
		<table width='100%' cellspacing='0' cellpadding='0'><tr><td align='left'>
			<div style='padding-left: 10px; padding-right: 10px;'>
			<?
			for( $x = 0; $x < count( $checker ); $x++ ) {
				echo "&bull; ".$checker[$x][3]."<br>";
				}
			?>
			</div>
		</td></tr></table>
	</div>
</div>
<?
	}
	
$checker = sql_get( 'package_info', 'package_id="'.$_GET['id'].'" AND event="missing"', '*' );
if( count( $checker ) > 0 ) {
?>
<div class='setting_row2'>
	<div class='settings_row_header'>Hiányzó képek</div>

	<div style='color: #777; float:right; margin-right: 10px; line-height: 40px;'><span class='close' onclick='hide_pubs2( <?= $_GET['id'] ?> ); return false;'>Bezárás</span></div>
	<div style='clear:both;'></div>
	
	<div class='settings_row_content'>
		<table width='100%' cellspacing='0' cellpadding='0'><tr><td align='left'>
			<div style='padding-left: 10px; padding-right: 10px;'>
			<?
			for( $x = 0; $x < count( $checker ); $x++ ) {
				echo "&bull; ".$checker[$x][3]."<br>";
				}
			?>
			</div>
		</td></tr></table>
	</div>
</div>
<?
	}
?>
