<?php

//
// ldap-users.inc
//
// Authenticate/create/modify users against LDAP or
// Active Directory.
//
// John E.P. Hynes/HyTronix [03/29/2015]
// 
// john@hytronix.com
// 

// Function ldap_login() - Takes username/password and tries to bind.
function ldap_login($user, $pass) {
	// Load all options from config.php
	$ldap_host = $GLOBALS['CONFIG']['ldap_host'];
        $ldap_port = $GLOBALS['CONFIG']['ldap_port'];
        $base_dn = $GLOBALS['CONFIG']['base_dn'];

	$bind_dn = $GLOBALS['CONFIG']['bind_dn'];
	$bind_pw = $GLOBALS['CONFIG']['bind_pw'];

	$searchfilter = $GLOBALS['CONFIG']['searchfilter'];
	$attributes =  array(
				$GLOBALS['CONFIG']['ldap_username'],
				$GLOBALS['CONFIG']['ldap_department'],
				$GLOBALS['CONFIG']['ldap_phone'],
				$GLOBALS['CONFIG']['ldap_Email'],
				$GLOBALS['CONFIG']['ldap_last_name'] ,
				$GLOBALS['CONFIG']['ldap_first_name']
			);

	// Try to connect to host:port
        $ldap_conn = ldap_connect($ldap_host, $ldap_port);
        if(!$ldap_conn) {
                error_log("ldap_connect() failed.");
                return FALSE;
        }

	// Try to set common LDAP options
        if(!ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3) ||
                !ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0)) {
                error_log("ldap_set_options() failed.");
                return FALSE;
        }

	// Attempt to bind with the bind_dn credentials to search directory
        if (!ldap_bind($ldap_conn, $bind_dn, $bind_pw)) {
                error_log("ldap_bind() failed.");
                return FALSE;
        }

        // Replace all occurences of %uid in user-provided filter with
        // the user name we are searching for. 
        $this_filter = str_replace("%uid", $user, $searchfilter);
        if(empty($this_filter)) {
                error_log("Error in searchfilter with str_replace()");
                return FALSE;
        }

        // Limit search returns - user should be unique, so if we get two, that's one too many...
        $search_results = @ldap_search($ldap_conn, $base_dn, $this_filter, $attributes, 0, 2);
        if (!$search_results) {
                error_log("ldap_search() failed.");
                return FALSE;
        }

        if (ldap_count_entries($ldap_conn, $search_results) != 1) {
                error_log("ldap_search() returned $search_results - uid not unique.");
                return FALSE;
        }

	// Fill user_entry array with whatever was found out of what we asked for
        $user_entry = ldap_get_entries($ldap_conn, $search_results);
        if (!$user_entry) {
                error_log("ldap_get_entries() failed.");
                return FALSE;
        }

	// Can't see how dn could be omitted, but check anyway and use it for user bind
	$this_dn = $user_entry[0]["dn"];
	if (!$this_dn) {
		error_log("Attribute dn in user entry did not exist.");
		return FALSE;
	}

	// If this succeeds, the user/pass logging in is correct
	$user_bind = ldap_bind($ldap_conn, $this_dn, $pass);
	if (!$user_bind) {
		return FALSE;
	}

	// We're done with the LDAP connection now
	ldap_close($ldap_conn);

	// If we're here, there's a valid LDAP account
	add_or_modify($user, $pass, $user_entry);

	return;
}

// Function add_or_modify() - if an LDAP user logs in but doesn't exist
// in the ODM database, create a user there.  If they exist in the ODM
// database, make sure LDAP data is synchronized (pushed to)
// the database for consistency.
function add_or_modify($username, $password, $user_entry) {
	// Check to see if user exists in ODM DB - get row count for user name
	$pdo = $GLOBALS['pdo'];
	$query = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = '$username'";
	
	if ($count = $pdo->query($query)) {
		// If user exists in ODM DB, update with data from LDAP
		if ($count->fetchColumn() == 1) {
			update_user($username, $password, $user_entry);
			return;
		// If user does not exist in ODM DB, create a new user there
		} elseif ($count->fetchColumn() == 0) {
			create_user($username, $password, $user_entry);
			return;
		}
	} else {
		// Something's wrong if we're here...
                error_log("Database error - username not unique or query failed.");
	}

    return;
}

// Function update_user() - if user exists in ODM database,
// synchronize (push) the LDAP data to ODM.
function update_user($username, $password, $user_entry) {
	// Build UPDATE query from $user_entry and execute it
	$query_result = build_execute_update_query($username, $password, $user_entry);
	if (!$query_result) {
		error_log("build_execute_update_query failed.");
		return FALSE;
	}

	return;
}

// Function create_user() - If LDAP user does not exist in ODM, add them.
function create_user($username, $password, $user_entry) {
	// Build INSERT query from $user_entry and execute it
	$query_result = build_execute_insert_query($username, $password, $user_entry);
	if(!$query_result) {
		error_log("build_execute_insert_query() failed.");
		return FALSE;
	}

	return;
}

