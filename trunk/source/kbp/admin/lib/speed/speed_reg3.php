<?php       
   
   // ������� ��� ��������� ��� ������� �������� �����
   $cn=1;
  
   // ������ ��� ���������� ����� �������
   // (����� ���� ������� ���-��� "<?php")
   $phpbegin='
      <?php 
         unset($text);
         $x="��";
         $text=implode("",file("speed_bez_sobaki.html"));
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