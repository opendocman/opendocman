<?php

$whois =(`whois jtcase@whois.ucdavis.edu`);
list($name_title,$name,$email_title, $email, $smtp_title, $smtp, $login_title, $login, $netid_title, $netid, $title_title, $title, $department_title, $department, $mailstop_title, $mailstop, $phone_title, $phone, $url_title, $url)= split (": ", $whois);
echo $whois;
list($server, $name_title)= split ("]", $name_title);
echo '<br><br>';
print "$name_title:$name<br>";
print "$login_title</br>";
?>
