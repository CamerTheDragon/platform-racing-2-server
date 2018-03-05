<?php

require_once('../fns/output_fns.php');
require_once('../fns/all_fns.php');

$name = find_no_cookie('name', '');
$ip = get_ip();

output_header("Player Search");

try {

	// sanity check: check for a name
	if (is_empty($name)) {
		throw new Exception("");
	}
	
	// rate limiting
	rate_limit('gui-player-search-'.$ip, 5, 1, 'Wait a bit before searching again.');
	rate_limit('gui-player-search-'.$ip, 30, 5, 'Wait a bit before searching again.');
	
	// connect
	$db = new DB();
	
	// get id from name
	$user_id = name_to_id($db, $name);
	
}
catch (Exception $e) {
	$error = $e->getMessage();
	output_search($name);
	if($error != '') {
		output_error($error);
	}
	output_footer();
	die();
}

try {
	
	// output functions
	output_search($name);
	output_page($db, $user_id);
	output_footer();
	
	// seeya
	die();
	
}
catch (Exception $e) {
	$error = $e->getMessage();
	output_error($error);
	output_footer();
	die();
}

function output_search($name='') {

	// safety first
	$safe_name = htmlspecialchars($name);
	
	echo '<center><font face="Gwibble" class="gwibble">-- Player Search --</font><br><br>';
	
	echo "<form method='get'>
	Username: <input type='text' name='name' value='$safe_name'>
	<input type='submit' value='Search'>
	</form>";

}

function output_error($error='') {
	$error = "<br><i>Error: $error</i>";
	if (!is_empty($error)) {
		echo $error;
	}
}

function output_page($db, $user_id) {
	
	// prepare the user id
	$user_id = (int) $user_id;
	
	// get player info from id
	$user = $db->grab_row('user_select_expanded', array($user_id));
	
	// sanity check: is the used tokens value set?
	if (!isset($user->used_tokens)) {
		$user->used_tokens = 0;
	}

	// group arrays
	$group_colors = ['7e7f7f', '047b7b', '1c369f', '870a6f'];
	$group_names = ['Guest', 'Member', 'Moderator', 'Admin'];

	// make some variables
	$user_name = $user->name; // name
	$group = (int) $user->power; // group
	$group_color = $group_colors[$group];
	$group_name = $group_names[$group];
	$status = $user->status; // status
	$guild_id = (int) $user->guild; // guild id
	$rank = (int) ($user->rank + $user->used_tokens); // rank
	$hats = (int) (count(explode(',', $user->hat_array)) - 1); // hats
	$login_date = date('j/M/Y', $user->time); // active
	$register_date = date('j/M/Y', $user->register_time); // joined
	
	// aoh check
	if ($register_date == '1/Jan/1970') {
		$register_date = "Age of Heroes";
	}
	
	// guild id to name
	if ($guild_id !== 0) {
		$guild = $db->grab_row('guild_select', array($guild_id));
		$guild_name = $guild->guild_name;
	}
	else {
		$guild_name = '';
	}
	
	// group html change if staff
	if ($group >= 2) {
		$group_name = "<a href='https://pr2hub.com/staff.php' style='color: #000000; font-weight: bold'>$group_name</a>";
	}
	
	// safety first
	$safe_name = htmlspecialchars($user_name);
	$safe_status = htmlspecialchars($status);
	$safe_guild = htmlspecialchars($guild_name);
	
	// --- Start the Page --- \\
	
	echo "<br><br>";
	
	echo "-- <font style='color: #$group_color; text-decoration: underline; font-weight: bold'>$safe_name</font> --<br><br>";
	
	echo "<i>$safe_status</i><br><br>";
	
	echo "Group: $group_name<br>";
	echo "Guild: $safe_guild<br>";
	echo "Rank: $rank<br>";
	echo "Hats: $hats<br>";
	echo "Joined: $register_date<br>";
	echo "Active: $login_date";
	
	echo "</center>";
	
}

?>
