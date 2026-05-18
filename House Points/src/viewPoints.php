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

namespace Gibbon\Module\HousePoints;

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Contracts\Services\Session;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use Gibbon\Module\HousePoints\Domain\HousePointHouseGateway;
use Gibbon\Module\HousePoints\Domain\HousePointCategoryGateway;

/**
 * View Points
 *
 * A view composer for the house points dashboard sections.
 * Each public method renders one section and returns its HTML output.
 *
 * @version v31
 * @since   v31
 */
class viewPoints implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $housePointHouseGateway;
    protected $housePointCategoryGateway;
    protected $session;

    public function __construct(
        Session $session,
        HousePointHouseGateway $housePointHouseGateway,
        HousePointCategoryGateway $housePointCategoryGateway
    ) {
        $this->session = $session;
        $this->housePointHouseGateway = $housePointHouseGateway;
        $this->housePointCategoryGateway = $housePointCategoryGateway;
    }

    /**
     * Section 1: Overall house point totals for the current school year.
     */
    public function renderOverallPoints()
    {
        $yearID = $this->session->get('gibbonSchoolYearID');
        $pointsList = $this->housePointHouseGateway->selectAllPoints($yearID);

        $gridRenderer = new GridView($this->getContainer()->get('twig'));
        $table = DataTable::create('hpOverall', $gridRenderer);
        $table->setTitle(__('Overall House Points'));
        $table->addMetaData('hidePagination', true);
        $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/3 my-2 text-center');

        $table->addColumn('Crest')
            ->format(function ($row) {
                return Format::photo($row['houseLogo'], 'md');
            });
        $table->addColumn('House')
            ->setClass('text-lg text-gray-600 leading-snug')
            ->format(function ($row) {
                return !empty($row['houseName']) ? $row['houseName'] : __('Unknown');
            });
        $table->addColumn('Total')
            ->setClass('text-base text-gray-600 leading-snug')
            ->format(function ($row) {
                return !empty($row['total']) ? $row['total'] : '0';
            });

        return $table->render($pointsList->toDataSet());
    }

    /**
     * Section 2: Events grouped by category
     */
    public function renderByCategory()
    {
        $yearID = $this->session->get('gibbonSchoolYearID');

        $categories = $this->housePointCategoryGateway->selectBy([], ['categoryID', 'categoryName'])->fetchAll();
        $categoryOptions = array_column($categories, 'categoryName', 'categoryID');

        $titleForm = Form::create('hpCategoryTitle', '');
        $titleForm->setTitle(__('House Points By Category'));
        $titleForm->setClass('noIntBorder w-full');
        $output = $titleForm->getOutput();

        $output .= '<div x-data="{ hpCategory: \'\' }">';

        // action='ajax' skips the <form> tag, so no nested x-data conflict
        $selectorForm = Form::createBlank('hpCategorySelector', 'ajax');
        $row = $selectorForm->addRow();
        $row->addLabel('hpCategorySelect', __('Category'));
        $row->addSelect('hpCategorySelect')->addClass('bg-blue-50')
            ->fromArray($categoryOptions)
            ->placeholder(__('-- Select a Category --'))
            ->setAttribute('x-model', 'hpCategory');
        $output .= $selectorForm->getOutput();

        foreach ($categories as $cat) {
            $catID = (int) $cat['categoryID'];

            // Per-category criteria — reads sort state from POST on AJAX refresh
            $criteria = $this->housePointHouseGateway
                ->newQueryCriteria(true)
                ->sortBy('awardedDate', 'DESC')
                ->fromPOST('hpEvents' . $catID);

            $events = $this->housePointHouseGateway->queryEventsByCategory($criteria, $yearID, $catID);

            $output .= '<div x-show="hpCategory == \'' . $catID . '\'" x-cloak class="mt-4">';

            $eventsTable = DataTable::createPaginated('hpEvents' . $catID, $criteria);
            $eventsTable->addMetaData('hidePagination', true);

            $eventsTable->addColumn('reason', __('Activity'))->sortable(['reason']);
            $eventsTable->addColumn('houseName', __('House'))->sortable(['houseName']);
            $eventsTable->addColumn('points', __('Points'))->notSortable();
            $eventsTable->addColumn('studentName', __('Student'))
                ->format(function ($row) {
                    return $row['studentName'] ?? '';
                });

            $output .= $eventsTable->render($events);
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    public function renderHallOfFame()
    {
        $titleForm = Form::create('title', '');
        $titleForm->setTitle(__('Hall of Fame'));
        $titleForm->setClass('noIntBorder w-full');
        $output = $titleForm->getOutput();

        $houseSchoolYears = $this->housePointHouseGateway->selectHouseSchoolYears()->fetchAll();

        foreach ($houseSchoolYears as $schoolYear) {
            $totalPointsList = $this->housePointHouseGateway->selectAllPoints($schoolYear['gibbonSchoolYearID']);

            $table = DataTable::create('housePoints' . $schoolYear['gibbonSchoolYearID']);
            $table->setTitle($schoolYear['name']);
            $table->addMetaData('hidePagination', true);
            $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/3 my-2 text-center');

            $table->addColumn('houseName', __('House'));
            $table->addColumn('houseLogo', __('House Logo'))
                ->format(function ($row) {
                    return Format::photo($row['houseLogo'], '', 'w-8 ');
                });
            $table->addColumn('total', __('Total Points'));

            $output .= $table->render($totalPointsList->toDataSet());
        }

        return $output;
    }
}