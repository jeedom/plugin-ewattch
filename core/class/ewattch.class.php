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

class ewattch extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function health() {
		$return = array();
		$cron = cron::byClassAndFunction('ewattch', 'pull');
		$running = false;
		if (is_object($cron)) {
			$running = $cron->running();
		}
		$return[] = array(
			'test' => __('Tâche de synchronisation', __FILE__),
			'result' => ($running) ? __('OK', __FILE__) : __('NOK', __FILE__),
			'advice' => ($running) ? '' : __('Allez sur la page du moteur des tâches et vérifiez lancer la tache ewattch::pull', __FILE__),
			'state' => $running,
		);
		return $return;
	}

	public static function pull() {
		try {
			$request_http = new com_http(config::byKey('superviseurIP', 'ewattch') . '/log.json?mode=1');
			$result = json_decode(trim(trim($request_http->exec(1, 1)), ','), true);
			if (config::byKey('numberFailed', 'ewattch', 0) > 0) {
				config::save('numberFailed', 0, 'ewattch');
			}
		} catch (Exception $e) {
			if (config::byKey('numberFailed', 'ewattch', 0) > 3) {
				log::add('ewattch', 'error', __('Erreur sur synchro ewattch ', __FILE__) . '(' . config::byKey('numberFailed', 'ewattch', 0) . ')' . $e->getMessage());
			} else {
				config::save('numberFailed', config::byKey('numberFailed', 'ewattch', 0) + 1, 'ewattch');
			}
			return;
		}
		print_r($result);
		if (isset($result[1][0])) {
			foreach ($result[1][0] as $resource) {
				$name = $resource[0];
				$eqLogic = self::byLogicalId('electricity_' . $name, 'ewattch');
				if (!is_object(($eqLogic))) {
					continue;
				}
				$index = $eqLogic->getCmd(null, 'index');
				if (is_object(($index))) {
					$value = $resource[7] + $index->getConfiguration('previous', 0);
					$index->event($value);
					if ($index->getConfiguration('lastPrevious') == '') {
						$index->setConfiguration('previous', 0);
						$index->setConfiguration('lastPrevious', date('Y-m-d'));
						$index->save();
					}
					if ($index->getConfiguration('lastPrevious', date('Y-m-d')) != date('Y-m-d')) {
						$index->setConfiguration('previous', $value);
						$index->setConfiguration('lastPrevious', date('Y-m-d'));
						$index->save();
					}
				}

				$cost = $eqLogic->getCmd(null, 'cost');
				if (is_object(($cost))) {
					$value = $resource[6] + $index->getConfiguration('previous', 0);
					$cost->event($value);
					if ($cost->getConfiguration('lastPrevious') == '') {
						$cost->setConfiguration('previous', 0);
						$cost->setConfiguration('lastPrevious', date('Y-m-d'));
						$cost->save();
					}
					if ($cost->getConfiguration('lastPrevious', date('Y-m-d')) != date('Y-m-d')) {
						$cost->setConfiguration('previous', $value);
						$cost->setConfiguration('lastPrevious', date('Y-m-d'));
						$cost->save();
					}
				}
			}
		}

		if (isset($result[1][1])) {
			foreach ($result[1][1] as $resource) {
				$name = $resource[0];
				$eqLogic = self::byLogicalId('water_' . $name, 'ewattch');
				if (!is_object(($eqLogic))) {
					continue;
				}
				$index = $eqLogic->getCmd(null, 'index');
				if (is_object(($index))) {
					$value = $resource[7] + $index->getConfiguration('previous', 0);
					$index->event($value);
					if ($index->getConfiguration('lastPrevious') == '') {
						$index->setConfiguration('previous', 0);
						$index->setConfiguration('lastPrevious', date('Y-m-d'));
						$index->save();
					}
					if ($index->getConfiguration('lastPrevious', date('Y-m-d')) != date('Y-m-d')) {
						$index->setConfiguration('previous', $value);
						$index->setConfiguration('lastPrevious', date('Y-m-d'));
						$index->save();
					}
				}
				$cost = $eqLogic->getCmd(null, 'cost');
				if (is_object(($cost))) {
					$value = $resource[6] + $index->getConfiguration('previous', 0);
					$cost->event($value);
					if ($cost->getConfiguration('lastPrevious') == '') {
						$cost->setConfiguration('previous', 0);
						$cost->setConfiguration('lastPrevious', date('Y-m-d'));
						$cost->save();
					}
					if ($cost->getConfiguration('lastPrevious', date('Y-m-d')) != date('Y-m-d')) {
						$cost->setConfiguration('previous', $value);
						$cost->setConfiguration('lastPrevious', date('Y-m-d'));
						$cost->save();
					}
				}
			}
		}
		try {
			$request_http = new com_http(config::byKey('superviseurIP', 'ewattch') . '/log.json?mode=10');
			$result = json_decode(trim(trim($request_http->exec(1, 1)), ','), true);
			if (config::byKey('numberFailed', 'ewattch', 0) > 0) {
				config::save('numberFailed', 0, 'ewattch');
			}
		} catch (Exception $e) {
			if (config::byKey('numberFailed', 'ewattch', 0) > 150) {
				log::add('ewattch', 'error', __('Erreur sur synchro ewattch ', __FILE__) . '(' . config::byKey('numberFailed', 'ewattch', 0) . ')' . $e->getMessage());
			} else {
				config::save('numberFailed', config::byKey('numberFailed', 'ewattch', 0) + 1, 'ewattch');
			}
		}

		foreach ($result['resource']['heating'] as $resource) {
			if (!isset($resource['index'])) {
				continue;
			}
			$eqLogic = self::byLogicalId('heating_' . $resource['name'], 'ewattch');
			if (!is_object(($eqLogic))) {
				continue;
			}
			$index = $eqLogic->getCmd(null, 'index');
			if (!is_object(($index))) {
				continue;
			}
			$index->event($resource['index'] - $previous);
		}

		foreach ($result['resource']['environment'] as $resource) {
			if (!isset($resource['value'])) {
				continue;
			}
			$eqLogic = self::byLogicalId('environment_' . $resource['name'], 'ewattch');
			if (!is_object(($eqLogic))) {
				continue;
			}
			$index = $eqLogic->getCmd(null, 'index');
			if (!is_object(($index))) {
				continue;
			}
			$index->event($resource['value']);
		}
	}

	public static function syncEwattch($_ip) {
		$request_http = new com_http($_ip . '/log.json?mode=10');
		$result = json_decode($request_http->exec(1, 1), true);
		foreach ($result['resource']['electricity'] as $resource) {
			$eqLogic = self::byLogicalId('electricity_' . $resource['name'], 'ewattch');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setName('electricity_' . $resource['name']);
				$eqLogic->setEqType_name('ewattch');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->setLogicalId('electricity_' . $resource['name']);
				$eqLogic->setConfiguration('ip', $_ip);
				$eqLogic->setCategory('energy', 1);
				$eqLogic->save();
			}
			$index = $eqLogic->getCmd(null, 'index');
			if (!is_object($index)) {
				$index = new ewattchCmd();
				$index->setLogicalId('index');
				$index->setIsVisible(1);
				$index->setName(__('Index', __FILE__));
			}
			$index->setUnite('wh');
			$index->setType('info');
			$index->setSubType('numeric');
			$index->setEventOnly(1);
			$index->setEqLogic_id($eqLogic->getId());
			$index->save();

			$cost = $eqLogic->getCmd(null, 'cost');
			if (!is_object($cost)) {
				$cost = new ewattchCmd();
				$cost->setLogicalId('cost');
				$cost->setIsVisible(1);
				$cost->setName(__('Coût', __FILE__));
			}
			$cost->setUnite('€');
			$cost->setType('info');
			$cost->setSubType('numeric');
			$cost->setEventOnly(1);
			$cost->setEqLogic_id($eqLogic->getId());
			$cost->save();
		}
		foreach ($result['resource']['water'] as $resource) {
			$eqLogic = self::byLogicalId('water_' . $resource['name'], 'ewattch');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setName('water_' . $resource['name']);
				$eqLogic->setEqType_name('ewattch');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->setLogicalId('water_' . $resource['name']);
				$eqLogic->setConfiguration('ip', $_ip);
				$eqLogic->setCategory('energy', 1);
				$eqLogic->save();
			}
			$index = $eqLogic->getCmd(null, 'index');
			if (!is_object($index)) {
				$index = new ewattchCmd();
				$index->setLogicalId('index');
				$index->setIsVisible(1);
				$index->setName(__('Index', __FILE__));
			}
			$index->setUnite('L');
			$index->setType('info');
			$index->setSubType('numeric');
			$index->setEventOnly(1);
			$index->setEqLogic_id($eqLogic->getId());
			$index->save();

			$cost = $eqLogic->getCmd(null, 'cost');
			if (!is_object($cost)) {
				$cost = new ewattchCmd();
				$cost->setLogicalId('cost');
				$cost->setIsVisible(1);
				$cost->setName(__('Coût', __FILE__));
			}
			$cost->setUnite('€');
			$cost->setType('info');
			$cost->setSubType('numeric');
			$cost->setEventOnly(1);
			$cost->setEqLogic_id($eqLogic->getId());
			$cost->save();
		}
		foreach ($result['resource']['heating'] as $resource) {
			$eqLogic = self::byLogicalId('heating_' . $resource['name'], 'ewattch');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setName('heating_' . $resource['name']);
				$eqLogic->setEqType_name('ewattch');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->setLogicalId('heating_' . $resource['name']);
				$eqLogic->setConfiguration('ip', $_ip);
				$eqLogic->setCategory('heating', 1);
				$eqLogic->save();
			}
			$index = $eqLogic->getCmd(null, 'index');
			if (!is_object($index)) {
				$index = new ewattchCmd();
				$index->setLogicalId('index');
				$index->setIsVisible(1);
				$index->setName(__('Index', __FILE__));
			}
			$index->setUnite('wh');
			$index->setType('info');
			$index->setSubType('numeric');
			$index->setEventOnly(1);
			$index->setEqLogic_id($eqLogic->getId());
			$index->save();
		}
		foreach ($result['resource']['environment'] as $resource) {
			$eqLogic = self::byLogicalId('environment_' . $resource['name'], 'ewattch');
			if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setName('environment_' . $resource['name']);
				$eqLogic->setEqType_name('ewattch');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->setLogicalId('environment_' . $resource['name']);
				$eqLogic->setConfiguration('ip', $_ip);
				$eqLogic->save();
			}
			$value = $eqLogic->getCmd(null, 'value');
			if (!is_object($value)) {
				$value = new ewattchCmd();
				$value->setLogicalId('value');
				$value->setIsVisible(1);
				$value->setName(__('Valeur', __FILE__));
			}
			$value->setUnite($resource['units']);
			$value->setType('info');
			$value->setSubType('numeric');
			$value->setEventOnly(1);
			$value->setEqLogic_id($eqLogic->getId());
			$value->save();
		}

	}

	/*     * **********************Getteur Setteur*************************** */
}

class ewattchCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		return true;
	}

	public function execute($_options = array()) {
		return '';
	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
