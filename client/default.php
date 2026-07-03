<?
header('Content-Type: text/html; charset=utf-8');

?>
<link href="css/timeline.css" rel="stylesheet" type="text/css" />

<?
$timeline = array();
setlocale(LC_ALL, 'hu_HU.UTF8');		
$timestamp = strtotime( "-3 days" );
$days = cal_days_in_month( CAL_GREGORIAN, date( "n", $timestamp ), date( "Y", $timestamp ) );
$remain = 18;
$start = date( 'j' , $timestamp );
$remain -= $month_day_left = $days-$start;
if( $month_day_left > 3 ) $timeline['header']['first_month'] = strftime( "%Y. %B", $timestamp );
else $timeline['header']['first_month'] = '';
$timeline['settings']['first_month_colspan'] = $month_day_left+1;
if( $remain > 3 ) {
	$timeline['settings']['second_month_colspan'] = $remain;
	$timestamp = strtotime( "first day of next month", $timestamp );
	$days = cal_days_in_month( CAL_GREGORIAN, date( "n", $timestamp ), date( "Y", $timestamp ) );
	$timeline['header']['second_month'] = strftime( "%Y. %B", $timestamp );
	}

$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*');

?>

<table id='timeline' cellspacing='0' cellpadding='0' style='margin-top: -20px;'>
		<thead>
		<tr>
			<td align='left' style='width: 250px;'>
				<div style='text-align: left; width: 25px; font-weight: bold; padding-left: 23px;'><?= $lang["timeline"]["jobs"] ?></div>
			</td>
			<td id='timeline_date'>
				<table id='tl' class='tline' cellspacing='0' cellpadding='0'>

					<tr>
						<?	
							echo generate_tline_header();
						?>					
					</tr>
				</table>
			</td>
		</tr>
		</thead>
		<tbody>

		<?
			$jobs = sql_get( 'publications', 'publisher_id="'.$user[0][4].'" ORDER BY `id` ASC', '*' );
			$jobs2 = anotherPubs( $user );
			for( $i = 0; $i < count( $jobs2 ); $i++ ) {
				$jobs[] = $jobs2[$i];
				}
				
			for( $i = 0; $i < count( $jobs ); $i++ ) {
				$mag = sql_get( 'magazines', 'id="'.$jobs[$i][2].'"', 'name' );
				$jobs[$i][] = $mag[0][0];
				}
			
			usort($jobs, querySort(15) );	
			for( $i = 0; $i < count( $jobs ); $i++ ) {
				if ($i % 2) $class = 'one';
				else $class = 'two';
				$magazine =  sql_get( 'magazines', 'id="'.$jobs[$i][2].'"', '*' );
				echo "<tr class='l'>";
					echo "<td align='left' class='".$class."' style='width: 160px; font-size: 14px; padding-left: 15px;'><a href='";
						//echo "?page=advertisement&pub=".$jobs[$i][10];
						echo "?page=publication&id=".$jobs[$i][0]."&code=".$jobs[$i][10];
					echo "'><div style='float:left;'>".$magazine[0][2]."</div>";
					echo "<div style='float:right; margin-right: 10px;'>".$jobs[$i][10]."</div></a></td>";
					echo "<td class='".$class."'>";
						echo "<table id='timeline' class='tline' cellspacing='0' cellpadding='0'>";
							echo "<tr>";
							$timestamp = strtotime( '-7 days' );
							$time = date( "Y-m-j", $timestamp );
							$timestamp = strtotime( $time, $timestamp );
							for( $y = 0; $y < ( $timeline['settings']['first_month_colspan']+$timeline['settings']['second_month_colspan'] ); $y++ ) {
								$calendar_date = strtotime( '+'.$y.' days', $timestamp );
								echo "<td style='position: relative;";
								if( $y == 7 ) echo "background: #9AA1D2 !important; border-left: 1px solid #A3A3A3 !important;";
								echo "'>";
									echo "<div>";
									$event_time = strtotime( $jobs[$i][11] );
									$event_time = strtotime( date( "Y-m-j", $event_time ) );
									if( $event_time == $calendar_date ) {
										$txt = 'Határidő:&nbsp;&nbsp;'.date( "Y. m. d. H:i", strtotime( $jobs[$i][11] ) ).'<br>
												
												';
										echo "<div class='calendar_event deadline' title='".$txt."'>";
											echo date( "G:i" , strtotime( $jobs[$i][11] ) );
										echo "</div>";
										}
									echo "</div>";
								echo "</td>";
								}
							echo "</tr>";
						echo "</table>";
					echo "</td>";
				echo "</tr>";
				}
		?>
		</tbody>
</table>

<script>
$( document ).ready( function(){
	$('#content').css({
		'padding': '20px 0px 0px 0px'
		});
	});

$('.calendar_event').each( function() {
				$(this).tipTip();
				});
</script>