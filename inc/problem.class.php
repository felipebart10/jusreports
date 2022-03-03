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

class PluginJusreportsProblem extends CommonDBTM
{
   static function getList(CommonDBTM $item)
   {
      global $DB;
      $criteria = Problem::getCommonCriteria();
      $restrict = [];
      $options  = [
         'criteria' => [],
         'reset'    => 'reset',
      ];
      $restrict['glpi_items_problems.items_id'] = $item->getID();
      $restrict['glpi_items_problems.itemtype'] = $item->getType();
      $options['criteria'][0]['field']      = 12;
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = 'all';
      $options['criteria'][0]['link']       = 'AND';

      $options['metacriteria'][0]['itemtype']   = $item->getType();
      $options['metacriteria'][0]['field']      = Search::getOptionNumber(
         $item->getType(),
         'id'
      );
      $options['metacriteria'][0]['searchtype'] = 'equals';
      $options['metacriteria'][0]['value']      = $item->getID();
      $options['metacriteria'][0]['link']       = 'AND';
      $criteria['DISTINCT'] = true;
      $criteria['INNER JOIN']['glpi_problemcosts'] = ['ON' => ['glpi_problems' => 'id', 'glpi_problemcosts' => 'problems_id']];
      $criteria['SELECT'] = array_merge(
         $criteria['SELECT'],
         [
            'glpi_problemcosts.cost_time',
            'glpi_problemcosts.cost_fixed',
            'glpi_problemcosts.cost_material'
         ]
      );
      //$criteria['WHERE'] = $restrict + getEntitiesRestrictCriteria(self::getTable());
      $criteria['WHERE'] = $restrict;
      $criteria['WHERE']['glpi_problems.is_deleted'] = 0;
      $criteria['LIMIT'] = (int)$_SESSION['glpilist_limit'];
      $iterator = $DB->request($criteria);
      $number = count($iterator);
      $colspan = 11;
      if (count($_SESSION["glpiactiveentities"]) > 1) {
         $colspan++;
      }

      if ($number > 0) {
         $id_store = [];
         while ($data = $iterator->next()) {
            array_push($id_store, $data["id"]);
            if (array_count_values($id_store)[$data["id"]] < 2) {
               Session::addToNavigateListItems('Problem', $data["id"]);
               PluginJusreportsReport::showShort(Problem::getType(), $data["id"]);
            }
         }
      }
      return true;
   }

   static function computeCost(CommonDBTM $item)
   {
      global $DB;

      $cost = 0;

      $iterator = $DB->request([
         'SELECT'    => 'glpi_problemcosts.*',
         'FROM'      => 'glpi_problemcosts',
         'WHERE'     => [
            'glpi_problemcosts.problems_id' => $item->getID(),
            'OR'                          => [
               'glpi_problemcosts.cost_time'     => ['>', 0],
               'glpi_problemcosts.cost_fixed'    => ['>', 0],
               'glpi_problemcosts.cost_material' => ['>', 0]
            ]
         ]
      ]);

      while ($data = $iterator->next()) {
         $cost += ProblemCost::computeTotalCost(
            $data["actiontime"],
            $data["cost_time"],
            $data["cost_fixed"],
            $data["cost_material"]
         );
      }
      return $cost;
   }

   static function computeTotalCost(CommonDBTM $item)
   {
      global $DB;

      $totalcost = 0;

      $iterator = $DB->request([
         'SELECT'    => ['glpi_problemcosts.*'],
         'FROM'      => ['glpi_items_problems', 'glpi_problemcosts'],
         'WHERE'     => [
            'glpi_items_problems.problems_id' => new \QueryExpression(DBmysql::quoteName('glpi_problemcosts.problems_id')),
            'glpi_items_problems.items_id' => $item->getID(),
            'glpi_items_problems.itemtype' => $item->getType(),
            'OR'                          => [
               'glpi_problemcosts.cost_time'     => ['>', 0],
               'glpi_problemcosts.cost_fixed'    => ['>', 0],
               'glpi_problemcosts.cost_material' => ['>', 0]
            ]
         ]
      ]);

      while ($data = $iterator->next()) {
         $totalcost += ProblemCost::computeTotalCost(
            $data["actiontime"],
            $data["cost_time"],
            $data["cost_fixed"],
            $data["cost_material"]
         );
      }
      return $totalcost;
   }

}
