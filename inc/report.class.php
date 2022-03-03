<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

// Class of the defined type

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class built to render the report table.
 *
 * ...
 */
class PluginJusreportsReport extends CommonDBTM
{
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return __('Total cost');
    }
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        echo "<div>";
        $totalcost = PluginJusreportsTicket::computeTotalCost($item) + PluginJusreportsProblem::computeTotalCost($item);
        if ($totalcost) {
            echo "<table class='tab_cadre_fixehov'>";
            self::reportHeader();
            PluginJusreportsTicket::getList($item);
            PluginJusreportsProblem::getList($item);
            self::reportFooter($totalcost);
            echo "</table></div>";
        } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('No data found') . "</th></tr>";
        }
        return true;
    }
    /**
     * Render the report header, with all its columns
     *
     * @since 1.0.0
     *
     * @param type $output_type report type, in this case HTML.
     * @param type $mass_id mass action stuff
     */
    static function reportHeader($mass_id = '')
    {
        $output_type = Search::HTML_OUTPUT;
        echo Search::showNewLine($output_type);
        $header_num = 1;
        $items = [];
        $items[(empty($mass_id) ? '&nbsp' : Html::getCheckAllAsCheckbox($mass_id))] = '';
        $items[__('Type')]             = "type";
        $items[__('ID')]             = "id";
        $items[__('Title')]          = "name";
        $items[__('Status')]             = "status";
        $items[_n('Date', 'Dates', 1)]               = "date";
        $items[__('Cost')] = 'total_cost';
        foreach (array_keys($items) as $key) {
            $link   = "";
            echo Search::showHeaderItem($output_type, $key, $header_num, $link);
        }
        echo Search::showEndLine($output_type);
    }

    /**
     * Render the report footer, with the sum of all costs
     *
     * @since 1.0.0
     *
     * @param float $value value to show on the item
     */
    static function reportFooter($value)
    {
        $output_type = Search::HTML_OUTPUT;
        $line_num = 1;
        echo Search::showNewLine($output_type);
        $check_col = '';
        echo Search::showHeaderItem($output_type, $check_col, $line_num, 1);
        echo Search::showHeaderItem($output_type, $check_col, $line_num, 1);
        echo Search::showHeaderItem($output_type, $check_col, $line_num, 1);
        echo Search::showHeaderItem($output_type, $check_col, $line_num, 1);
        echo Search::showHeaderItem($output_type, $check_col, $line_num, 1);
        $line_num = 1;
        echo Search::showHeaderItem($output_type, "TOTAL", $line_num, '', '', '', "class='right'");
        echo Search::showHeaderItem($output_type, number_format($value, 2, ',', ''), $line_num, '', '', '', '');
        echo Search::showEndLine($output_type);
    }

    /**
     * Render a line with data from 
     *
     * @since 1.0.0
     *
     * @param string $type ITIL object type
     * @param int $id ITIL item ID
     */
    static function showShort($type, $id, $options = [])
    {

        $p = [
            'output_type'            => Search::HTML_OUTPUT,
            'row_num'                => 0,
            'type_for_massiveaction' => 0,
            'id_for_massiveaction'   => 0,
            'followups'              => false,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }
        switch ($type) {
            case Ticket::getType():
                $item = new Ticket();
                break;
            case Problem::getType():
                $item = new Problem();
                break;
            case Change::getType():
                $item = new Change();
                break;
        }
        $align       = "class='left'";

        if ($item->getFromDB($id)) {
            $item_num = 1;
            echo Search::showNewLine($p['output_type'], $p['row_num'] % 2, $item->isDeleted());
            $check_col = '';
            echo Search::showItem($p['output_type'], $check_col, $item_num, $p['row_num'], $align);
            //  column: type
            $first_col = $item->getTypeName();
            echo Search::showItem($p['output_type'], $first_col, $item_num, $p['row_num'], $align);
            //  column: ID
            $second_col = $item->fields["id"];
            echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align);
            //  column: name
            $name_col = $item->fields['name'];
            echo Search::showItem($p['output_type'], $name_col, $item_num, $p['row_num'], $align);
            //  column: Status
            $third_col = $item->getStatusIcon($item->fields['status']);
            echo Search::showItem($p['output_type'], $third_col, $item_num, $p['row_num'], $align);
            //  column: Date
            $fourth_col = Html::convDateTime($item->fields['date']);
            echo Search::showItem($p['output_type'], $fourth_col, $item_num, $p['row_num'], $align);
            //  column: Cost
            if ($type == Ticket::getType()) {
                $fifth_col = PluginJusreportsTicket::computeCost($item);
            } else if ($type == Problem::getType()) {
                $fifth_col = PluginJusreportsProblem::computeCost($item);
            }
            echo Search::showItem($p['output_type'], number_format($fifth_col, 2, ',', ''), $item_num, $p['row_num'], $align);

            echo Search::showEndLine($p['output_type']);
        }
    }
}
