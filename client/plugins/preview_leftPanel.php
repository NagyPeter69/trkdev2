	<? if( $rights['viewComment'] ) { ?>
	<div id='panel_top'>
		<div class='panelElement'>
			<img class="commentEnabler" id="comment3" src='images/icons/comment3.png' height='25px'>
		</div>
		<div class='panelElement'>
			<img class="commentEnabler" id="comment2" src='images/icons/comment2.png' height='25px'>
		</div>
		<div class='panelElement'>
			<img class="commentEnabler" id="comment" src='images/icons/comment.png' height='25px'>
		</div>
	</div>
	<? } if( $rights['createComment'] ) { ?>
	<div id='panel'>
		<div class='panelElement'>
			<img id="square" onclick='panelElementClick( this, "square" );' src='images/icons/dashsquare.png' height='25px'>
		</div>
		<div class='panelElement'>
			<img id="circle" onclick='panelElementClick( this, "circle" );' src='images/icons/dashedcircle.png' height='25px'>
		</div>
		<div class='panelElement'>
			<img id="dot" onclick='panelElementClick( this, "dot" );' src='images/icons/dot.png' height='25px'>
		</div>		
	</div>
	<? } ?>