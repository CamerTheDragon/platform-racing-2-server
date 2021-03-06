<?php

require_once GEN_HTTP_FNS;
require_once HTTP_FNS . '/output_fns.php';
require_once HTTP_FNS . '/http_data_fns.php';

$ip = get_ip();

try {
    // rate limiting
    rate_limit('gui-hint-' . $ip, 5, 2);

    // get data, make variables
    $decode = json_decode(file_get_contents(WWW_ROOT . "/files/artifact_hint.txt"));
    $safe_hint = htmlspecialchars($decode->hint, ENT_QUOTES);
    $safe_finder = htmlspecialchars($decode->finder_name, ENT_QUOTES);

    output_header('Artifact Hint');

    echo '<center>'
        .'<font face="Gwibble" class="gwibble">-- Artifact Hint --</font>'
        .'<br>'
        .'<img src="img/artifact.png" width="80px" height="70px"></img>'
        .'<br>';

    // echo hint
    echo "Here's what Fred can remember: $safe_hint"
        .'<br>';

    // echo finder if one, not found yet if none
    if (is_empty($decode->finder_name)) {
        echo "<i><b>This artifact hasn't been found yet!</b></i>";
    } else {
        echo "<i>The first person to find this artifact was <b>$safe_finder</b>!!</i>";
    }

    // get last updated time
    $timestamp = (int) (time() - $decode->updated_time);
    $formatted_date = date('M j, Y g:i A', $timestamp);
    $formatted_time = format_duration($timestamp);

    // echo last updated time
    echo '<br><br>'
        ."Fred lost this artifact <span title='$formatted_date'>approximately $formatted_time ago</span>.";

    // seeya
    echo '</center>';
} catch (Exception $e) {
    output_header('Error');
    $error = htmlspecialchars($e->getMessage(), ENT_QUOTES);
    echo "Error: $error";
} finally {
    output_footer();
}
