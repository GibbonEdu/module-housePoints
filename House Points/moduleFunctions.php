<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

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

// Queries for list of points awarded for students and houses.
function readEventsList($dbh, $yearID) {
    $data = array(
        'yearID' => $yearID
    );
    $sql = "SELECT gibbonHouse.gibbonHouseID AS houseID,
    gibbonHouse.name AS houseName,
    gibbonHouse.logo AS houseLogo,
    pointOverall.individualPoints AS individualPoints,
	pointOverall.reason AS reason,
    pointOverall.awardedDate AS awardedDate
    FROM gibbonHouse
    LEFT JOIN
    (
        SELECT gibbonPerson.gibbonHouseID AS houseID,
        hpPointStudent.points AS individualPoints,
        hpPointStudent.reason AS reason,
        hpPointStudent.awardedDate AS awardedDate
        FROM hpPointStudent
        INNER JOIN gibbonPerson
        ON hpPointStudent.studentID = gibbonPerson.gibbonPersonID
        WHERE hpPointStudent.yearID=:yearID
        UNION
        SELECT hpPointHouse.houseID,
        hpPointHouse.points AS individualPoints,
        hpPointHouse.reason AS reason,
        hpPointHouse.awardedDate AS awardedDate
        FROM hpPointHouse
        WHERE hpPointHouse.yearID=:yearID

    ) AS pointOverall
    ON pointOverall.houseID = gibbonHouse.gibbonHouseID
    ORDER BY awardedDate";
    $rs = $dbh->prepare($sql);
    $rs->execute($data);
    return $rs;
}

// Pivots the table returned in readEventsList() to have houses as columns and an event associated to them.
function parseEventsList($rs) {
    $oldTable = $rs->fetchAll();
    $uniqueHouses = array_unique(array_column($oldTable, 'houseName'));
    $sortValues = array_column($oldTable, 'reason'); 
    array_multisort($sortValues, SORT_ASC, $oldTable);
    $newTable = [];

    foreach ($oldTable as $row) {
        if(!empty($newTable[$row['reason']][$row['houseName']])) {
            $newTable[$row['reason']][$row['houseName']] += $row['individualPoints'];
        } else {
            $newTable[$row['reason']][$row['houseName']] = $row['individualPoints'];
        }
        $newTable[$row['reason']]['awardedDate'] = $row['reason'];
        $newTable[$row['reason']]['reason'] = $row['reason'];
    }

    $sortValues = array_column($newTable, 'awardedDate'); 
    array_multisort($sortValues, SORT_DESC, $newTable);

    return ['events' => $newTable, 'houses' => $uniqueHouses];
    
}


?>
