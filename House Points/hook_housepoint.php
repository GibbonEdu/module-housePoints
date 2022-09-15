<?php

//Module includes
require_once './modules/House Points/moduleFunctions.php';

/*
if (isActionAccessible($guid, $connection2, '/modules/House Points/overall.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo 'You do not have access to this action.';
    echo '</div>';

} else {
*/
    $yearID = $session->get('gibbonSchoolYearID');
    $pointsList = readPointsList($connection2, $yearID);

    $hook = "";
    $hook .= "<p>&nbsp;</p>";
    $hook .= "<h3>Overall House Points</h3>";
    $hook .= "<table style='width:100%;font-size:14pt'>";
        $hook .= "<tr>";
            $hook .= "<th style='width:15%'>Crest</th>";
            $hook .= "<th style='width:35%'>House</th>";
            $hook .= "<th style='width:30%'>Points</th>";
        $hook .= "</tr>";

        while ($row = $pointsList->fetch()) {
            $hook .= "<tr>";
                $hook .= "<td class='textCenter'>";
                if (!empty($row['houseLogo'])) {
                    $hook .= sprintf('<img src="%1$s" title="%2$s" style="width:auto;height:80px;">', $session->get('absoluteURL').'/'.$row['houseLogo'], $row['houseName'] );
                }
                $hook .= "</td>";
                $hook .= "<td>".$row['houseName']."</td>";
                $hook .= "<td>".$row['total']."</td>";
            $hook .= "</tr>";
        }
    $hook .= "</table>";
    return $hook;
//}
