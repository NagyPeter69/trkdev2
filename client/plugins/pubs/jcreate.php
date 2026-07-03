<?
$publisher = sql_get( "publishers", "id='".$user[0][4]."'", "name" );
?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["jobs"]["new_job"] ?></div>
	<div class='panelControl' style='width: 470px;'>
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<tr>
			<td align='left'>Client</td>
			<td>
				<select name="client">
					<option value="0">Ad-Hoc</option>
					<?php
					$pubs = sql_aget( "publishers", "1 order by name ASC", "*" );	
					for( $i = 0; $i < count( $pubs ); $i++ ) {
						echo "<option value='".$pubs[$i]["id"]."'>".$pubs[$i]["name"]."</option>";
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td align='left'>Job Name</td>
			<td style="height: 22px;">
				<input type='text' name='jobname' style='margin-left: 1px;'>
			</td>
		</tr>
		<tr>
			<td align='left'>Proposed Code</td>
			<td style="height: 22px;">
				<input type='text' name='jobcode' style='margin-left: 1px;'>
			</td>
		</tr>
		<tr>
			<td align='left'>Deadline</td>
			<td style="height: 22px;">
				<input type='text' class='datepicker' readonly='' name='deadline' style='margin-left: 1px;'>
			</td>
		</tr>
		<tr>
			<td align='left' valign='top'>E-mail Addresses</td>
			<td style="height: 22px;">
				<textarea name='mails' style='width: 170px; height: 60px; resize: none; margin-left: 1px;'></textarea>
			</td>
		</tr>
		<tr>
			<td align='left'>Workflow</td>
			<td>
				<select name="workflow">
					<?php
					$temp = array( 'Full', 'Hybrid', 'Repack', 'Resize', 'Enhance' );	
					foreach( $temp as $t ) {
						echo "<option value='".$t."'>".$t."</option>";
						}
					
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td align='left'>Trimmed Size</td>
			<td style="height: 22px;">
				<input type='text' name='size_x' style='width: 22px; margin-left: 1px;'> x <input type='text' name='size_y' style='width: 22px;'> mm
			</td>
		</tr>
		<tr>
			<td align='left' valign='top'>Parts</td>
			<td id="partsBox" style="height: 22px;">
				<table cellspacing='0' cellpadding='0'>
					<tr class='partadd'>
						<td>
							<img onclick="addPart()" style="cursor: pointer;" src="images/trk_plus.png">
						</td>
					</tr>
				</table>
			</td>
		</tr>
		
				
		<tr>
			<td colspan="2" align="center" align="center" style="padding-top: 10px;">
				<div onclick="closePanel( 'pubs_jcreate', 'back' )" style="margin-left: 130px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div onclick="createJob()" style="margin-left: 20px;" class="createBut panelButton"><?= $lang["standard"]["create"] ?></div>
			</td>
		</tr>
	</table>	
	</div>
</div>
</form>	

<script>
var x = 0;

$("input[name='jobname']").focusout(function() {
	var temp = $(this).val();
	temp = temp.split( " " );
	
	if( temp.length > 1 ) {
		temp = temp[0].substring(0, 3)+""+temp[1].substring(0, 2);
		}
	else {
		temp = temp[0].substring(0, 5);
		}
	
	temp = temp.toUpperCase();
	$("input[name='jobcode']").val( temp );
	});
	
function addPart() {
	var partHTML = "<tr><td class='part_opt'><div><select class='nr_"+x+"' name='partType'><option value='Cover'>Borító</option><option value='Content'>Belív</option><option value='Attachment'>Melléklet</option><option value='Endpaper'>Előzék</option><option value='Hardcover'>Táblaborító</option><option value='Softcover'>Védőborító</option></select><img onclick='removePart(this)' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'></div><div><select name='partColor'><option value='FOGRA_29'>FOGRA 29</option><option selected value='FOGRA_39'>FOGRA 39</option><option value='FOGRA_45'>FOGRA 45</option><option value='FOGRA_46'>FOGRA 45</option><option value='FOGRA_47'>FOGRA 47</option><option value='FOGRA_51'>FOGRA 51</option><option value='FOGRA_52'>FOGRA 52</option></select></div></td></tr>";
	$(partHTML).insertBefore(".partadd");
	
	$("select[name='partType']").change( function() {
		$("select[name='partType'] option").prop("disabled", false);
		
		var temp = new Array();
		$("select[name='partType']").each(function(){
			temp.push( $(this).val() );
			});
		
		for( var i = 0; i < temp.length; i++ ) {
			$("option[value='"+temp[i]+"']").prop("disabled", true);
			}
		});

	$("select[name='partType']").change();
	
	if( $("select[name='partType']").length > 1 ) {
		$(".nr_"+x ).val( $( ".nr_"+x+" option:not([disabled]):first").val() );		
		}
	
	$("select[name='partType']").change();
	x++;
	}
	
function removePart( obj ) {
	$(obj).parent().parent().parent().remove();
	$("select[name='partType']").change();
	}
	
function createJob() {
	$(".createBut").addClass("disabled");
	var data = {};
	var row = {};
	var error = false;
	
	data.client = $("select[name='client']").val();
	data.name = $("input[name='jobname']").val();
	data.code = $("input[name='jobcode']").val();
	data.deadline = $("input[name='deadline']").val();
	data.mails = $("textarea[name='mails']").val();
	data.workflow = $("select[name='workflow']").val();
	data.size = $("input[name='size_x']").val()+" x "+$("input[name='size_y']").val()+" mm";
	data.parts = "";
	
	$("input[name='jobname']").css("background-color", "");
	$("input[name='jobcode']").css("background-color", "");
	$("input[name='deadline']").css("background-color", "");
	$("textarea[name='mails']").css("background-color", "");
	$("input[name='size_x']").css("background-color", "");
	$("input[name='size_y']").css("background-color", "");
	
	if( data.name == "" ) {
		$("input[name='jobname']").css("background-color", "rgb(209, 69, 80)");
		error = true;
		}
		
	if( data.code == "" ) {
		$("input[name='jobcode']").css("background-color", "rgb(209, 69, 80)");
		error = true;
		}		

	if( data.deadline == "" ) {
		$("input[name='deadline']").css("background-color", "rgb(209, 69, 80)");
		error = true;
		}

	if( data.mails == "" ) {
		$("textarea[name='mails']").css("background-color", "rgb(209, 69, 80)");
		error = true;
		}

	if( $("input[name='size_x']").val() == "0" ) {
		$("input[name='size_x']").css("background-color", "rgb(209, 69, 80)");
		error = true;
		}

	if( $("input[name='size_y']").val() == "0" ) {
		$("input[name='size_y']").css("background-color", "rgb(209, 69, 80)");
		error = true;
		}		
	
	var mlist = data.mails.split("\n");
	for( var i = 0; i < mlist.length; i++ ) {
		if( !isEmail( mlist[i] ) ) {
			$("textarea[name='mails']").css("background-color", "rgb(209, 69, 80)");
			error = true;
			}
		}
	
	var temp = new Array();
	$(".part_opt").each(function(){
		var type = $(this).find("select[name='partType'] option:selected").val();
		var color = $(this).find("select[name='partColor'] option:selected").val();
		
		temp.push( type+"-"+color );
		});
	
	data.parts = temp.join(";");
	
	if( !error ) {
		$.ajax	({
			url:"engine/job_ajax.php?op=checkCode",
			type: "POST",
			data: { data : data },
			dataType: 'json',
			success:function( res ) {
				console.log( res );
				
				if( res == "free" )  {
					$.ajax	({
						url:"engine/job_ajax.php?op=addJob",
						type: "POST",
						data: { data : data },
						dataType: 'json',
						success:function( data ) {
							closePanel( 'pubs_jcreate', 'back' );
							}
						});
					}
				else {
					$("input[name='jobcode']").css("background-color", "rgb(209, 69, 80)");
					}
				}
			});		
		}
		
	$(".createBut").removeClass("disabled");
	}

function isEmail( email ) {
	var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	return regex.test( email );
	}
	
</script>