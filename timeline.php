	<table id='job_names' cellspacing='0' cellpadding='0'>
		<tr>
			<td id='jobs' valign='top' style='min-width: 150px;'>
				<table width='100%' cellspacing='0' cellpadding='0'>
					<thead>
						<tr>
							<td style='background: transparent;'>&nbsp;</td>
						</tr>					
						<tr>
							<td class='left top bottom2' >Munka neve</td>
						</tr>
					</thead>
					<tbody>			
					</tbody>					
				</table>
			</td>
			<td id='timeline' valign='top'>
				<div>
				<table cellspacing='0' cellpadding='0'>
					<thead>
						<tr>
							<?
							setlocale(LC_ALL, 'hu_HU.UTF8');		
							$timestamp = strtotime( "-7 days" );
							$days = cal_days_in_month( CAL_GREGORIAN, date( "n", $timestamp ), date( "Y", $timestamp ) );
							$remain = 21;
							$start = date( 'j' , $timestamp );
							$remain -= $month_day_left = $days-$start;
							echo "<td align='center' class='left top right' colspan='".($month_day_left+1)."' >".strftime( "%Y. %B", $timestamp )."</td>";
							if( $remain > 0 ) {
								$timestamp = strtotime( "first day of next month", $timestamp );
								$days = cal_days_in_month( CAL_GREGORIAN, date( "n", $timestamp ), date( "Y", $timestamp ) );
								echo "<td align='center' class='right top' colspan='".$remain."' >".strftime( "%Y. %B", $timestamp )."</td>";
								}
							?>
							
						</tr>
						<tr>
						<?	
							echo generate_tline_header();
						?>
						</tr>
					</thead>
					<tbody>	
					</tbody>
				</table>
				</div>
			</td>
		</tr>
	</table>

<script>

function refresh_jobs(){
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=refresh_jobs',
		dataType: 'json',
		success:function( data ) {
			console.log( data[2] );
			$('#jobs > table > tbody').html( data[0] );
			$('#timeline > div > table > tbody').html( data[1] );
			$('.calendar_event').each( function() {
				$(this).tipTip();
				});
    		var new_height = parseInt( $('#timeline > div > table').height() )+10;
 	  		$('#timeline > div').height( new_height )

			setTimeout(function(){ refresh_jobs(); }, 10000);
			}
		});
	}

$(function(){
    refresh_jobs();
	});

</script>