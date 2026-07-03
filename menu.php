<ul class='nav'>
	<li class='<? if( !isset( $_GET['page'] ) or $_GET['page'] == '' ) { echo 'selected'; } ?>'><a href='?page='>Idővonal</a></li>
	<li class='<? if( $_GET['page'] == 'create_user' ) { echo 'selected'; } ?>'><a href='?page=create_user'>Új felhasználó</a></b></li>
	<li class='<? if( $_GET['page'] == 'magazines' ) { echo 'selected'; } ?>'><a href='?page=magazines'>Kiadványok</a></li>
	<li class='<? if( $_GET['page'] == 'pmd' ) { echo 'selected'; } ?>'><a href='?page=pmd'>Master Publication Database</a></li>
</ul>