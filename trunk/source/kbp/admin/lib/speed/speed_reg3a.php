<?php       
   
   // ������� ��� ��������� ��� ������� �������� �����
   $cn=1;
  
   // ������ ��� ���������� ����� �������
   // (����� ���� ������� ���-��� "<?php")
   $phpbegin='
      <?php 
         unset($text);
         $text=implode("",file("speed.html"));
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text.$text;
         //$text=$text.$text.$text.$text;
         //$text=$text.$text.$text;
         
         echo "(".strlen($text).") ";
   ';

   // ��� ����� ������ (�� 0 �� N) - �� ������ �� �������� ������
   // ����������� �������� � ��������� �������, ������ ������ ������������
   $phptest='
eregi("([a-z_-]+@([a-z][a-z-]*\.)+([a-z]{2}|com|mil|org|net|gov|edu|arpa|info|biz))",$text);
preg_match("/([a-z_-]+@([a-z][a-z-]*\.)+([a-z]{2}|com|mil|org|net|gov|edu|arpa|info|biz))/im",$text);
   ';

   // ��������� ����� ��������� �� $test (������� �� ����� ������)
   $phpexplode="\r\n";

   // ������ ��� ���������� ����� ������
   // (����� ���� ������� ���-��� "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>