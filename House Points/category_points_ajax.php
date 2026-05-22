<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
include  "../../gibbon.php";

$categoryID = isset($_POST['categoryID'])? $_POST['categoryID'] : '';

if (empty($categoryID)) {
    die('');
}

$data = array('categoryID' => $categoryID);
$sql = "SELECT categoryPresets FROM hpCategory WHERE categoryID=:categoryID";
$result = $pdo->executeQuery($data, $sql);

if (!$result || $result->rowCount() == 0) {
    die('');
} else {
    $presets = array();
    $presetsText = $result->fetchColumn(0);
    if (empty($presetsText)) {
        die('');
    }

    $presetGroups = array_map('trim', explode(',', $presetsText));
    foreach ($presetGroups as $index => $preset) {
        $presetValues = array_map('trim', explode(':', $preset));
        list($name, $points) = array_pad($presetValues, 2, false);
        $presets[$points.chr(($index+65))] = ($name != $points)? $name.': '.$points.' points' : $points.' points';
    }

    die(json_encode($presets));
}
