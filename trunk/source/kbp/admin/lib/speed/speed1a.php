<?php       
   
   // ������� ��� ��������� ��� ������� �������� �����
   $cn=50000;
  
   // ������ ��� ���������� ����� �������
   // (����� ���� ������� ���-��� "<?php")
   $phpbegin='<?php $test="12345567890";';

   // ��� ����� ������ (�� 0 �� N) - �� ������ �� �������� ������
   // ����������� �������� � ��������� �������, ������ ������ ������������
   $phptest='
$x="test ".$test." test ".$test." test ".$test;                
$x="test $test test $test test $test";                         
$x="test ";$x.=$test;$x="test ";$x.=$test;$x="test ";$x.=$test;
   ';

   // ��������� ����� ��������� �� $test (������� �� ����� ������)
   $phpexplode="\r\n";

   // ������ ��� ���������� ����� ������
   // (����� ���� ������� ���-��� "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>