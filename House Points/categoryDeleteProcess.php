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

use Gibbon\Module\HousePoints\Domain\HousePointCategoryGateway;

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . '/category.php';

if (!isActionAccessible($guid, $connection2, '/modules/House Points/category.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    
    $categoryID = $_POST['categoryID'] ?? '';

    $housePointCategoryGateway = $container->get(HousePointCategoryGateway::class);
    
    
    if (empty($categoryID) || !$housePointCategoryGateway->exists($categoryID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit();
    }

    if (!$housePointCategoryGateway->delete($categoryID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit();
}
?>
