<?php
$flows = GetSwitchFlows2();

//Switch beállítások
define( "SWITCHLOGINURL", "http://192.168.1.8:51088/login" );
define( "SWITCHURL", "http://192.168.1.8:51088/api/v1/job"  );

define( "AQID", $flows["uploads"]["flowid"] );
define( "AQOID", $flows["uploads"]["objectid"] );

define( "FLOWID", $flows["commands"]["flowid"] );
define( "OBJECTID", $flows["commands"]["objectid"] );

define( "FLOWID_TESZT", $flows["commands"]["flowid"] );
define( "OBJECTID_TESZT", $flows["commands"]["objectid"] );

?>