// JavaScript Document
/*
FormCheck.js - Provides for input validation
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2010 Stephen Lawrence Jr.

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


function checkforblanks()
{
    for (var i = 0; i < arguments.length; i += 2)
    {
        if (!arguments[i])
        {
            alert("Please enter " + arguments[i+1] + ".");
            return false;
        }
    }
    return true;
}


function validateEmail(FormName){
    /************************************************
DESCRIPTION: Validates that a string contains a 
  valid email pattern. 
  
 PARAMETERS:
   strValue - String to be tested for validity
   
RETURNS:
   True if valid, otherwise false.
   
REMARKS: Accounts for email with country appended
  does not validate that email contains valid URL
  type (.com, .gov, etc.) or valid country suffix.
*************************************************/
    var strValue = FormName.Email.value;

    var objRegExp  = /^([a-zA-Z0-9_\.\-\&])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
 
    //check for valid email
    if(!objRegExp.test(strValue))
    {
        alert("E-mail address contain invalid format");
        //field.focus();
        //field.select();
        return false;
    }
    else
    {
        return true;
    }
}

function validatePhone(FormName) {
    /************************************************
DESCRIPTION: Validates that a string contains valid
  US phone pattern. 
  Ex. 999 9999999 or 9999999999 or (999) 999-9999
  
PARAMETERS:
   strValue - String to be tested for validity
   
RETURNS:
   True if valid, otherwise false.
*************************************************/
    var strValue = FormName.phonenumber.value;
    var objRegExp  = /^[1-9]\d{2}\s?\d{3}\d{4}$/;
    var obj2RegExp  = /^\([1-9]\d{2}\)\s?\d{3}\-\d{4}$/;
 
    //check for valid us phone with or without space between
    //area code
    if(objRegExp.test(strValue) || obj2RegExp.test(strValue))
    {
        return true;
    }
    else
    {
        alert("Phone number is in valid must in the form of 999 9999999 or (999) 999-9999");
        // field.focus();
        //field.select();
        return false;
    }
}

function validatePassword(FormName){
    /***************************************************
DESCRIPTION: Validates the two password string for equality 

PARAMETERS: Str1 - String of the password field in the html form name
			Str2 - String of the confirm password field (form name)
			
RETURNS:
	True if valid, otherwise false
************************************************/

    if(FormName.password.value == "")
    {
        alert("Password field cannot be empty");
        return false;
    }
    if(FormName.password.value == FormName.conf_password.value)
    {
        return true;
    }
    else
    {
        alert("Password and Confirm Password fields do not match, Please check it again.");
        return false;
    }
	
}
/***************************************
add or remove fields to check for certain fields are empty or not
the best way is to find fixed amount of field to check that way this file can be reused 

****************************************/

function isEmpty(FormName)
{
    var isFull = checkforblanks(FormName.last_name.value, "Last Name", FormName.first_name.value, "First Name",
        FormName.username.value, "User Name");
    if(isFull)
    {
        return true;
    }
    else
    {
        return false;
    }
}


/****************************************************************
function that validate every field that the functions above uses
function returns true if all the functions are pass without a problem
other wise false will returned;
****************************************************************/
function validate(FormName){
    if(validatePassword(FormName) && validatePhone(FormName) && validateEmail(FormName) && isEmpty(FormName))
    {
        return true;
    }
    else
    {
	
        return false;
    }
}

function validatemod(FormName)
{
	
    if(/*validatePhone(FormName) && */validateEmail(FormName) && validatePassword(FormName))
    {
        if(isEmpty(FormName))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }

}

/********************************************
field checking for department and category
********************************************/


function checkdepart(FormName)
{
    if(!FormName.department.value)
    {
        alert("Department Can Not Be Empty");
        return false;
    }
    else
    {
        return true;
    }
}

function checkcategory(FormName)
{
    if(!FormName.category.value)
    {
        alert("Category Can Not Be Empty");
        return false;
    }
    else
    {
        return true;
    }
}

function checkforempty($var)
{
    if(!$var)
    {
        alert("Error, Field Can Not Be Empty");
        return false;
    }
    else
    {
        return true;
    }
}

