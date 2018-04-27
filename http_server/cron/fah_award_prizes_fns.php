<?php

function add_prizes($pdo, $name, $score, $prize_array, $processed_names)
{
    $lower_name = strtolower($name);
    
    if (!isset($processed_names[$lower_name])) {
        $processed_names[$lower_name] = 1;

        try {
            if (isset($prize_array[$lower_name])) {
                $row = $prize_array[$lower_name];
                $user_id = $row->user_id;
                $status = $row->status;
            } else {
                $user = user_select_by_name($pdo, $name);

                // make some variables
                $user_id = $user->user_id;
                $status = $user->status;

                folding_insert($pdo, $user_id);
                $row = folding_select_by_user_id($pdo, $user_id);
            }

            if ($status != 'offline') {
                throw new Exception("$name is \"$status\". Abort mission! We'll try again later.");
            }
            
            // --- ensure awards and give new ones --- \\
            
            // get information from pr2, rank_tokens, and folding_at_home
            $hat_array = explode(',', pr2_select($pdo, $user_id)->hat_array);
            $available_tokens = (int) rank_token_select($pdo, $user_id)->available_tokens;
            
            // define columns
            $columns = array(
                        'r1' => array('token' => 1, 'min_score' => 1),
                        'r2' => array('token' => 2, 'min_score' => 500),
                        'r3' => array('token' => 3, 'min_score' => 1000),
                        'crown_hat' => array('hat' => 'crown', 'min_score' => 5000),
                        'cowboy_hat' => array('hat' => 'cowboy', 'min_score' => 100000),
                        'r4' => array('token' => 4, 'min_score' => 1000000),
                        'r5' => array('token' => 5, 'min_score' => 10000000)
                        );
            
            // get number of folded tokens/hats
            $token_awards = array();
            $award_crown = false;
            $award_cb = false;
            foreach ($columns as $column => $data) {
                // sanity check: is the score less than the min_score?
                if ($data['min_score'] > $score) {
                    continue;
                }
                // determine the column to check
                if (strpos($column, 'r') === 0) {
                    array_push($token_awards, $data);
                } elseif ($column == 'crown_hat') {
                    $award_crown = true;
                } elseif ($column == 'cowboy_hat') {
                    $award_cb = true;
                }
            }
            
            // award tokens
            if ($available_tokens !== count($token_awards)) {
                foreach ($token_awards as $column) {
                    award_token($pdo, $user_id, $name, $score, $column);
                }
            }

            // award crown hat
            $has_crown = in_array('6', $hat_array);
            if (($award_crown === true || $score > 5000) && $has_crown === false) {
                award_hat($pdo, $user_id, $name, $score, 'crown');
            }
            
            // award cowboy hat
            $has_cb = in_array('5', $hat_array);
            if (($award_cb === true || $score > 100000) && $has_cb === false) {
                award_hat($pdo, $user_id, $name, $score, 'cowboy');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            $safe_error = htmlspecialchars($error);
            output($safe_error);
        }
    }
}



function award_token($pdo, $user_id, $name, $score, $column)
{
    $token_num = $column['token'];
    $column = "r$token_num";
    
    try {
        // verify that the correct amount of points has been folded for this prize
        if (($column == 'r1' && $score < 1)
            || ($column == 'r2' && $score < 500)
            || ($column == 'r3' && $score < 1000)
            || ($column == 'r4' && $score < 1000000)
            || ($column == 'r5' && $score < 10000000)
        ) {
            throw new Exception("$name ($user_id): Insufficient score ($score) for that folding prize ($column).");
        }
        
        // do it
        output("Awarding $column to $name...");
        rank_token_upsert($pdo, $user_id, $token_num);
        
        // finalize it (send message, mark as awarded in folding_at_home)
        finalize_folding_award($pdo, $user_id, $name, $column);
    } catch (Exception $e) {
        output(htmlspecialchars($e->getMessage()));
    }
}



function award_hat($pdo, $user_id, $name, $score, $hat)
{
    $column = $hat . '_hat';
    
    try {
        // define hat id
        if ($hat == 'crown') {
            $hat_id = 6;
        } elseif ($hat == 'cowboy') {
            $hat_id = 5;
        } // sanity check: is the prize an actual prize?
        else {
            throw new Exception("$name ($user_id): Invalid hat prize ($hat).");
        }
        
        // sanity check: has the correct amount of points been folded for this prize?
        if (($hat == 'crown' && $score < 5000)
            || ($hat == 'cowboy' && $score < 100000)
        ) {
            throw new Exception("$name ($user_id): Insufficient score ($score) for that folding prize ($column).");
        }
        
        // do it
        output("Awarding $column to $name...");
        award_part($pdo, $user_id, 'hat', $hat_id);
        
        // finalize it (send message, mark as awarded in folding_at_home)
        finalize_folding_award($pdo, $user_id, $name, $column);
    } catch (Exception $e) {
        output(htmlspecialchars($e->getMessage()));
    }
}



function finalize_folding_award($pdo, $user_id, $name, $prize_code)
{
    $rt_desc = 'a rank token';
    $prizes = array(
                    'r1' => array($rt_desc, '1 point'),
                    'r2' => array($rt_desc, '500 points'),
                    'r3' => array($rt_desc, '1,000 points'),
                    'r4' => array($rt_desc, '1,000,000 points'),
                    'r5' => array($rt_desc, '10,000,000 points'),
                    'crown_hat' => array('the Crown Hat', '5,000 points'),
                    'cowboy_hat' => array('the Cowboy Hat', '100,000 points')
                );
    
    // compose a PM
    $prize_str = $prizes[$prize_code][0];
    $min_score = $prizes[$prize_code][1];
    $message = "Dear $name,\n\n"
        ."Congratulations on earning $min_score for Team Jiggmin! "
        ."As a special thank you, I've added $prize_str to your account!!\n\n"
        ."Thanks for helping us take over the world (or cure cancer)!\n\n"
        ."- Jiggmin";
    
    // send the folder the composed message
    message_insert($pdo, $user_id, 1, $message, '0');
    
    // remember that this prize has been given
    folding_update($pdo, $user_id, $prize_code);
    
    // output
    output("Finished awarding $prize_code to $name ($user_id).");
}