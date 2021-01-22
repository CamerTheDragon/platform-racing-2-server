<?php


// tell a user if there's currently a tournament
function tournament_status($requester)
{
    if (\pr2\multi\PR2SocketServer::$tournament) {
        $hat_name = \pr2\multi\Hats::idToStr(\pr2\multi\PR2SocketServer::$tournament_hat);
        $hat_name .= $hat_name != 'None' ? ' Hat' : '';
        $requester->write(
            'systemChat`Tournament mode is on!<br/>'
            ."Hat: $hat_name<br/>"
            .'Speed: ' . \pr2\multi\PR2SocketServer::$tournament_speed . '<br/>'
            .'Accel: ' . \pr2\multi\PR2SocketServer::$tournament_acceleration . '<br/>'
            .'Jump: ' . \pr2\multi\PR2SocketServer::$tournament_jumping
        );
    } else {
        $requester->write('systemChat`Tournament mode is off.');
    }
}


// announce a tournament to the chat
function announce_tournament($chat)
{
    if (\pr2\multi\PR2SocketServer::$tournament) {
        $hat_name = \pr2\multi\Hats::idToStr(\pr2\multi\PR2SocketServer::$tournament_hat);
        $hat_name .= $hat_name != 'None' ? ' Hat' : '';
        $chat->sendToAll(
            'systemChat`Tournament mode is on!<br/>'
            ."Hat: $hat_name<br/>"
            .'Speed: ' . \pr2\multi\PR2SocketServer::$tournament_speed . '<br/>'
            .'Accel: ' . \pr2\multi\PR2SocketServer::$tournament_acceleration . '<br/>'
            .'Jump: ' . \pr2\multi\PR2SocketServer::$tournament_jumping
        );
    } else {
        $chat->sendToAll('systemChat`Tournament mode is off.');
    }
}


// issue a tournament to the server
function issue_tournament($str)
{
    // if nothing was passed to the function, disable tournament mode
    if (is_empty($str)) {
        \pr2\multi\PR2SocketServer::$tournament = false;
    } else {
        $arr = explode(' ', $str); // add an array item on every space
        array_shift($arr); // don't include /t in the array

        // did the user say /t on? if so, let's ignore 'on' and handle the rest of the data as normal (if any)
        if (isset($arr[0]) && strtolower($arr[0]) === 'on') {
            // take 'on' out of the array
            array_shift($arr);

            // if they didn't give any values after "on", start the default tournament
            if (!isset($arr[0]) || trim(strtolower($str)) === '/t on') {
                $arr[0] = "None";
                $arr[1] = "65";
                $arr[2] = "65";
                $arr[3] = "65";
            }
        }

        // apply the requested change
        if (is_empty($arr[0]) || strtolower($arr[0]) === 'off') { // /t off?
            \pr2\multi\PR2SocketServer::$tournament = false;
        } else { // not /t off
            if (isset($arr[0]) && isset($arr[1]) && isset($arr[2]) && isset($arr[3]) && !isset($arr[4])) {
                \pr2\multi\PR2SocketServer::$tournament = true;

                // make array values easier to work with
                $hat = $arr[0];
                $speed = (int) $arr[1];
                $accel = (int) $arr[2];
                $jump = (int) $arr[3];
            } else {
                $e = 'It looks like you left out a stat value or the hat name, or added too many arguments.';
                throw new Exception($e);
            }
        }

        // if tournament mode was determined to be on, let's set the values
        if (\pr2\multi\PR2SocketServer::$tournament) {
            if (isset($hat)) {
                if (\pr2\multi\Hats::strToId($hat) <= 16) {
                    \pr2\multi\PR2SocketServer::$tournament_hat = limit(\pr2\multi\Hats::strToId($hat), 1, 16);
                } else {
                    throw new Exception('The hat you entered isn\'t a valid hat name.');
                }
            }
            if (isset($speed)) {
                if ($speed >= 0 && $speed <= 100) {
                    \pr2\multi\PR2SocketServer::$tournament_speed = limit($speed, 0, 100);
                } else {
                    throw new Exception('Stat values can only be between 0 and 100.');
                }
            }
            if (isset($accel)) {
                if ($accel >= 0 && $accel <= 100) {
                    \pr2\multi\PR2SocketServer::$tournament_acceleration = limit($accel, 0, 100);
                } else {
                    throw new Exception('Stat values can only be between 0 and 100.');
                }
            }
            if (isset($jump)) {
                if ($jump >= 0 && $jump <= 100) {
                    \pr2\multi\PR2SocketServer::$tournament_jumping = limit($jump, 0, 100);
                } else {
                    throw new Exception('Stat values can only be between 0 and 100.');
                }
            }
        }
    }
}
