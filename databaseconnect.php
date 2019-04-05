<?php
 $con = mysql_connect('localhost','root','');
  mysql_select_db('eurostats');
if(!$con) {
 echo '<h1>Connected to MySQL</h1>';
}
?>