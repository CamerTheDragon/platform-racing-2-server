<?php

require_once GEN_HTTP_FNS;
require_once HTTP_FNS . '/output_fns.php';
require_once HTTP_FNS . '/pages/admin/contests/add_contest_fns.php';
require_once QUERIES_DIR . '/admin_actions.php';
require_once QUERIES_DIR . '/contests.php';

$ip = get_ip();
$action = default_post('action', 'form');

try {
    // rate limiting
    rate_limit('add-contest-'.$ip, 30, 5);
    rate_limit('add-contest-'.$ip, 5, 2);

    // connect
    $pdo = pdo_connect();

    // make sure you're an admin
    $admin = check_moderator($pdo, null, true, 3);

    // build page
    if ($action === 'form') {
        output_header('Add Contest', true, true);
        output_form();
        output_footer();
    } elseif ($action === 'add') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Invalid request type.');
        }
        add_contest($pdo, $admin);
    } else {
        throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    output_header('Error');
    echo 'Error: ' . $e->getMessage() . '<br><br><a href="javascript:history.back()"><- Go Back</a>';
    output_footer();
}
