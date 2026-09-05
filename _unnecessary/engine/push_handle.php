<?

require_once('../plugins/push/Pusher.php');

$app_key = 'ab9e2f2c231b0ab5d298';
$app_secret = 'a63fb36719c794ff6353';
$app_id = '60031';

$pusher = new Pusher($app_key, $app_secret, $app_id);
$data = array('message' => 'This is an HTML5 Realtime Push Notification!');
$pusher->trigger('nyk_not', 'notification', $data);

?>