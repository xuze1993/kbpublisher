<?php       
   
   // ������� ��� ��������� ��� ������� �������� �����
   $cn=50000;
  
   // ������ ��� ���������� ����� �������
   // (����� ���� ������� ���-��� "<?php")
   $phpbegin='
      <?php 
         unset($test); 
         $myarray=array("123",123,"asdfgh",array(1,2,array("qwe")),"name"=>array(1,2,"second"=>array("qwe","asd","zxc"))); 
   ';

   // ��� ����� ������ (�� 0 �� N) - �� ������ �� �������� ������
   // ����������� �������� � ��������� �������, ������ ������ ������������
   $phptest='
$x="test ".$myarray["name"]["second"][1]." test";       
$x="test {$myarray[name][second][1]} test";             
$x="test ";$x.=$myarray["name"]["second"][1];$x=" test";
   ';

   // ��������� ����� ��������� �� $test (������� �� ����� ������)
   $phpexplode="\r\n";

   // ������ ��� ���������� ����� ������
   // (����� ���� ������� ���-��� "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>