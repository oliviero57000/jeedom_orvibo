<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';


function orvibo_install() {
    $cron = cron::byClassAndFunction('orvibo', 'daemon');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('orvibo');
        $cron->setFunction('daemon');
        $cron->setEnable(1);
        $cron->setDeamon(1);
        $cron->setSchedule('* * * * *');
        $cron->setTimeout('1440');
        $cron->save();
    }
}

function orvibo_update() {
  $cron = cron::byClassAndFunction('orvibo', 'pull');
  if (is_object($cron)) {
      $cron->remove();
  }
    $cron = cron::byClassAndFunction('orvibo', 'daemon');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('orvibo');
        $cron->setFunction('daemon');
        $cron->setEnable(1);
        $cron->setDeamon(1);
        $cron->setSchedule('* * * * *');
        $cron->setTimeout('1440');
        $cron->save();
    }
    $cron->stop();
}

function orvibo_remove() {
    $cron = cron::byClassAndFunction('orvibo', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }
    $cron = cron::byClassAndFunction('orvibo', 'daemon');
    if (is_object($cron)) {
        $cron->stop();
        $cron->remove();
    }
}
?>
