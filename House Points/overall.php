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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
use Gibbon\Module\HousePoints\viewPoints;

require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('View points overall'));

if (isActionAccessible($guid, $connection2, '/modules/House Points/overall.php') == false) {
    // Access Denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $view = $container->get(viewPoints::class);

    // POINT TOTALS DATATABLE
    echo $view->renderOverallPoints();

    // EVENT POINTS DATATABLE
    echo $view->renderByEvents();

    // HALL OF FAME DATATABLE
    echo $view->renderHallOfFame();
}
