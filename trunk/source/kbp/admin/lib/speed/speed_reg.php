<?php       
   
   // ������� ��� ��������� ��� ������� �������� �����
   $cn=1;
  
   // ������ ��� ���������� ����� �������
   // (����� ���� ������� ���-��� "<?php")
   $phpbegin='
      <?php 
         unset($text);
         $x="��";
         $text=implode("",file("speed.html"));
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text;
         echo "(".strlen($text).") ";
   ';

   // ��� ����� ������ (�� 0 �� N) - �� ������ �� �������� ������
   // ����������� �������� � ��������� �������, ������ ������ ������������
   $phptest='
eregi("���+��",$text);
preg_match("/���+��/im",$text);
   ';

   // ��������� ����� ��������� �� $test (������� �� ����� ������)
   $phpexplode="\r\n";

   // ������ ��� ���������� ����� ������
   // (����� ���� ������� ���-��� "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>