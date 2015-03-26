<?php

// ldap-users.inc
//
// Authenticate/create/modify users against LDAP

// Function ldap_login() - Takes username/password and tries to bind.
// Return user name on success, FALSE on failure.
function ldap_login($user, $pass) {
	$ldap_host = $GLOBALS['CONFIG']['ldap_host'];
	$ldap_port = $GLOBALS['CONFIG']['ldap_port'];
	$base_dn = $GLOBALS['CONFIG']['base_dn'];
	$searchfilter = $GLOBALS['CONFIG']['searchfilter'];
	$bind_dn = $GLOBALS['CONFIG']['bind_dn'];
	$bind_pw = $GLOBALS['CONFIG']['bind_pw'];

	$ldap_conn = ldap_connect($ldap_host, $ldap_port);
	if(!$ldap_conn) {
		error_log("ldap_connect() failed.");
		return FALSE;
	}

	if(!ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3) ||
		!ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0)) {
		error_log("ldap_set_options() failed.");
		return FALSE;
	}

	if (!ldap_bind($ldap_conn, $bind_dn, $bind_pw)) {
		error_log("ldap_bind() failed.");
		return FALSE;
	}

	// Return only one match (there can be only one... ;)
	$match_count = 1;
	// Replace provided filter term's %uid field with the user name
	$this_filter = str_replace("%uid", $user, $searchfilter, $match_count);
	if(empty($this_filter)) {
		error_log("Error in searchfilter with str_replace(), got $this_filter");
		return FALSE;
	}

	// We only need the uid attribute (for now...)
	$attribute = array("uid");
	$search_results = @ldap_search($ldap_conn, $base_dn, $this_filter, $attribute, 0, 2);
	if (!$search_results) {
		error_log("ldap_search() failed.");
		return FALSE;
	}

	if (ldap_count_entries($ldap_conn, $search_results) != 1) {
		error_log("ldap_search() returned $search_results - uid not unique.");
		return FALSE;
	}

	$user_entry = ldap_first_entry($ldap_conn, $search_results);
	if (!$user_entry) {
		error_log("ldap_get_first_entry() failed.");
		return FALSE;
	}
	
	$this_dn = ldap_get_dn($ldap_conn, $user_entry);
	if (!$this_dn) {
		error_log("ldap_get_dn() failed.");
		return FALSE;
	}

	$user_bind = ldap_bind($ldap_conn, $this_dn, $pass);
	if (!$user_bind) {
		return FALSE;
	}

	ldap_close($ldap_conn);

	return $user;
}

// Function add_or_modify() - if an LDAP user logs in but doesn't exist
// in the ODM database, create a user there.  If they exist in the ODM
// database, make sure LDAP password is synchronized (pushed to)
// the database for consistency.
function add_or_modify($username, $password) {
	// Check to make sure user does not already exist
	$pdo = $GLOBALS['pdo'];
	$query = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = '$username'";
	
	if ($count = $pdo->query($query)) {
		if ($count->fetchColumn() == 1) {
			update_password($username, $password);
			return;
	} elseif ($count->fetchColumn() == 0) {
		create_user($username, $password);
		return;
	}
	else {
		error_log("Database error - username not unique.");
	}
	}

    return;
}

// Function update_passwd() - if user exists in ODM database,
// synchronize (push) the LDAP password to ODM.
function update_password($username, $password) {
	$pdo = $GLOBALS['pdo'];
	$query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user
		SET password=md5(:password) WHERE username=:username";
	$stmt = $pdo->prepare($query);
	$stmt->execute(array(':username' => $username, ':password' => $password));

	return;
}

// Function create_user() - If LDAP user does not exist in ODM,
// add them.
function create_user($username, $password) {
	$pdo = $GLOBALS['pdo'];

	// These are just temporary defaults -
	// will add attempts to get from LDAP, or 
	// configurable defaults later.
	$department = 1;
	$phonenumber = "000-000-0000";
	$email = "";
	$lastname = "";
	$firstname = "";
	$can_add = 0;
	$can_checkin = 0;

	$query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user
		(username, password, department, phone, Email, last_name, first_name, can_add, can_checkin)
		VALUES(
			:username,
			md5(:password),
			:department,
			:phonenumber,
			:email,
			:lastname,
			:firstname,
			:can_add,
			:can_checkin
		)";

	$stmt = $pdo->prepare($query);
	$stmt->execute(array(
		':username' => $username,
		':password' => $password,
		':department' => $department,
		':phonenumber' => $phonenumber,
		':email' => $email,
		':lastname' => $lastname,
		':firstname' => $firstname,
		':can_add' => $can_add,
		':can_checkin' => $can_checkin
	));

	$user_id = $pdo->lastInsertId();;
	$admin = 0;
	$query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}admin (id, admin) VALUES(:user_id, :admin)";
	$stmt = $pdo->prepare($query);
	$stmt->execute(array(':user_id' => $user_id, ':admin' => $admin));

	return;
}

?>
