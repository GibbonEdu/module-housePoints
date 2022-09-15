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


?>
