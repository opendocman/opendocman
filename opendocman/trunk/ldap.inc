<?php
/*
ldap.inc - Not Working - just a rough draft of ldap integration
Copyright (C) 2002-2010 Stephen Lawrence Jr.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/


function draw_ldap_info($ldap_request)
{

    $ds=ldap_connect($GLOBALS['CONFIG']['ldap_server']);

// ** IF Connected, send request.  Takes the user input, puts asterisks around it for a larger query return. ** //

    if ($ds)
    {
        $cn=" ".$ldap_request." ";
        $cn = preg_replace ("/ /", "*", $cn);
        $ldap_no_aster= preg_replace ("/\*/", " ", $ldap_request);

// ** $filter is the field it searches in the LDAP database.  Compares against Employee Number first. **//
        $filter="(|(employeenumber=$ldap_no_aster))";

// ** $justthese are the fields you want returned **//
        $justthese = array("cn", "mail", "uid", "telephonenumber", "postaladdress", "ou", "title");
        $r=ldap_bind($ds); #bind to directory

// ** ldap_search(connection, "Base DN", search fields, fields returned,0,[max returns]) **//
        $sr=@ldap_search($ds,$GLOBALS['CONFIG']['ldap_basedn'], $filter,$justthese,0,300);

        switch(ldap_count_entries($ds,$sr))
        {

//** Only one entry returned, go directly to Detailed View **//
            case 1:
                $info = ldap_get_entries($ds, $sr); #get the result
                print"<table cellpadding=1 cellspacing=1 align='center'>";
                for ($i=0; $i<$info["count"]; $i++)
                {  #loop though ldap search result
                    print "<tr><td class=text><b>Name: &nbsp;&nbsp;</b></td><td class=text>";
                    eval("print \$info[\$i]['cn'][0];"); #print attribute value
                    print "</td></tr>";
                    print "<tr><td class=text><b>Title: &nbsp;&nbsp;</b></td><td class=text>";
                    eval("print \$info[\$i]['title'][0];"); #print attribute value
                    print "</td></tr>";
                    print "<tr><td class=text><b>Login ID: &nbsp;&nbsp;</b></td><td class=text>";
                    eval("print \$info[\$i]['uid'][0];"); #print attribute value
                    print "</td></tr>";
                    print "<tr><td class=text><b>Address: &nbsp;&nbsp;</b></td><td class=text>";
                    eval("print \$info[\$i]['postaladdress'][0];"); #print attribute value
                    print "</td></tr>";
                    print "<tr><td class=text><b>Department: &nbsp;&nbsp;</b></td><td class=text>";
                    eval("print \$info[\$i]['ou'][0];"); #print attribute value
                    print "</td></tr>";
                    print "<tr><td class=text><b>Telephone: &nbsp;&nbsp;</b></td><td class=text>";
                    eval("print \$info[\$i]['telephonenumber'][0];"); #print attribute value
                    print "</td></tr>";
                    print "<tr><td class=text><b>E-Mail: &nbsp;&nbsp;</b></td><td class=text><a href=\"mailto:";
                    eval("print \$info[\$i]['mail'][0];"); #print email address for hyperlink
                    print "\"><font color='blue'>";
                    eval("print \$info[\$i]['mail'][0];"); #print email address for viewing
                    print "</font></a></td></tr>";
                }
                print "</table>";
                break;
//** No returns on Employee ID.  Check against Common Name, Email, UCD Login ID, employeenumber **//
            case 0:
                $cn=" ".$ldap_request." ";
                $cn = preg_replace ("/ /", "*", $cn);
                $filter="(|(cn=$cn)(mail=$ldap_request)(uid=$ldap_no_aster)(employeenumber=$ldap_no_aster))";
                $justthese = array("cn", "mail", "uid", "telephonenumber", "postaladdress", "ou", "title", "employeenumber");
                $r=ldap_bind($ds); #bind to directory
                $sr=@ldap_search($ds,"ou=University of California Davis,o=University of California,c=US", $filter,$justthese,0,300);

                switch(true)
                {

// ** If only one result, return in detail mode **//
                    case ldap_count_entries($ds,$sr)==1:
                        $info = ldap_get_entries($ds, $sr); #get the result
                        print"<table cellpadding=1 cellspacing=1 align='center'>";
                        for ($i=0; $i<$info["count"]; $i++)
                        {  #loop though ldap search result
                            print "<tr><td class=text><b>Name: &nbsp;&nbsp;</b></td><td class=text>";
                            eval("print \$info[\$i]['cn'][0];"); #print attribute value
                            print "</td></tr>";
                            print "<tr><td class=text><b>Title: &nbsp;&nbsp;</b></td><td class=text>";
                            eval("print \$info[\$i]['title'][0];"); #print attribute value
                            print "</td></tr>";
                            print "<tr><td class=text><b>Login ID: &nbsp;&nbsp;</b></td><td class=text>";
                            eval("print \$info[\$i]['uid'][0];"); #print attribute value
                            print "</td></tr>";
                            print "<tr><td class=text><b>Address: &nbsp;&nbsp;</b></td><td class=text>";
                            eval("print \$info[\$i]['postaladdress'][0];"); #print attribute value
                            print "</td></tr>";
                            print "<tr><td class=text><b>Department: &nbsp;&nbsp;</b></td><td class=text>";
                            eval("print \$info[\$i]['ou'][0];"); #print attribute value
                            print "</td></tr>";
                            print "<tr><td class=text><b>Telephone: &nbsp;&nbsp;</b></td><td class=text>";
                            eval("print \$info[\$i]['telephonenumber'][0];"); #print attribute value
                            print "</td></tr>";
                            print "<tr><td class=text><b>E-Mail: &nbsp;&nbsp;</b></td><td class=text><a href=\"mailto:";
                            eval("print \$info[\$i]['mail'][0];"); #print email address for hyperlink
                            print "\"><font color='blue'>";
                            eval("print \$info[\$i]['mail'][0];"); #print email address for viewing
                            print "</font></a></td></tr>";
                        }
                        print "</table>";
                        break;


// ** If more than one result, sort results by lastname,firstname and return as LIST **//
                    case ldap_count_entries($ds,$sr)>1:

                    // SORTING
                        function aSortBySecondIndex($multiArray, $secondIndex)
                        {
                            while (list($firstIndex, ) = each($multiArray))
                                $indexMap[$firstIndex] = $multiArray[$firstIndex][$secondIndex];
                            asort($indexMap);
                            while (list($firstIndex, ) = each($indexMap))
                                if (is_numeric($firstIndex))
                                    $sortedArray[] = $multiArray[$firstIndex];
                                else $sortedArray[$firstIndex] = $multiArray[$firstIndex];
                            return $sortedArray;
                        }

                        $info = ldap_get_entries($ds, $sr); #get the result
                        $info = aSortBySecondIndex($info,"sn");

// ** Data sorted, return as list with "Details" hyperlink to see individuals **//
                        print"
					<table width=500 align='center' callpadding=1 cellspacing=1>
					
					<tr><td class='text'><b>Name</b></td><td class='text'><b>UCD ID</b></td><td class='text'><b>Email</b></td><td></td></tr>";			
                        for ($i=0; $i<$info["count"]; $i++)
                        {  #loop though ldap search result
                            print"<tr>";
                            $detail=$info[$i]['employeenumber'][0];
                            print "<td class='text'>";
                            eval("print \$info[\$i]['cn'][0];"); #print attribute value
                            print "&nbsp;</td>";
                            print "<td class='text'>";
                            eval("print \$info[\$i]['uid'][0];"); #print attribute value
                            print "&nbsp;</td>";
                            print "<td class='text'>";
                            eval("print \$info[\$i]['mail'][0];"); #print attribute value
                            print "&nbsp;</td>";

                            //print "<td class='text'><a href='ucd_directory.php?ldap_request=$detail'><font color='blue'>[Detail]</font></a></td></tr>";
                            print "</tr>";
                        }
                        print "</table><BR><BR>";
                        break;

// ** if there has been a search, but no matches... **//
                    case $ldap_request:
                        print"<div class='text' align='center'>No matches were found.</div>";
                        break;
                }
        }





        ldap_close($ds);

    } else
    {
        echo "<h4>Unable to connect to LDAP
server</h4>";
    }
}
