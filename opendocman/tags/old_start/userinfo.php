<?php

$whois =array(`whois logart@whois.ucdavis.edu`);
print "<pre>$whois</pre>";
list($name_title,$name,$smtp,$login,$netid)= split (": ", $whois);

print "<pre>$name_title$name</pre>";
?>
