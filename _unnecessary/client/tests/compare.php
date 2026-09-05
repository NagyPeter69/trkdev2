<?

include_once('../../engine/connect.php');
include_once('../../engine/engine.php');
include_once('../../engine/xml_handler.php');


echo "Alap hasonlítás: AUTOCOMPARE a.pdf b.pdf<br>";
$result = r3run( 'AUTOCOMPARE', array(), '/var/www/intra/client/tests/a.pdf', '/var/www/intra/client/tests/b.pdf' );
file_put_contents( '/var/www/intra/client/tests/A-B.out', $result );
echo $result."<br><br>";

echo "Apró betűeltérés: AUTOCOMPARE b.pdf c.pdf<br>";
$result = r3run( 'AUTOCOMPARE', array(), '/var/www/intra/client/tests/b.pdf', '/var/www/intra/client/tests/c.pdf' );
file_put_contents( '/var/www/intra/client/tests/B-C.out', $result );
echo $result."<br><br>";

echo "Apró kép színeltérés: AUTOCOMPARE b.pdf d.pdf<br>";
$result = r3run( 'AUTOCOMPARE', array(), '/var/www/intra/client/tests/b.pdf', '/var/www/intra/client/tests/d.pdf' );
file_put_contents( '/var/www/intra/client/tests/B-D.out', $result );
echo $result."<br><br>";

echo "Ugynaz az oldal: AUTOCOMPARE a.pdf a.pdf<br>";
$result = r3run( 'AUTOCOMPARE', array(), '/var/www/intra/client/tests/a.pdf', '/var/www/intra/client/tests/a.pdf' );
file_put_contents( '/var/www/intra/client/tests/A-A.out', $result );
echo $result."<br><br>";

echo "Ugynaz az oldal: AUTOCOMPARE b.pdf b.pdf<br>";
$result = r3run( 'AUTOCOMPARE', array(), '/var/www/intra/client/tests/b.pdf', '/var/www/intra/client/tests/b.pdf' );
file_put_contents( '/var/www/intra/client/tests/B-B.out', $result );
echo $result."<br><br>";

echo "Apró képkülönbség: AUTOCOMPARE b.pdf e.pdf<br>";
$result = r3run( 'AUTOCOMPARE', array(), '/var/www/intra/client/tests/b.pdf', '/var/www/intra/client/tests/e.pdf' );
file_put_contents( '/var/www/intra/client/tests/B-E.out', $result );
echo $result."<br><br>";

echo "Apró képkülönbség: AUTOCOMPARE d.pdf e.pdf<br>";
$result = r3run( 'AUTOCOMPARE', array(), '/var/www/intra/client/tests/d.pdf', '/var/www/intra/client/tests/e.pdf' );
file_put_contents( '/var/www/intra/client/tests/D-E.out', $result );
echo $result."<br><br>";

echo "Apró képkülönbség: AUTOCOMPARE b.pdf f.pdf<br>";
$result = r3run( 'AUTOCOMPARE', array(), '/var/www/intra/client/tests/b.pdf', '/var/www/intra/client/tests/f.pdf' );
file_put_contents( '/var/www/intra/client/tests/B-F.out', $result );
echo $result."<br><br>";
?>
