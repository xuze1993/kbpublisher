<?php       
   
   // ������� ��� ��������� ��� ������� �������� �����
   $cn=50000;
  
   // ������ ��� ���������� ����� �������
   // (����� ���� ������� ���-��� "<?php")
   $phpbegin='
      <?php 
         unset($test); 
         $myarray=array("123",123,"test"=>"asdfgh",array(1,2,array("qwe")),"name"=>array(1,2,"second"=>array("qwe","asd","zxc"))); 
   ';

   // ��� ����� ������ (�� 0 �� N) - �� ������ �� �������� ������
   // ����������� �������� � ��������� �������, ������ ������ ������������
   $phptest='
$x="test".$myarray["test"]."test";
$x="test$myarray[test]test";      
$x="test{$myarray[test]}test";    
   ';

   // ��������� ����� ��������� �� $test (������� �� ����� ������)
   $phpexplode="\r\n";

   // ������ ��� ���������� ����� ������
   // (����� ���� ������� ���-��� "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>