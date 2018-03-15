<?php

function guild_increment_gp($pdo, $guild_id, $gp)
{
	$stmt = $pdo->prepare('
        UPDATE guilds
        SET gp_today = gp_today + p_gp,
            gp_total = gp_total + p_gp
        WHERE guild_id = p_guild_id
        LIMIT 1
    ');
    $stmt->bindValue(':guild_id', $guild_id, PDO::PARAM_INT);
	$stmt->bindValue(':gp', $gp, PDO::PARAM_INT);
	$result = $stmt->execute();

    if (!$result) {
        throw new Exception('could not increment guild gp');
    }

    return $result;
}