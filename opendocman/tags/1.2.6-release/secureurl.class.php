<?php
/****
* Titre........... : PHPSecureURL
* Description..... : Hide URL parameter to secure PHP pages
* version......... : 0.51
* date............ : april 16 2003
* file............ : phpsecureurl.pclass
* Auteur.......... : Pascal CASENOVE  pascal@cawete.com
*
* licence......... : The GNU General Public License (GPL) 
*					 http://www.opensource.org/licenses/gpl-license.html
*
* changement...... : 0.51 the first publishing version
* A faire......... : Waitting for suggest
* 					 
* Suggestion...... : don�t hesitate� CONTACT ME !!
*					 pascal@cawete.com
*
* Description  ... : With PHP, to pass parameters between two pages you can use something like this
* 					 http://www.monsite.net/mapage.php?parametre1=123&amp;user=456
*  					 A visitor can modify thoses parameters just to see
*   				 or to test parameters. 
*  					 Example he modify user parameter and he see the account of another user.
*					 This classes hide parameters. You don't have to modify your code.
*   				 You have just to add some lines and your link become somethig like
*					 http://www.monsite.net/mapage.php?aku=DFgh4hfdg4454fgHFGHfg44fghfg4
*
* remarques ...... : it not use cryptography. 
* 					 It just use a difficult read format
*
* Methodes........ : **encode($var) return the hide URL with original URL in parameter.
*
*                    **decode($var) internal methode
*						
*			
* Proprietes...... : **decode_url URL unhide 
*
* Parametres...... : $var_name the name of the parameter transmit by the URL default aku
*								
****/
		
/*******************************************************************
*
*    class 
*
********************************************************************/
require_once('odm-load.php');
class phpsecureurl{

	var $var_name="aku"; 			// you hide all your URL in thid parmeter "aku" is an example you can redefine
	var $decode_url;				// url unhide
	function code_param_url(){
		$this->decode();
	}
//*******************************************
	function encode($url){ 			// methode to encode $url = par1=toto&par2=tioti& ...
		if($GLOBALS['CONFIG']['secureurl'] == 'True')
		{
			$pos_debut=strpos($url,"?"); if(!$pos_debut){$sep="&";}
			//$pos_fin=strpos($url," ");
			//if($pos_fin){
			//	$pos_long=$pos_fin-$pos_debut-1; $fin=substr($url,$pos_fin); 
			//}else{
				$pos_long=strlen($url)-$pos_debut-1;
			//}
			$debut=substr($url,0,$pos_debut+1);
			$param=substr($url,$pos_debut+1,$pos_long);
			$code = base64_encode($param);
			return $debut.@$sep.$this->var_name."=".$code.@$fin;
		}
		else
		{
			return $url;
		}
	} // methode return ?aku=dfgdfgdgdfgdgdfhgjdfhjghj all parameter are hide in one

	// methode returm something like ?aku=dfgdfgdgdfgdgdfhgjdfhjghj all parameters un one
	// $url can be 				"http://www.moserveur.com/monfichier.php?var1=dfdf& var2=fdsgdf&var3=dfg "
	// or 						"http://www.moserveur.com/monfichier.php?var1=dfdf& var2=fdsgdf&var3=dfg target=_self"
	// or only					"?var1=dfdf& var2=fdsgdf&var3=dfg"
//*********************************************

//**************** decode and change global variables
	function decode (){ // methode to unhide
			$_SERVER['QUERY_STRING'] = '';
		
		if(isset($_REQUEST[$this->var_name])){ 
			$this->decode_url=base64_decode($_REQUEST[$this->var_name]); 
			parse_str($this->decode_url, $tbl); 
			foreach($tbl as $k=>$v){
				$_REQUEST[$k]=$v;
				$_GET[$k]=$v;
				$_SERVER['QUERY_STRING'] .= "$k=$v&";
				global $$k; 
				$$k=$v;
			}
		} 
	}
//*********************************
}


?>
