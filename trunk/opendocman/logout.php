<?php
include ('config.php');

	// If kerbauth, then display warning about shutting down browser

	if($GLOBALS["CONFIG"]["authen"] =='kerbauth')
	{

?>
	<html>
	 <BODY bgcolor="#FFFFFF" link="#000000" vlink="#000000" background="images/background_blue.gif">
	  <TABLE width="633" border="0" cellspacing="0" cellpadding="0">
	   <TR>
  	    <TD width="4">&nbsp;&nbsp;&nbsp;</td>
            <TD>
	     <TABLE border="0" cellpadding="0" cellspacing="0" width="620">
	      <TR>
               <TD width="100%" align="LEFT" BGCOLOR="#31639C"><IMG SRC="images/blue_left.gif" WIDTH="5" HEIGHT="16" ALIGN="top"><FONT face="Arial" size="-1" color="#FFFFFF"><B>&nbsp;Thank you for using OpenDocMan</FONT></B></TD>

	      </TR>
              <TR>
                <TD width="100%" align="LEFT" BGCOLOR="#FFCE31">
                  <IMG SRC="images/logout_logo.gif" align="left"><H2>Logging off...</H2>
                </TD>
              </TR>
	      <TR>
	       <TD align="left">
	 	<IMG src="/images/white_dot.gif" height="8"><BR>
		<FONT face="ARIAL" color="#000000" size="-1">OpenDocMan, and other campus web systems, use a cookie to store your credentials for access.  This cookie is kept only in your computers memory and not saved to disk for security purposes.  In order to remove this cookie from memory you must completely exit your browser.  The LOGOUT button below will close the current browser window, but this may not exit your browser software completely.
		 <P>
		<B>Macintosh Users:</B> Choose 'Quit' from the 'File' menu to be sure the browser is completely exited.       <P>
		<B>PC/Windows Users:</B> Close off all browser windows by clicking the 'X' icon in the upper right of the window.  Be sure all browser windows are closed.
                <P>
	        </font>
		<P>
		<FORM NAME="CM">
		 <FONT face="ARIAL" color="#000000" size="-2">&nbsp;<INPUT TYPE="BUTTON" VALUE="LOGOUT" Onclick="top.close();"></font>
		 <FONT face="ARIAL" color="#000000" size="-2">&nbsp;</font>
	       </TD>
              </TR>
              <TR><TD>
	     </TD>
	    </TR>
         </TABLE>
        </FORM>
<?php	
draw_footer();

	}
	else
	// mysql auth, so just kill session and show login prompt
	{
        	session_start();
        	if(session_unregister('SESSION_UID'))
        	{
			header('Location:index.php');
		}


	}


?>
