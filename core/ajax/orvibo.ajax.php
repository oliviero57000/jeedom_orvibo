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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

     if (init('action') == 'getOrvibo') {
        $orvibo = orvibo::byId(init('id'));
        if (!is_object($orvibo)) {
            throw new Exception(__('Orvibo inconnu verifié l\'id', __FILE__));
        }
        $return = utils::o2a($orvibo);
        $return['cmd'] = array();
        foreach ($orvibo->getCmd() as $cmd) {
            $cmd_info = utils::o2a($cmd);
            $cmd_info['value'] = $cmd->execCmd(null, 0);
            $return['cmd'][] = $cmd_info;
        }
        ajax::success($return);
     }

    if (init('action') == 'learning') {
        ajax::success(orvibo::modeLearning( init('node')));
    }

    if (init('action') == 'rflearning') {
        ajax::success(orvibo::EnterRFLearningMode( init('node')));
    }

    if (init('action') == 'command') {
        ajax::success(orvibo::modeCommand( init('node')));
    }

    if (init('action') == 'synchronise') {
        ajax::success(orvibo::synchronise( init('source'), init('target')));
    }

    if (init('action') == 'count') {
      if (init('node') == '') {
        ajax::success('0');
    } else {
      ajax::success(orvibo::count( init('node')));
    }
  }

  if (init('action') == 'getModuleInfo') {
        $eqLogic = orvibo::byId(init('id'));
        if (!is_object($eqLogic)) {
        throw new Exception(__('{{orvibo eqLogic non trouvé}} : ', __FILE__) . init('id'));
            }
        ajax::success($eqLogic->getInfo());
    }

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
