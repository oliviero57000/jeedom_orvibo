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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class orvibo extends eqLogic {

  public static function cron5() {
    log::add('orvibo', 'debug', 'Début du pull');
    orvibo::Discover();
    foreach (eqLogic::byType('orvibo') as $orvibo) {
      orvibo::Subscribe($orvibo->getConfiguration('mac'));
    }
  }

  public static function deamon_info() {
		$return = array();
		$return['log'] = '';
		$return['state'] = 'nok';
		$cron = cron::byClassAndFunction('orvibo', 'daemon');
		if (is_object($cron) && $cron->running()) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamon_start($_debug = false) {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$cron = cron::byClassAndFunction('orvibo', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->run();
    orvibo::Discover();
	}

	public static function deamon_stop() {
		$cron = cron::byClassAndFunction('orvibo', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->halt();
	}

  public static function daemon() {
    //Create a UDP socket
    if(!($socksrv = socket_create(AF_INET, SOCK_DGRAM, 0)))
    {
      $errorcode = socket_last_error();
      $errormsg = socket_strerror($errorcode);
      die(log::add('orvibo', 'error', 'Création du socket impossible ' . $errorcode . ' : ' . $errormsg));
    }

    if (!socket_set_option($socksrv, SOL_SOCKET, SO_REUSEADDR, 1))
    {
      log::add('orvibo', 'error', 'Impossible d appliquer les options au socket : ' . socket_strerror($errorcode));
    }

    // Bind the source address
    if( !socket_bind($socksrv, "0.0.0.0" , 10000) )
    {
      $errorcode = socket_last_error();
      $errormsg = socket_strerror($errorcode);
      die(log::add('orvibo', 'error', 'Connexion au socket impossible ' . $errorcode . ' : ' . $errormsg));
    }
    log::add('orvibo', 'debug', 'Daemon en écoute');

    //Do some communication, this loop can handle multiple clients
    while(1)
    {
      //Receive some data
      $r = socket_recvfrom($socksrv, $buf, 1024, 0, $remote_ip, $remote_port);
      $message=orvibo::binaryToString($buf);
      orvibo::handleMessage($message, $remote_ip);
      log::add('orvibo', 'debug', 'Recu : ' . $message . ' de ' . $remote_ip);
    }
    socket_close($socksrv);
  }

  public function stopDaemon() {
    $cron = cron::byClassAndFunction('orvibo', 'daemon');
    $cron->stop();
    $cron->start();
    orvibo::Discover();
  }

  public static function dependancy_info() {
    $return = array();
    $return['log'] = 'orvibo_dep';
    $serialport = realpath(dirname(__FILE__) . '/../../node/node_modules/node-orvibo');
    $request = realpath(dirname(__FILE__) . '/../../node/node_modules/request');
    $return['progress_file'] = '/tmp/orvibo_dep';
    if (is_dir($serialport) && is_dir($request)) {
      $return['state'] = 'ok';
    } else {
      $return['state'] = 'nok';
    }
    return $return;
  }

  public static function dependancy_install() {
    log::add('orvibo','info','Installation des dépéndances nodejs');
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' > ' . log::getPathToLog('orvibo_dep') . ' 2>&1 &');
  }

  public static function modeCommand($mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    if ($orvibo->getConfiguration('command') == '1') {
      $orvibo->setConfiguration('command', '0');
      $orvibo->save();
    } else {
      $orvibo->setConfiguration('command', '1');
      $orvibo->save();
    }
    log::add('orvibo', 'debug', 'Modification du mode de création des commandes ' . $orvibo->getConfiguration('command'));
    return $orvibo->getConfiguration('command');
  }

  public static function modeLearning($mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    if ($orvibo->getConfiguration('learning') == '1') {
      orvibo::Subscribe($orvibo->getConfiguration('mac'));
      $orvibo->setConfiguration('learning', '0');
      $orvibo->save();
    } else {
      orvibo::EnterLearningMode($mac);
      $orvibo->setConfiguration('learning', '1');
      $orvibo->save();
    }
    log::add('orvibo', 'debug', 'Modification du mode d\'apprentissage des commandes ' . $orvibo->getConfiguration('learning'));
    return $orvibo->getConfiguration('learning');
  }

  public static function synchronise($source,$target) {
    $orviboSrc = self::byLogicalId($source, 'orvibo');
    $orviboTar = self::byLogicalId($target, 'orvibo');
    foreach ($orviboTar->getCmd('action') as $cmd) {
      //orvibo::saveCode($IR,$mac)
      $cmd->remove();
    }
    foreach ($orviboSrc->getCmd('action') as $cmd) {
      //orvibo::saveCode($IR,$mac)
      $cmds = $orviboTar->getCmd();
      $order = count($cmds);
      $orviboCmd = new orviboCmd();
      $orviboCmd->setEqLogic_id($orviboTar->getId());
      $orviboCmd->setEqType('orvibo');
      $orviboCmd->setOrder($order);
      $orviboCmd->setLogicalId($cmd->getLogicalId());
      $orviboCmd->setName( $cmd->getName() );
      $orviboCmd->setConfiguration('order', $cmd->getConfiguration('order'));
      $orviboCmd->setDisplay('icon', $cmd->getDisplay('icon'));
      $orviboCmd->setType('action');
      $orviboCmd->setSubType('other');
      $orviboCmd->save();
    }
    log::add('orvibo', 'debug', 'Synchronisation des commandes de ' . $source . ' à ' . $target);
  }

  // Count number of commands of eqLogic for auto-refresh
  public static function count($mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    $cmds = $orvibo->getCmd();
    $total = count($cmds);
    return $total;
  }

  // Discover is a function that broadcasts 686400067161 over the network in order to find unpaired networks
  public static function Discover() {
    log::add('orvibo', 'debug', 'Découverte des équipements');
    $payload = orvibo::makePayload(array(0x68, 0x64, 0x00, 0x06, 0x71, 0x61));
    orvibo::broadcastMessage($payload);
  }

  // Subscribe loops over all the Devices we know about, and asks for control (subscription)
  public static function Subscribe($mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    log::add('orvibo', 'debug', 'Souscription à : ' . $orvibo->getConfiguration('mac'));
    // We send a message to each socket. reverseMAC takes a MAC address and reverses each pair (e.g. AC CF 23 becomes CA FC 32)
    $twenties=array(0x20, 0x20, 0x20, 0x20, 0x20, 0x20);
    $macReversed=orvibo::reverseMAC($orvibo->getConfiguration('mac'));
    $payload = self::makePayload(array(0x68, 0x64, 0x00, 0x1e, 0x63, 0x6c)).self::makePayload(self::HexStringToArray($orvibo->getConfiguration('mac'))).self::makePayload($twenties).self::makePayload($macReversed).self::makePayload($twenties);
    orvibo::SendMessage($payload,$orvibo->getConfiguration('mac'));
  }

  // Query asks all the sockets we know about, for their names. Current state is sent on Subscription confirmation, not here
  public static function Query($mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    log::add('orvibo', 'debug', 'Query de ' . $orvibo->getConfiguration('mac'));
    $twenties=array(0x20, 0x20, 0x20, 0x20, 0x20, 0x20);
    $payload = self::makePayload(array(0x68, 0x64, 0x00, 0x1d, 0x72, 0x74)).self::makePayload(self::HexStringToArray($orvibo->getConfiguration('mac'))).self::makePayload($twenties).self::makePayload(array(0x00, 0x00, 0x00, 0x00, 0x04, 0x74, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00));
    orvibo::SendMessage($payload, $orvibo->getConfiguration('mac'));
    log::add('orvibo', 'debug', 'Interrogation de : ' . $orvibo->getConfiguration('mac'));
  }

  public static function SaveDevice($mac,$ip,$type) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    if (!is_object($orvibo)) {
      log::add('orvibo', 'info', 'Equipement n existe pas, creation');
      $orvibo = new orvibo();
      $orvibo->setEqType_name('orvibo');
      $orvibo->setLogicalId($mac);
      $orvibo->setName($mac);
      $orvibo->setIsEnable(true);
      $orvibo->setConfiguration('mac', $mac);
      $orvibo->setConfiguration('addr', $ip);
      $orvibo->setConfiguration('type', $type);
      $orvibo->setConfiguration('query', '0');
      $orvibo->setConfiguration('subscribe', '0');
      $orvibo->setConfiguration('learning', '0');
      $orvibo->setConfiguration('command', '1');
      $orvibo->save();
      $orvibo->setStatus('lastCommunication', date('Y-m-d H:i:s'));
      $orvibo->save();
      event::add('orvibo::includeDevice',
        array(
          'state' => 1
        )
      );
      if ($type == 'allone') {
        $orviboCmd = new orviboCmd();
        $orviboCmd->setEqLogic_id($orvibo->getId());
        $orviboCmd->setEqType('orvibo');
        $orviboCmd->setIsVisible(1);
        $orviboCmd->setIsHistorized(0);
        $orviboCmd->setSubType('string');
        $orviboCmd->setLogicalId('activity');
        $orviboCmd->setType('info');
        $orviboCmd->setName( 'Activité' );
        $orviboCmd->save();
      } else {
        $orviboCmd = new orviboCmd();
        $orviboCmd->setEqLogic_id($orvibo->getId());
        $orviboCmd->setEqType('orvibo');
        $orviboCmd->setIsVisible(1);
        $orviboCmd->setIsHistorized(0);
        $orviboCmd->setSubType('binary');
        $orviboCmd->setLogicalId('status');
        $orviboCmd->setType('info');
        $orviboCmd->setName( 'Statut' );
	$orviboCmd->setDisplay('generic_type','LIGHT_STATE');
        $orviboCmd->save();
        $orviboCmd = new orviboCmd();
        $orviboCmd->setEqLogic_id($orvibo->getId());
        $orviboCmd->setEqType('orvibo');
        $orviboCmd->setLogicalId('on');
        $orviboCmd->setName( 'On' );
        $orviboCmd->setConfiguration('order', '01');
        $orviboCmd->setType('action');
        $orviboCmd->setSubType('other');
	$orviboCmd->setDisplay('generic_type','LIGHT_ON');
        $orviboCmd->save();
        $orviboCmd = new orviboCmd();
        $orviboCmd->setEqLogic_id($orvibo->getId());
        $orviboCmd->setEqType('orvibo');
        $orviboCmd->setLogicalId('off');
        $orviboCmd->setName( 'Off' );
        $orviboCmd->setConfiguration('order', '00');
        $orviboCmd->setType('action');
        $orviboCmd->setSubType('other');
	$orviboCmd->setDisplay('generic_type','LIGHT_OFF');
        $orviboCmd->save();
      }
    } else {
    	if ($type != 'allone') {
    	  $orviboCmd = $orvibo->getCmd(null, 'status');
    	  if ( $orviboCmd->getSubType() != 'binary' ) {
    	    $orviboCmd->setSubType('binary');
    	    $orviboCmd->save();
    	  }
    	  if ( $orviboCmd->getDisplay('generic_type') == "" ) {
    	    $orviboCmd->setDisplay('generic_type','LIGHT_STATE');
    	    $orviboCmd->save();
    	  }			
    	  $orviboCmd = $orvibo->getCmd(null, 'off');
    	  if ( $orviboCmd->getDisplay('generic_type') == "" ) {
    	    $orviboCmd->setDisplay('generic_type','LIGHT_OFF');
    	    $orviboCmd->save();
    	  }			
    	  $orviboCmd = $orvibo->getCmd(null, 'on');
    	  if ( $orviboCmd->getDisplay('generic_type') == "" ) {
    	    $orviboCmd->setDisplay('generic_type','LIGHT_ON');
    	    $orviboCmd->save();
    	  }			
	}
      }
      if ($orvibo->getConfiguration('addr') != $ip) {
      $orvibo->setConfiguration('addr', $ip);
      $orvibo->save();
    }
  }

  public static function saveCode($IR,$mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    log::add('orvibo', 'info', 'Nouvelle commande pour ' . $mac . ' code ' . $IR);
    if (is_object($orvibo)) {

      $orvibo->setStatus('lastCommunication', date('Y-m-d H:i:s'));
      $orvibo->save();
      if ($orvibo->getConfiguration('command') == '1') {
        log::add('orvibo', 'debug', 'Mode création de commandes activé sur ' . $mac);
        $orviboCmd = orviboCmd::byEqLogicIdAndLogicalId($orvibo->getId(),$IR);
        if (!is_object($orviboCmd)) {
          log::add('orvibo', 'debug', 'Commande non existante, création sur ' . $mac);
          $cmds = $orvibo->getCmd();
          $order = count($cmds);
          $orviboCmd = new orviboCmd();
          $orviboCmd->setEqLogic_id($orvibo->getId());
          $orviboCmd->setEqType('orvibo');
          $orviboCmd->setOrder($order);
          $orviboCmd->setLogicalId($IR);
          $orviboCmd->setName( 'Commande - ' . $order );
          $orviboCmd->setConfiguration('order', $IR);
          $orviboCmd->setType('action');
          $orviboCmd->setSubType('other');
          $orviboCmd->save();
          event::add('orvibo::stackData',
            utils::o2a($orviboCmd)
          );
        } else {
          log::add('orvibo', 'debug', 'Commande existante, pas de création sur ' . $mac);
        }
      } else {
        log::add('orvibo', 'debug', 'Mode création de commandes non-activé sur ' . $mac);
      }
    }
  }

  // EmitIR emits IR from the AllOne. Takes a hex string
  public static function EmitIR($IR, $mac) {
    orvibo::Subscribe($mac);
    $orvibo = self::byLogicalId($mac, 'orvibo');
    $code=trim(str_replace(' ', '', $IR));
    $len1=(int)(strlen($code)/2)+26;
    $high_byte=floor($len1/256);
    $low_byte=$len1-$high_byte*256;
    $h1=dechex($high_byte);
    $l1=dechex($low_byte);
    if (strlen($h1)<2) {
      $h1='0'.$h1;
    }
    if (strlen($l1)<2) {
      $l1='0'.$l1;
    }
    $packetlen=$h1.$l1;
    $len2=strlen($code)/2;
    $high_byte=floor($len2/256);
    $low_byte=$len2-$high_byte*256;
    $h2=dechex($high_byte);
    $l2=dechex($low_byte);
    if (strlen($h2)<2) {
      $h2='0'.$h2;
    }
    if (strlen($l2)<2) {
      $l2='0'.$l2;
    }
    $irlen=array_reverse(orvibo::HexStringToArray($h2.$l2));
    $randomBitA = rand(0, 255);
    $randomBitB = rand(0, 255);
    $twenties=array(0x20, 0x20, 0x20, 0x20, 0x20, 0x20);

    $payload = orvibo::makePayload(array(0x68, 0x64));
    $payload .= orvibo::makePayload(orvibo::HexStringToArray($packetlen));
    $payload .= orvibo::makePayload(array(0x69, 0x63));
    $payload .= orvibo::makePayload(orvibo::HexStringToArray($orvibo->getConfiguration('mac')));
    $payload .= orvibo::makePayload($twenties);
    $payload .= orvibo::makePayload(array(0x65, 0x00, 0x00, 0x00));
    $payload .= orvibo::makePayload(array($randomBitA, $randomBitB));
    $payload .= orvibo::makePayload($irlen);
    $payload .= orvibo::makePayload(orvibo::HexStringToArray($code));
    log::add('orvibo', 'info', 'Code IR : ' . $payload . ' à ' . $orvibo->getConfiguration('mac'));
    orvibo::SendMessage($payload, $orvibo->getConfiguration('mac'));
    /*data: {
      extra: "65000000",
      // The AllOne flat out refuses to emit IR if these two bytes are the same
      // as the last time IR was emitted. It's to prevent UDP-related double-blasting
      randomA: _.padLeft(Math.floor((Math.random() * 255)).toString(16), 2, "0"),
      randomB: _.padLeft(Math.floor((Math.random() * 255)).toString(16), 2, "0"),
      // HA HA, OH WOW! This is a doozy. It takes the length of our IR (divided by 2, because bytes)
      // turns it into a hex string, then uses lodash's "padLeft" to add leading zeroes if necessary. It then splits the string into chunks of two
      // (like our subscribe() function) before reversing the chunks, flattening any nested arrays, then joins the lot into a single string. UGH!
      irlength: this.switchEndian(_.padLeft((args.ir.length / 2).toString(16).toUpperCase(), 4, "0")),
      ir: args.ir
    }*/
  }

  public static function EnterLearningMode($mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    $twenties=array(0x20, 0x20, 0x20, 0x20, 0x20, 0x20);
    $payload = orvibo::makePayload(array(0x68, 0x64, 0x00, 0x18, 0x6c, 0x73)).orvibo::makePayload(orvibo::HexStringToArray($orvibo->getConfiguration('mac'))).orvibo::makePayload($twenties).orvibo::makePayload(array(0x01, 0x00, 0x00, 0x00, 0x00, 0x00));
    log::add('orvibo', 'info', 'Apprentissage IR ' . $payload . ' pour ' . $orvibo->getConfiguration('mac'));
    orvibo::Subscribe($orvibo->getConfiguration('mac'));
    orvibo::SendMessage($payload, $orvibo->getConfiguration('mac'));
  }

  public static function EnterRFLearningMode($mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    $rfid = 'abcde';
    $orviboCmd = new orviboCmd();
    $orviboCmd->setEqLogic_id($orvibo->getId());
    $orviboCmd->setEqType('orvibo');
    $orviboCmd->setName( 'Rfswitch ' . $rfid . ' ON' );
    $orviboCmd->setConfiguration('order', '01');
    $orviboCmd->setConfiguration('rfid', $rfid);
    $orviboCmd->setType('action');
    $orviboCmd->setSubType('other');
    $orviboCmd->save();
    event::add('orvibo::stackData',
      utils::o2a($orviboCmd)
    );
    $orviboCmd = new orviboCmd();
    $orviboCmd->setEqLogic_id($orvibo->getId());
    $orviboCmd->setEqType('orvibo');
    $orviboCmd->setName( 'Rfswitch ' . $rfid . ' OFF' );
    $orviboCmd->setConfiguration('order', '00');
    $orviboCmd->setConfiguration('rfid', $rfid);
    $orviboCmd->setType('action');
    $orviboCmd->setSubType('other');
    $orviboCmd->save();
    event::add('orvibo::stackData',
      utils::o2a($orviboCmd)
    );
    log::add('orvibo', 'info', 'Apprentissage RF ' . $rfid . ' pour ' . $orvibo->getConfiguration('mac'));
    orvibo::EmitRF($mac, $rfid, '01');
  }

  public static function EmitRF($mac, $rfid, $status) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    if ($status == '01') {
      $commande='0x01';
    } else {
      $commande='0x00';
    }
    $twenties=array(0x20, 0x20, 0x20, 0x20, 0x20, 0x20);
    $randomBitA = rand(0, 255);
    $randomBitB = rand(0, 255);
    $payload = orvibo::makePayload(array(0x68, 0x64, 0x00, 0x17, 0x64, 0x63));
    $payload .= orvibo::makePayload(orvibo::HexStringToArray($orvibo->getConfiguration('mac')));
    $payload .= orvibo::makePayload($twenties);
    $payload .= orvibo::makePayload(array(0x00, 0x00, 0x00, 0x00));//sessionID
    $payload .= orvibo::makePayload(array($randomBitA, $randomBitB));
    $payload .= orvibo::makePayload($commande);
    $payload .= orvibo::makePayload(orvibo::HexStringToArray(array(0x2a, 0x00)));//rfkey
    $payload .= orvibo::makePayload(orvibo::HexStringToArray($rfid));//rfid
    log::add('orvibo', 'info', 'Apprentissage RF ' . $payload . ' pour ' . $orvibo->getConfiguration('mac'));
    orvibo::SendMessage($payload, $orvibo->getConfiguration('mac'));
  }

/*
    data: {
      sessionID: "00000000"
      randomA: _.padLeft(Math.floor((Math.random() * 255)).toString(16), 2, "0"),
      randomB: _.padLeft(Math.floor((Math.random() * 255)).toString(16), 2, "0"),
      state: args.state ? "01" : "00",
      rfkey: "2a00",
      rfid: args.rfid
    }
  }*/

  public static function SocketState($status,$mac) {
    $orvibo = self::byLogicalId($mac, 'orvibo');
    $twenties=array(0x20, 0x20, 0x20, 0x20, 0x20, 0x20);
    if ($status == '01') {
      $commande=array(0x00, 0x00, 0x00, 0x00, 0x01);
    } else {
      $commande=array(0x00, 0x00, 0x00, 0x00, 0x00);
    }
    $payload = orvibo::makePayload(array(0x68, 0x64, 0x00, 0x17, 0x64, 0x63)).orvibo::makePayload(orvibo::HexStringToArray($orvibo->getConfiguration('mac'))).orvibo::makePayload($twenties).orvibo::makePayload($commande);
    log::add('orvibo', 'info', 'Socket ' . $payload . ' pour ' . $orvibo->getConfiguration('mac'));
    orvibo::Subscribe($orvibo->getConfiguration('mac'));
    orvibo::SendMessage($payload, $orvibo->getConfiguration('mac'));
    /* setState turns a socket on or off.
      data: {
        // Session ID?
        blank: "00000000",
        // Ternary operators are cool, but hard to read.
        // This one says "if state is true, set state to 01, otherwise, set to 00"
        state: args.state ? "01" : "00"
      }*/
  }




  // handleMessage parses a message found by CheckForMessages
  public static function handleMessage($message, $addr) {
    if (strlen($message) == 0) { // Blank message? Don't try and parse it!
      log::add('orvibo', 'debug', 'Recu : Message vide recu');
    }

    if ($message == "686400067161") {
      return true;
    }
    $commandID = substr($message, 8, 4); // What command we've received back

    $macStart = strrpos($message, "accf");  // Find where our MAC Address starts
    $mac = substr($message, $macStart, 12); // The MAC address of the socket responding
	  
	if (strlen($mac) != 12) {
		return false;
	}

    log::add('orvibo', 'debug', 'Recu : Commande ' . $commandID . ', Message ' . $message . ' de MAC ' . $mac . ' et IP ' . $addr);
    $orvibo = self::byLogicalId($mac, 'orvibo');

    switch ($commandID) {
      case "7161": // We've had a response to our broadcast message
      if (strrpos($message, "49524430")) { // Contains IRD00? It's an IR blaster!
        $type = 'allone';
      } else if (strrpos($message, "534f4330")) { // Contains SOC00? It's a socket!
        $type = 'socket';
      } else {
        $type = 'unknow';
      }
      log::add('orvibo', 'debug', 'Recu : Périphérique découvert MAC ' . $mac . ' de type ' . $type . ' et IP ' . $addr);
      orvibo::SaveDevice($mac,$addr,$type);
      orvibo::Subscribe($mac);
      break;

      case "636c": // We've had confirmation of subscription
      $lastBit = substr($message, -1); // Get the last bit from our message. 0 or 1 for off or on
      log::add('orvibo', 'debug', 'Recu : Bit de souscription ' . $lastBit . ' pour périphérique ' . $mac);
      if ($lastBit == "1") {
        log::add('orvibo', 'debug', 'Recu ' . $lastBit);
        $orvibo->setConfiguration('subscribe', '1');
        $orvibo->save();
        //log::add('orvibo', 'debug', 'Souscription acceptée pour périphérique ' . $mac);
      } else {
        $orvibo->setConfiguration('subscribe', '1');
        $orvibo->save();
        //log::add('orvibo', 'debug', 'Souscription refusée pour périphérique ' . $mac);
      }
      orvibo::Query($mac);
      break;

      case "7274": // We've queried our socket, this is the data back
      // Our name starts after the fourth 202020202020, or 140 bytes in
      $tmp=explode('202020202020', $message);
      $strName=$tmp[4];
      if($strName == "ffffffffffffffffffffffffffffffff") {
        $name='noname';
      } else {
        $name=trim(orvibo::HexStringToString($strName));
      }
      if (is_object($orvibo)) {
        $orvibo->setConfiguration('query', '1');
        $orvibo->setConfiguration('id', $name);
        $orvibo->save();
      }

      log::add('orvibo', 'debug', 'Recu : retour de Query pour périphérique ' . $mac);
      break;

      case "7366": // Confirmation of state change
      $lastBit = substr($message, -1); // Get the last bit from our message. 0 or 1 for off or on
      if ($lastBit == "0") {
        $statut = '0';
      } else {
        $statut = '1';
      }
      log::add('orvibo', 'debug', 'Recu : Changement de statut pour périphérique ' . $mac);
      $orvibo->setStatus('lastCommunication', date('Y-m-d H:i:s'));
      $orvibo->save();
      $orviboCmd = orviboCmd::byEqLogicIdAndLogicalId($orvibo->getId(),'status');
      if (is_object($orviboCmd)) {
        $orviboCmd->setConfiguration('value', $statut);
        $orviboCmd->save();
        $orviboCmd->event($statut);
      }
      break;

      case "6463": // RF433 pressed
      if (is_object($orvibo)) {
        $orvibo->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $orvibo->save();
        $rf433cmd = substr($message, 36, 42);
        $cmdReic = substr($message, 48, 50);
        if ($cmdReic == "00") {
          $rf433status = 0;
        } else {
          $rf433status = 1;
        }
        log::add('orvibo', 'debug', 'Recu : Changement de statut pour RF433 ' . $rf433cmd . ' valeur ' . $cmdReic . ' sur ' . $mac);
      }
      break;

      case "6469": // DI, Button pressed
      if (is_object($orvibo)) {
        $orvibo->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $orvibo->save();
        $orviboCmd = orviboCmd::byEqLogicIdAndLogicalId($orvibo->getId(),'activity');
        if (is_object($orviboCmd)) {
          $orviboCmd->setConfiguration('value', 'button');
          $orviboCmd->save();
          $orviboCmd->event('button');
        }
      }
      log::add('orvibo', 'debug', 'Recu : Bouton appuyé sur ' . $mac);
      break;

      case "6c73":
      $codeIR = substr($message, 52);
      log::add('orvibo', 'debug', 'Code ' . $codeIR);
      // 686400186c73accf232a5ffa202020202020000000000000
      if (strlen($message) >= 52) {
        $codeIR = substr($message, 52);
        log::add('orvibo', 'debug', 'Recu : IR ' . $codeIR . ' sur ' . $mac);
        orvibo::saveCode($codeIR,$mac);
        $orvibo->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $orvibo->save();
        $orviboCmd = orviboCmd::byEqLogicIdAndLogicalId($orvibo->getId(),'activity');
        if (is_object($orviboCmd)) {
          $orviboCmd->setConfiguration('value', $codeIR);
          $orviboCmd->save();
          $orviboCmd->event($codeIR);
          log::add('orvibo', 'debug', 'Activité enregistrée');
        }
      }
      break;

      default: // No message? Return true
      log::add('orvibo', 'debug', 'Recu : Message inconnu ' . $message);
      break;

    }
    if ($orvibo->getConfiguration('learning') == '1') {
      log::add('orvibo', 'debug', 'On remet la allone en apprentissage ' . $mac);
      orvibo::EnterLearningMode($mac);
    }

  }

  // SendMessage is the heart of our library. Sends UDP messages to specified IP addresses
  public static function SendMessage($msg, $mac) {
    // Turn this hex string into bytes for sending
    //buf, _ := hex.DecodeString(msg)
    $orvibo = self::byLogicalId($mac, 'orvibo');
    $ip = $orvibo->getConfiguration('addr');
    $trad = orvibo::HexStringToArray($msg);
    log::add('orvibo', 'debug', 'Envoi de ' . $msg . ' à ' . $ip);
    $sock = socket_create(AF_INET, SOCK_DGRAM, 0);

    // Actually write the data and send it off
    if( ! socket_sendto($sock, $msg , strlen($msg) , 0 , $ip , '10000')) {
      $errorcode = socket_last_error();
      $errormsg = socket_strerror($errorcode);
      die("Could not send data: [$errorcode] $errormsg \n");
      log::add('orvibo', 'error', 'Envoi impossible :  ' . $errorcode . ', avec message ' . $errormsg);
    } else {
      log::add('orvibo', 'debug', 'Envoi ok');
    }
    socket_close($sock);
  }

  // broadcastMessage is another core part of our code. It lets us broadcast a message to the whole network.
  // It's essentially SendMessage with a IPv4 Broadcast address
  public static function broadcastMessage($msg) {
    $sock = socket_create(AF_INET, SOCK_DGRAM, 0);
    socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
    if( ! socket_sendto($sock, $msg , strlen($msg) , 0 , '255.255.255.255' , '10000')) {
      $errorcode = socket_last_error();
      $errormsg = socket_strerror($errorcode);
      die("Could not send data: [$errorcode] $errormsg \n");
      log::add('orvibo', 'error', 'Envoi impossible :  ' . $errorcode . ', avec message ' . $errormsg);
    } else {
      log::add('orvibo', 'debug', 'Commande broadcast : ' . $msg);
    }
    socket_close($sock);
  }

  // reverseMAC takes a MAC address and reverses each pair (e.g. AC CF 23 becomes CA FC 32)
  public static function reverseMAC($mac) {
    $res=array_reverse(orvibo::HexStringToArray($mac));
    return $res;
  }

  public static function makePayload($data) {
    $res='';
    foreach($data as $v) {
      $res.=chr($v);
    }
    return $res;
  }

  public static function HexStringToArray($buf) {
    $res=array();
    for($i=0;$i<strlen($buf)-1;$i+=2) {
      $res[]=(hexdec($buf[$i].$buf[$i+1]));
    }
    return $res;
  }

  public static function HexStringToString($buf) {
    $res='';
    for($i=0;$i<strlen($buf)-1;$i+=2) {
      $res.=chr(hexdec($buf[$i].$buf[$i+1]));
    }
    return $res;
  }

  public static function binaryToString($buf) {
    $res='';
    for($i=0;$i<strlen($buf);$i++) {
      $num=dechex(ord($buf[$i]));
      if (strlen($num)==1) {
        $num='0'.$num;
      }
      $res.=$num;
    }
    return $res;
  }

  public function getInfo($_infos = '') {
    $return = array();
    $number = 0;
    $eqLogics = eqLogic::byType('orvibo');

    foreach ($eqLogics as $eqLogic) {
      if ($eqLogic->getConfiguration('type', '') == 'allone') {
        $number++;
      }
    }

    $cmds = $this->getCmd();
    $total = count($cmds);

    $return['id'] = array(
      'value' => $this->getConfiguration('id', ''),
    );
    $return['mac'] = array(
      'value' => $this->getConfiguration('mac', ''),
    );
    $return['addr'] = array(
      'value' => $this->getConfiguration('addr', ''),
    );
    $return['type'] = array(
      'value' => $this->getConfiguration('type', ''),
    );
    $return['query'] = array(
      'value' => $this->getConfiguration('query', ''),
    );
    $return['subscribe'] = array(
      'value' => $this->getConfiguration('subscribe', ''),
    );
    $return['lastActivity'] = array(
      'value' => $this->getConfiguration('updatetime', ''),
    );
    $return['command'] = array(
      'value' => $this->getConfiguration('command', ''),
    );
    $return['learning'] = array(
      'value' => $this->getConfiguration('learning', ''),
    );
    $return['number'] = array(
      'value' => $number,
    );
    $return['total'] = array(
      'value' => $total,
    );
    $return['id'] = array(
      'value' => $this->getId(),
    );

    return $return;
  }

}

class orviboCmd extends cmd {
  /*     * *************************Attributs****************************** */



  /*     * ***********************Methode static*************************** */

  /*     * *********************Methode d'instance************************* */
  public function execute($_options = null) {
    switch ($this->getType()) {
      case 'info' :
      return $this->getConfiguration('value');
      break;

      case 'action' :
      $eqLogic = $this->getEqLogic();
      if ($this->getConfiguration('rfid', '') != '') {
        orvibo::EmitRF($eqLogic->getConfiguration('mac'),$this->getConfiguration('rfid'),$this->getConfiguration('order'));
        return true;
      }
      if ($eqLogic->getConfiguration('type', '') == 'socket') {
        orvibo::SocketState($this->getConfiguration('order'),$eqLogic->getConfiguration('mac'));
        return true;
      }
      if ($this->getConfiguration('order') == 'learning') {
        orvibo::EnterLearningMode($eqLogic->getConfiguration('mac'));
      } else {
        orvibo::EmitIR($this->getConfiguration('order'),$eqLogic->getConfiguration('mac'));
      }
      return true;
    }

  }


}

?>