// function build_execute_insert_query() - use configured attributes for creating a new
// ODM user from LDAP data, and build the INSERT query string values and execute it
function build_execute_insert_query($username, $password, $user_entry) {
	$pdo = $GLOBALS['pdo'];

	// The first two are mandatory...
        $insert = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user (username, password";
        $values = "VALUES(:username, md5(:password)";
	$query_array = array(
                        ':username' => $username,
                        ':password' => $password,
                        );

	// Map LDAP attributes specified in config.php to ODM DB fields
	if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_department']])) {
		$department     = $user_entry[0][$GLOBALS['CONFIG']['ldap_department']][0];
	} else {
		// Default department number to ldap_default_dept
		$department = $GLOBALS['CONFIG']['ldap_default_dept']; 
	}
	$insert         = $insert . ", " . "department";
	$values         = $values . ", " . ":department";
	$query_array[':department']		= $department;

	if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_phone']])) {
                $phone		= $user_entry[0][$GLOBALS['CONFIG']['ldap_phone']][0];
		$insert         = $insert . ", " . "phone";
                $values         = $values . ", " . ":phone";
		$query_array[':phone']		= $phone;
        }
	if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_Email']])) {
                $Email		= $user_entry[0][$GLOBALS['CONFIG']['ldap_Email']][0];
		$insert         = $insert . ", " . "Email";
                $values         = $values . ", " . ":Email";
		$query_array[':Email']		= $Email;
        }
	if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_last_name']])) {
                $last_name	= $user_entry[0][$GLOBALS['CONFIG']['ldap_last_name']][0];
		$insert         = $insert . ", " . "last_name";
                $values         = $values . ", " . ":last_name";
		$query_array[':last_name']	= $last_name;
        }
	if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_first_name']])) {
                $first_name	= $user_entry[0][$GLOBALS['CONFIG']['ldap_first_name']][0];
		$insert         = $insert . ", " . "first_name";
                $values         = $values . ", " . ":first_name";
		$query_array[':first_name']	= $first_name;
        }

	// The last two are mandatory too.
	$insert = $insert . ", can_add, can_checkin) ";
	$values = $values . ", :can_add, :can_checkin)";
	$query_array[':can_add']	= $GLOBALS['CONFIG']['ldap_can_add'];
        $query_array[':can_checkin']	= $GLOBALS['CONFIG']['ldap_can_checkin'];

	// Assemble query
	$query = $insert . $values;

	// Prepare and execute, bail if it fails	
	$stmt = $pdo->prepare($query);
	$stmt->execute($query_array);

	if(!$stmt) {
		error_log("INSERT query failed.");
		return FALSE;
	}

	// Add user ID and FALSE to the "is admin?" table
	$user_id = $pdo->lastInsertId();;
        $admin = 0;
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}admin (id, admin) VALUES(:user_id, :admin)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':user_id' => $user_id, ':admin' => $admin));

        return $stmt;
}

// function build_execute_update_query() - use configured attributes for updating the 
// ODM user from LDAP data, and build the UPDATE query string values and execute it
function build_execute_update_query($username, $password, $user_entry) {
	$pdo = $GLOBALS['pdo'];
       
	$update = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET
			password=md5(:password)";
	$query_array = array(
                        ':username' => $username,
                        ':password' => $password,
                        );

	if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_department']])) {
                $department     = $user_entry[0][$GLOBALS['CONFIG']['ldap_department']][0];
        } else {
                // Default department number to ldap_default_dept
                $department = $GLOBALS['CONFIG']['ldap_default_dept'];
        }
        	$update		= $update . ", " . "department=:department";
		$query_array[':department']    	= $department;
        if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_phone']])) {
                $phone          = $user_entry[0][$GLOBALS['CONFIG']['ldap_phone']][0];
                $update		= $update . ", " . "phone=:phone";
		$query_array[':phone']         	= $phone;
        }
        if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_Email']])) {
                $Email          = $user_entry[0][$GLOBALS['CONFIG']['ldap_Email']][0];
                $update		= $update . ", " . "Email=:Email";
		$query_array[':Email']         	= $Email;
        }
        if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_last_name']])) {
                $last_name      = $user_entry[0][$GLOBALS['CONFIG']['ldap_last_name']][0];
                $update		= $update . ", " . "last_name=:last_name";
		$query_array[':last_name']	= $last_name;
        }
        if(isset($user_entry[0][$GLOBALS['CONFIG']['ldap_first_name']])) {
                $first_name     = $user_entry[0][$GLOBALS['CONFIG']['ldap_first_name']][0];
                $update		= $update . ", " . "first_name=:first_name";
		$query_array[':first_name']	= $first_name;
	}

	$update = $update . " WHERE username=:username";

        $stmt = $pdo->prepare($update);
        $stmt->execute($query_array);

	return $stmt;
}

?>
