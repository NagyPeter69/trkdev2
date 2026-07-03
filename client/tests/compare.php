<?

include_once('../../engine/connect.php');
include_once('../../engine/engine.php');
include_once('../../engine/xml_handler.php');


$command = './r3 -mode:AUTOCOMPARE /var/www/intra/client/tests/a.pdf /var/www/intra/client/tests/b.pdf | tee /var/www/intra/client/tests/A-B.out 2>&1';
echo "Alap hasonlítás:".$command."<br>";
$result = shell_exec('
	cd /var/www/intra/client/engine/r3 2>&1;
	'.$command.';
	');	
echo $result."<br><br>";

$command = './r3 -mode:AUTOCOMPARE /var/www/intra/client/tests/b.pdf /var/www/intra/client/tests/c.pdf | tee /var/www/intra/client/tests/B-C.out 2>&1';
echo "Apró betűeltérés:".$command."<br>";
$result = shell_exec('
	cd /var/www/intra/client/engine/r3 2>&1;
	'.$command.';
	');	
echo $result."<br><br>";

$command = './r3 -mode:AUTOCOMPARE /var/www/intra/client/tests/b.pdf /var/www/intra/client/tests/d.pdf | tee /var/www/intra/client/tests/B-D.out 2>&1';
echo "Apró kép színeltérés:".$command."<br>";
$result = shell_exec('
	cd /var/www/intra/client/engine/r3 2>&1;
	'.$command.';
	');	
echo $result."<br><br>";

$command = './r3 -mode:AUTOCOMPARE /var/www/intra/client/tests/a.pdf /var/www/intra/client/tests/a.pdf | tee /var/www/intra/client/tests/A-A.out 2>&1';
echo "Ugynaz az oldal:".$command."<br>";
$result = shell_exec('
	cd /var/www/intra/client/engine/r3 2>&1;
	'.$command.';
	');	
echo $result."<br><br>";

$command = './r3 -mode:AUTOCOMPARE /var/www/intra/client/tests/b.pdf /var/www/intra/client/tests/b.pdf | tee /var/www/intra/client/tests/B-B.out 2>&1';
echo "Ugynaz az oldal:".$command."<br>";
$result = shell_exec('
	cd /var/www/intra/client/engine/r3 2>&1;
	'.$command.';
	');	
echo $result."<br><br>";

$command = './r3 -mode:AUTOCOMPARE /var/www/intra/client/tests/b.pdf /var/www/intra/client/tests/e.pdf | tee /var/www/intra/client/tests/B-E.out 2>&1';
echo "Apró képkülönbség:".$command."<br>";
$result = shell_exec('
	cd /var/www/intra/client/engine/r3 2>&1;
	'.$command.';
	');	
echo $result."<br><br>";

$command = './r3 -mode:AUTOCOMPARE /var/www/intra/client/tests/d.pdf /var/www/intra/client/tests/e.pdf | tee /var/www/intra/client/tests/D-E.out 2>&1';
echo "Apró képkülönbség:".$command."<br>";
$result = shell_exec('
	cd /var/www/intra/client/engine/r3 2>&1;
	'.$command.';
	');	
echo $result."<br><br>";

$command = './r3 -mode:AUTOCOMPARE /var/www/intra/client/tests/b.pdf /var/www/intra/client/tests/f.pdf | tee /var/www/intra/client/tests/B-F.out 2>&1';
echo "Apró képkülönbség:".$command."<br>";
$result = shell_exec('
	cd /var/www/intra/client/engine/r3 2>&1;
	'.$command.';
	');	
echo $result."<br><br>";
?>