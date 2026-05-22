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
 * A view composer for the viewing House Points
 *
 * @version v31
 * @since   v31
 */
class ViewPoints implements ContainerAwareInterface
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
        $table->addMetaData('hidePagination', false);
        $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/3 my-2 text-center');

        $table->addHeaderAction('view', __('House Points By Events'))
            ->displayLabel()
            ->setURL('/modules/House Points/overall_events.php');

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
     * Section 2: Table of house/student points filtered by event
     */
    public function renderByEvents()
    {
        $yearID        = $this->session->get('gibbonSchoolYearID');
        $absoluteURL   = $this->session->get('absoluteURL');
        $selectedEvent = trim($_GET['hpEvent'] ?? '');

        // Distinct event names for the dropdown.
        $distinctEvents = $this->housePointCategoryGateway->selectDistinctCategoryEvents()->fetchAll();
        $eventOptions   = array_map('trim', array_column($distinctEvents, 'categoryEvent'));

        $form = Form::create('hpEventSelectorForm', $absoluteURL . '/index.php', 'get');
        $form->setTitle(__('House Points By Events'));
        $form->setClass('noIntBorder w-full');
        $form->addHiddenValue('q', '/modules/House Points/overall_events.php');

        $row = $form->addRow();
            $row->addLabel('hpEvent', __('Event'));
            $row->addSelect('hpEvent')
                ->fromArray($eventOptions)
                ->selected($selectedEvent)
                ->required()
                ->placeholder();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($this->session);

        $output = $form->getOutput();

        if (!empty($selectedEvent) && in_array($selectedEvent, $eventOptions)) {
            $criteria = $this->housePointHouseGateway
                ->newQueryCriteria()
                ->sortBy('awardedDate', 'DESC')
                ->pageSize(50)
                ->fromPOST('hpCategoryEvents');

            $events = $this->housePointHouseGateway
                ->queryEventsByCategoryEvent($criteria, $yearID, $selectedEvent);

            $table = DataTable::createPaginated('hpCategoryEvents', $criteria);
            $table->setTitle($selectedEvent);

            $table->addColumn('activity', __('Activity'));
            $table->addColumn('points', __('Points'))->notSortable();
            $table->addColumn('houseName', __('House'));
            $table->addColumn('studentName', __('Student'));

            $output .= $table->render($events);
        }

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
