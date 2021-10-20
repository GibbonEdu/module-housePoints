<?php
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

     
function readMyPoints($dbh, $studentID, $yearID) {
    $data = array(
        'studentID' => $studentID,
        'yearID' => $yearID
    );
    $sql = "SELECT  
        hpPointStudent.points, 
        CONCAT(LEFT(gibbonPerson.preferredName,1), '.', gibbonPerson.surname) AS teacherName,
        hpPointStudent.awardedDate, 
        hpCategory.categoryName
        FROM hpPointStudent
        INNER JOIN hpCategory
        ON hpCategory.categoryID = hpPointStudent.categoryID
        INNER JOIN gibbonPerson
        ON gibbonPerson.gibbonPersonID = hpPointStudent.awardedBy
        WHERE hpPointStudent.studentID = :studentID
        AND hpPointStudent.yearID = :yearID
        ORDER BY hpPointStudent.awardedDate DESC";
    $rs = $dbh->prepare($sql);
    $rs->execute($data);
    return $rs;
}
    
function readPointsList($dbh, $yearID) {
    $data = array(
        'yearID' => $yearID
    );
    $sql = "SELECT gibbonHouse.gibbonHouseID AS houseID,
        gibbonHouse.name AS houseName,
        gibbonHouse.logo as houseLogo,
        COALESCE(pointStudent.total + pointHouse.total, pointStudent.total, pointHouse.total, 0) AS total
        FROM gibbonHouse
        LEFT JOIN 
        (
            SELECT gibbonPerson.gibbonHouseID AS houseID,
            SUM(hpPointStudent.points) AS total
            FROM hpPointStudent
            INNER JOIN gibbonPerson
            ON hpPointStudent.studentID = gibbonPerson.gibbonPersonID
            WHERE hpPointStudent.yearID=:yearID
            GROUP BY gibbonPerson.gibbonHouseID

        ) AS pointStudent
        ON pointStudent.houseID = gibbonHouse.gibbonHouseID
        LEFT JOIN 
        (
            SELECT hpPointHouse.houseID,
            SUM(hpPointHouse.points) AS total
            FROM hpPointHouse
            WHERE hpPointHouse.yearID=:yearID
            GROUP BY hpPointHouse.houseID
        ) AS pointHouse
        ON pointHouse.houseID = gibbonHouse.gibbonHouseID

        ORDER BY total DESC";
    $rs = $dbh->prepare($sql);
    $rs->execute($data);
    return $rs;
}

    
