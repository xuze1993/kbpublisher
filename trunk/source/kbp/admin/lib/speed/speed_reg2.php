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
eregi("(��[a-z�-�]{1,20})",$text);
preg_match("/(��[a-z�-�]{1,20})/im",$text);
   ';

   // ��������� ����� ��������� �� $test (������� �� ����� ������)
   $phpexplode="\r\n";

   // ������ ��� ���������� ����� ������
   // (����� ���� ������� ���-��� "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>