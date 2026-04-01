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

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

/*
 * ========================
 *  Overkiz Cloud API Client
 * ========================
 */
class OverkizCloudAPI {
    private $server;
    private $baseUrl;
    private $jsessionId;

    private static $SERVERS = array(
        'ha101-1.overkiz.com' => 'Somfy Europe',
        'ha201-1.overkiz.com' => 'Somfy Australia',
        'ha401-1.overkiz.com' => 'Somfy North America',
    );

    public function __construct($server = 'ha101-1.overkiz.com') {
        $this->server = $server;
        $this->baseUrl = 'https://' . $server . '/enduser-mobile-web/enduserAPI';
        $cachedSession = cache::byKey('somfycloud::jsessionid')->getValue('');
        if ($cachedSession !== '') {
            $this->jsessionId = $cachedSession;
        }
    }

    public static function getServers() {
        return self::$SERVERS;
    }

    /**
     * Authenticate with Somfy Connect credentials.
     * Returns true on success, throws on failure.
     */
    public function login($email, $password) {
        log::add('somfycloud', 'debug', 'Logging in to Overkiz cloud as ' . $email);
        $response = $this->httpRequest('/login', 'POST', array(
            'userId'       => $email,
            'userPassword' => $password,
        ), true);

        if ($response['httpCode'] !== 200) {
            $body = is_string($response['body']) ? $response['body'] : json_encode($response['body']);
            throw new Exception(__('Echec de connexion Overkiz: ', __FILE__) . $body);
        }

        // JSESSIONID is extracted from response cookies in httpRequest
        if (empty($this->jsessionId)) {
            throw new Exception(__('Pas de cookie JSESSIONID recu', __FILE__));
        }

        // Cache the session for 1 hour
        cache::set('somfycloud::jsessionid', $this->jsessionId, 3600);
        log::add('somfycloud', 'info', 'Successfully logged in to Overkiz cloud');
        return true;
    }

    /**
     * Ensure we have a valid session; login if needed.
     */
    public function ensureLoggedIn() {
        if (!empty($this->jsessionId)) {
            return;
        }
        $email = config::byKey('email', 'somfycloud', '');
        $password = config::byKey('password', 'somfycloud', '');
        if ($email === '' || $password === '') {
            throw new Exception(__('Email ou mot de passe Somfy non configure', __FILE__));
        }
        $this->login($email, $password);
    }

    /**
     * GET /setup — returns the full device setup.
     */
    public function getSetup() {
        $this->ensureLoggedIn();
        $response = $this->apiRequest('/setup', 'GET');
        return $response;
    }

    /**
     * POST /exec/apply — execute a command on a device.
     */
    public function execute($deviceURL, $command, $params = array()) {
        $this->ensureLoggedIn();
        $body = array(
            'label'   => 'Jeedom - ' . $command,
            'actions' => array(
                array(
                    'deviceURL' => $deviceURL,
                    'commands'  => array(
                        array(
                            'name'       => $command,
                            'parameters' => $params,
                        ),
                    ),
                ),
            ),
        );
        log::add('somfycloud', 'info', 'Executing command ' . $command . ' on ' . $deviceURL . ' with params: ' . json_encode($params));
        $response = $this->apiRequest('/exec/apply', 'POST', $body);
        return $response;
    }

    /**
     * POST /events/register — register an event listener.
     */
    public function registerEventListener() {
        $this->ensureLoggedIn();
        $response = $this->apiRequest('/events/register', 'POST');
        if (isset($response['id'])) {
            cache::set('somfycloud::listenerId', $response['id'], 600);
            log::add('somfycloud', 'debug', 'Registered event listener: ' . $response['id']);
        }
        return $response;
    }

    /**
     * POST /events/{listenerId}/fetch — fetch events from listener.
     */
    public function fetchEvents($listenerId) {
        $this->ensureLoggedIn();
        $response = $this->apiRequest('/events/' . $listenerId . '/fetch', 'POST');
        return $response;
    }

    /**
     * POST /setup/devices/states/refresh — force state refresh.
     */
    public function refreshStates() {
        $this->ensureLoggedIn();
        $response = $this->apiRequest('/setup/devices/states/refresh', 'POST');
        return $response;
    }

    /**
     * GET /exec/current — get currently running executions.
     */
    public function getCurrentExecutions() {
        $this->ensureLoggedIn();
        return $this->apiRequest('/exec/current', 'GET');
    }

    /**
     * Make an API request with automatic 401 re-login.
     */
    private function apiRequest($endpoint, $method = 'GET', $body = null) {
        $response = $this->httpRequest($endpoint, $method, $body, false);

        // On 401, clear session and retry after re-login
        if ($response['httpCode'] === 401) {
            log::add('somfycloud', 'debug', 'Got 401, re-logging in');
            $this->jsessionId = null;
            cache::delete('somfycloud::jsessionid');
            $this->ensureLoggedIn();
            $response = $this->httpRequest($endpoint, $method, $body, false);
        }

        if ($response['httpCode'] < 200 || $response['httpCode'] >= 300) {
            $errorMsg = 'Overkiz API error ' . $response['httpCode'] . ' on ' . $endpoint;
            if (is_array($response['body']) && isset($response['body']['error'])) {
                $errorMsg .= ': ' . $response['body']['error'];
            } elseif (is_string($response['body'])) {
                $errorMsg .= ': ' . substr($response['body'], 0, 200);
            }
            log::add('somfycloud', 'error', $errorMsg);
            throw new Exception($errorMsg);
        }

        return $response['body'];
    }

    /**
     * Low-level HTTP request using curl.
     *
     * @param string $endpoint   API endpoint path (e.g. /login)
     * @param string $method     HTTP method
     * @param mixed  $body       Request body (array for JSON or form data)
     * @param bool   $formEncoded If true, send as application/x-www-form-urlencoded
     * @return array  ['httpCode' => int, 'body' => mixed]
     */
    private function httpRequest($endpoint, $method = 'GET', $body = null, $formEncoded = false) {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $headers = array();

        // Set JSESSIONID cookie if available
        if (!empty($this->jsessionId)) {
            curl_setopt($ch, CURLOPT_COOKIE, 'JSESSIONID=' . $this->jsessionId);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                if ($formEncoded) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
                    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                    $headers[] = 'Content-Type: application/json';
                }
            }
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $headers[] = 'Accept: application/json';
        $headers[] = 'User-Agent: Jeedom-SomfyCloud/1.0';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $rawResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            log::add('somfycloud', 'error', 'Curl error: ' . $error);
            throw new Exception(__('Erreur de connexion: ', __FILE__) . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $responseHeaders = substr($rawResponse, 0, $headerSize);
        $responseBody = substr($rawResponse, $headerSize);

        // Extract JSESSIONID from Set-Cookie header
        if (preg_match('/Set-Cookie:\s*JSESSIONID=([^;]+)/i', $responseHeaders, $matches)) {
            $this->jsessionId = $matches[1];
            log::add('somfycloud', 'debug', 'Got new JSESSIONID');
        }

        // Try to decode JSON
        $decoded = json_decode($responseBody, true);
        if ($decoded !== null || $responseBody === 'null') {
            $responseBody = $decoded;
        }

        return array(
            'httpCode' => $httpCode,
            'body'     => $responseBody,
        );
    }
}

/*
 * ========================
 *  somfycloud eqLogic class
 * ========================
 */
class somfycloud extends eqLogic {

    // Encrypt the password field in plugin configuration
    public static $_encryptConfigKey = array('password');

    /**
     * Device type to command mapping.
     * Each entry: 'uiClass' => array of commands with their config.
     */
    private static $DEVICE_COMMANDS = array(
        'RollerShutter' => array(
            array('name' => 'open',     'logicalId' => 'open',     'type' => 'action', 'subType' => 'other', 'order' => 1, 'icon' => 'fas fa-arrow-up'),
            array('name' => 'close',    'logicalId' => 'close',    'type' => 'action', 'subType' => 'other', 'order' => 2, 'icon' => 'fas fa-arrow-down'),
            array('name' => 'stop',     'logicalId' => 'stop',     'type' => 'action', 'subType' => 'other', 'order' => 3, 'icon' => 'fas fa-stop'),
            array('name' => 'my',       'logicalId' => 'my',       'type' => 'action', 'subType' => 'other', 'order' => 4, 'icon' => 'fas fa-heart'),
            array('name' => 'Positionner', 'logicalId' => 'position', 'type' => 'action', 'subType' => 'slider', 'order' => 5, 'icon' => 'fas fa-sliders-h',
                  'config' => array('minValue' => 0, 'maxValue' => 100)),
            array('name' => 'Etat position', 'logicalId' => 'position_state', 'type' => 'info', 'subType' => 'numeric', 'order' => 0,
                  'config' => array('minValue' => 0, 'maxValue' => 100),
                  'state' => 'core:ClosureState', 'unite' => '%'),
        ),
        'ExteriorScreen' => 'RollerShutter',  // alias
        'Screen'         => 'RollerShutter',  // alias
        'Awning'         => 'RollerShutter',  // alias
        'Pergola'        => 'RollerShutter',  // alias
        'GarageDoor'     => 'RollerShutter',  // alias
        'Gate'           => 'RollerShutter',  // alias
        'Shutter'        => 'RollerShutter',  // alias
        'Window'         => 'RollerShutter',  // alias
        'VenetianBlind'  => array(
            array('name' => 'open',     'logicalId' => 'open',     'type' => 'action', 'subType' => 'other', 'order' => 1, 'icon' => 'fas fa-arrow-up'),
            array('name' => 'close',    'logicalId' => 'close',    'type' => 'action', 'subType' => 'other', 'order' => 2, 'icon' => 'fas fa-arrow-down'),
            array('name' => 'stop',     'logicalId' => 'stop',     'type' => 'action', 'subType' => 'other', 'order' => 3, 'icon' => 'fas fa-stop'),
            array('name' => 'my',       'logicalId' => 'my',       'type' => 'action', 'subType' => 'other', 'order' => 4, 'icon' => 'fas fa-heart'),
            array('name' => 'Positionner', 'logicalId' => 'position', 'type' => 'action', 'subType' => 'slider', 'order' => 5, 'icon' => 'fas fa-sliders-h',
                  'config' => array('minValue' => 0, 'maxValue' => 100)),
            array('name' => 'Orienter', 'logicalId' => 'orientation', 'type' => 'action', 'subType' => 'slider', 'order' => 6, 'icon' => 'fas fa-sync-alt',
                  'config' => array('minValue' => 0, 'maxValue' => 100)),
            array('name' => 'Etat position', 'logicalId' => 'position_state', 'type' => 'info', 'subType' => 'numeric', 'order' => 0,
                  'config' => array('minValue' => 0, 'maxValue' => 100),
                  'state' => 'core:ClosureState', 'unite' => '%'),
            array('name' => 'Etat orientation', 'logicalId' => 'orientation_state', 'type' => 'info', 'subType' => 'numeric', 'order' => 0,
                  'config' => array('minValue' => 0, 'maxValue' => 100),
                  'state' => 'core:SlateOrientationState', 'unite' => '%'),
        ),
        'Light' => array(
            array('name' => 'on',    'logicalId' => 'on',    'type' => 'action', 'subType' => 'other', 'order' => 1, 'icon' => 'fas fa-lightbulb'),
            array('name' => 'off',   'logicalId' => 'off',   'type' => 'action', 'subType' => 'other', 'order' => 2, 'icon' => 'far fa-lightbulb'),
            array('name' => 'Etat',  'logicalId' => 'state', 'type' => 'info',   'subType' => 'binary', 'order' => 0,
                  'state' => 'core:OnOffState'),
        ),
    );

    /**
     * Default commands for unknown device types.
     */
    private static $DEFAULT_COMMANDS = array(
        array('name' => 'open',  'logicalId' => 'open',  'type' => 'action', 'subType' => 'other', 'order' => 1, 'icon' => 'fas fa-arrow-up'),
        array('name' => 'close', 'logicalId' => 'close', 'type' => 'action', 'subType' => 'other', 'order' => 2, 'icon' => 'fas fa-arrow-down'),
        array('name' => 'stop',  'logicalId' => 'stop',  'type' => 'action', 'subType' => 'other', 'order' => 3, 'icon' => 'fas fa-stop'),
    );

    /*     * ***********************Methodes statiques*************************** */

    /**
     * Get the API client instance.
     */
    public static function getAPI() {
        $server = config::byKey('server', 'somfycloud', 'ha101-1.overkiz.com');
        return new OverkizCloudAPI($server);
    }

    /**
     * Recursively build a map of placeOID => label from the rootPlace tree.
     */
    private static function buildPlaceMap($place, &$map) {
        if (isset($place['oid']) && isset($place['label'])) {
            $map[$place['oid']] = $place['label'];
        }
        if (isset($place['subPlaces']) && is_array($place['subPlaces'])) {
            foreach ($place['subPlaces'] as $sub) {
                self::buildPlaceMap($sub, $map);
            }
        }
    }

    /**
     * Resolve command definitions for a given uiClass.
     */
    private static function getCommandsForUIClass($uiClass) {
        if (isset(self::$DEVICE_COMMANDS[$uiClass])) {
            $cmds = self::$DEVICE_COMMANDS[$uiClass];
            // Follow alias
            if (is_string($cmds)) {
                $cmds = self::$DEVICE_COMMANDS[$cmds];
            }
            return $cmds;
        }
        return self::$DEFAULT_COMMANDS;
    }

    /**
     * Synchronize devices from Overkiz cloud.
     */
    public static function syncDevices() {
        log::add('somfycloud', 'info', 'Starting device synchronization');
        $api = self::getAPI();
        $setup = $api->getSetup();

        if (!isset($setup['devices']) || !is_array($setup['devices'])) {
            throw new Exception(__('Aucun equipement trouve dans la reponse Overkiz', __FILE__));
        }

        // Build a place OID => place label map from the root place tree
        $placeMap = array();
        if (isset($setup['rootPlace'])) {
            self::buildPlaceMap($setup['rootPlace'], $placeMap);
        }

        $count = 0;
        foreach ($setup['devices'] as $device) {
            $deviceURL = $device['deviceURL'];
            $label = isset($device['label']) ? $device['label'] : 'Unknown';
            $controllableName = isset($device['controllableName']) ? $device['controllableName'] : '';
            $uiClass = isset($device['uiClass']) ? $device['uiClass'] : '';

            // Prepend room name if available
            $placeOID = isset($device['placeOID']) ? $device['placeOID'] : '';
            if ($placeOID !== '' && isset($placeMap[$placeOID])) {
                $label = $placeMap[$placeOID] . ' - ' . $label;
            }

            // Skip internal protocol gateway devices
            if (strpos($deviceURL, 'internal://') === 0) {
                log::add('somfycloud', 'debug', 'Skipping internal device: ' . $deviceURL);
                continue;
            }

            log::add('somfycloud', 'info', 'Syncing device: ' . $label . ' (' . $uiClass . ') - ' . $deviceURL);

            // Find or create eqLogic
            $eqLogic = self::byLogicalId($deviceURL, 'somfycloud');
            if (!is_object($eqLogic)) {
                $eqLogic = new self();
                $eqLogic->setEqType_name('somfycloud');
                $eqLogic->setLogicalId($deviceURL);
                $eqLogic->setName($label);
                $eqLogic->setIsEnable(1);
            }

            $eqLogic->setConfiguration('deviceURL', $deviceURL);
            $eqLogic->setConfiguration('controllableName', $controllableName);
            $eqLogic->setConfiguration('uiClass', $uiClass);

            // Store available states for later reference
            if (isset($device['states']) && is_array($device['states'])) {
                $stateNames = array();
                foreach ($device['states'] as $state) {
                    if (isset($state['name'])) {
                        $stateNames[] = $state['name'];
                    }
                }
                $eqLogic->setConfiguration('availableStates', implode(',', $stateNames));
            }

            // Store available commands for reference
            if (isset($device['definition']['commands']) && is_array($device['definition']['commands'])) {
                $cmdNames = array();
                foreach ($device['definition']['commands'] as $cmd) {
                    if (isset($cmd['commandName'])) {
                        $cmdNames[] = $cmd['commandName'];
                    }
                }
                $eqLogic->setConfiguration('availableCommands', implode(',', $cmdNames));
            }

            $eqLogic->save();

            // Create commands for this device
            self::createCommandsForDevice($eqLogic, $uiClass);

            // Update info command values from current states
            if (isset($device['states']) && is_array($device['states'])) {
                self::updateStatesFromDevice($eqLogic, $device['states']);
            }

            $count++;
        }

        log::add('somfycloud', 'info', 'Synchronization complete: ' . $count . ' devices synced');
        return $count;
    }

    /**
     * Create commands for a device based on its uiClass.
     */
    private static function createCommandsForDevice($eqLogic, $uiClass) {
        $commandDefs = self::getCommandsForUIClass($uiClass);

        foreach ($commandDefs as $cmdDef) {
            $cmd = $eqLogic->getCmd(null, $cmdDef['logicalId']);
            if (!is_object($cmd)) {
                $cmd = new somfycloudCmd();
                $cmd->setLogicalId($cmdDef['logicalId']);
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setName($cmdDef['name']);
                $cmd->setType($cmdDef['type']);
                $cmd->setSubType($cmdDef['subType']);
                $cmd->setOrder($cmdDef['order']);
                if (isset($cmdDef['icon'])) {
                    $cmd->setDisplay('icon', '<i class="' . $cmdDef['icon'] . '"></i>');
                }
                if (isset($cmdDef['config'])) {
                    foreach ($cmdDef['config'] as $key => $value) {
                        $cmd->setConfiguration($key, $value);
                    }
                }
                if (isset($cmdDef['unite'])) {
                    $cmd->setUnite($cmdDef['unite']);
                }
                // Link slider action to its info command for value display
                if ($cmdDef['type'] === 'action' && $cmdDef['subType'] === 'slider') {
                    $infoCmd = $eqLogic->getCmd('info', $cmdDef['logicalId'] . '_state');
                    if (is_object($infoCmd)) {
                        $cmd->setValue($infoCmd->getId());
                    }
                }
                $cmd->save();
            }
        }

        // Now re-link sliders to info commands (in case info was created after action)
        foreach ($commandDefs as $cmdDef) {
            if ($cmdDef['type'] === 'action' && $cmdDef['subType'] === 'slider') {
                $actionCmd = $eqLogic->getCmd('action', $cmdDef['logicalId']);
                $infoCmd = $eqLogic->getCmd('info', $cmdDef['logicalId'] . '_state');
                if (is_object($actionCmd) && is_object($infoCmd) && $actionCmd->getValue() != $infoCmd->getId()) {
                    $actionCmd->setValue($infoCmd->getId());
                    $actionCmd->save();
                }
            }
        }
    }

    /**
     * Update info command values from device states.
     */
    private static function updateStatesFromDevice($eqLogic, $states) {
        $uiClass = $eqLogic->getConfiguration('uiClass', '');
        $commandDefs = self::getCommandsForUIClass($uiClass);

        foreach ($commandDefs as $cmdDef) {
            if ($cmdDef['type'] !== 'info' || !isset($cmdDef['state'])) {
                continue;
            }
            foreach ($states as $state) {
                if (isset($state['name']) && $state['name'] === $cmdDef['state'] && isset($state['value'])) {
                    $cmd = $eqLogic->getCmd('info', $cmdDef['logicalId']);
                    if (is_object($cmd)) {
                        $value = $state['value'];
                        // Convert OnOffState to binary
                        if ($cmdDef['subType'] === 'binary') {
                            $value = ($value === 'on' || $value === 'ON' || $value === true || $value === 1) ? 1 : 0;
                        } else {
                            $value = round(floatval($value));
                        }
                        $eqLogic->checkAndUpdateCmd($cmd, $value);
                        log::add('somfycloud', 'debug', 'Updated ' . $eqLogic->getName() . ' ' . $cmdDef['logicalId'] . ' = ' . $value);
                    }
                }
            }
        }
    }

    /**
     * Cron polling: refresh device states every 5 minutes.
     */
    public static function poll() {
        log::add('somfycloud', 'debug', 'Starting poll cycle');

        try {
            $api = self::getAPI();

            // Try refreshing states first, then fetch full setup
            try {
                $api->refreshStates();
                // Small delay to let the gateway refresh
                sleep(2);
            } catch (Exception $e) {
                log::add('somfycloud', 'debug', 'State refresh failed (non-critical): ' . $e->getMessage());
            }

            $setup = $api->getSetup();
            if (!isset($setup['devices']) || !is_array($setup['devices'])) {
                log::add('somfycloud', 'warning', 'No devices in poll response');
                return;
            }

            foreach ($setup['devices'] as $device) {
                $deviceURL = $device['deviceURL'];
                $eqLogic = self::byLogicalId($deviceURL, 'somfycloud');
                if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
                    continue;
                }
                if (isset($device['states']) && is_array($device['states'])) {
                    self::updateStatesFromDevice($eqLogic, $device['states']);
                }
            }

            log::add('somfycloud', 'debug', 'Poll cycle complete');
        } catch (Exception $e) {
            log::add('somfycloud', 'error', 'Poll error: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to Overkiz cloud.
     * Returns a status message.
     */
    public static function testConnection() {
        $email = config::byKey('email', 'somfycloud', '');
        $password = config::byKey('password', 'somfycloud', '');
        $server = config::byKey('server', 'somfycloud', 'ha101-1.overkiz.com');

        if ($email === '' || $password === '') {
            throw new Exception(__('Veuillez configurer l\'email et le mot de passe', __FILE__));
        }

        $api = new OverkizCloudAPI($server);
        $api->login($email, $password);

        // Try to get setup to verify full access
        $setup = $api->getSetup();
        $deviceCount = isset($setup['devices']) ? count($setup['devices']) : 0;

        return __('Connexion reussie ! ', __FILE__) . $deviceCount . __(' equipement(s) trouve(s).', __FILE__);
    }

    /*     * *********************Methodes d'instance************************* */

    public function preInsert() {
    }

    public function postInsert() {
    }

    public function preSave() {
    }

    public function postSave() {
    }

    public function preUpdate() {
    }

    public function postUpdate() {
    }

    public function preRemove() {
    }

    public function postRemove() {
    }

    /*     * **********************Getteur Setteur*************************** */
}

/*
 * ========================
 *  somfycloudCmd class
 * ========================
 */
class somfycloudCmd extends cmd {

    /*     * ***********************Methodes statiques*************************** */

    /*     * *********************Methodes d'instance************************* */

    public function preSave() {
    }

    public function postSave() {
    }

    /**
     * Execute an action command.
     */
    public function execute($_options = array()) {
        if ($this->getType() !== 'action') {
            return;
        }

        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            log::add('somfycloud', 'warning', 'Equipment disabled or not found for command ' . $this->getLogicalId());
            return;
        }

        $deviceURL = $eqLogic->getConfiguration('deviceURL', '');
        if ($deviceURL === '') {
            throw new Exception(__('Pas de deviceURL configure pour cet equipement', __FILE__));
        }

        $logicalId = $this->getLogicalId();
        $api = somfycloud::getAPI();
        $params = array();

        switch ($logicalId) {
            case 'open':
                $api->execute($deviceURL, 'open');
                break;
            case 'close':
                $api->execute($deviceURL, 'close');
                break;
            case 'stop':
                $api->execute($deviceURL, 'stop');
                break;
            case 'my':
                $api->execute($deviceURL, 'my');
                break;
            case 'on':
                $api->execute($deviceURL, 'on');
                break;
            case 'off':
                $api->execute($deviceURL, 'off');
                break;
            case 'position':
                $value = isset($_options['slider']) ? intval($_options['slider']) : 0;
                $api->execute($deviceURL, 'setClosure', array($value));
                break;
            case 'orientation':
                $value = isset($_options['slider']) ? intval($_options['slider']) : 0;
                $api->execute($deviceURL, 'setOrientation', array($value));
                break;
            default:
                // Try to execute as a raw command name
                $api->execute($deviceURL, $logicalId, $params);
                break;
        }

        // Schedule a delayed state refresh
        // (states don't update instantly on the cloud)
        log::add('somfycloud', 'debug', 'Command ' . $logicalId . ' executed on ' . $eqLogic->getName());
    }

    /*     * **********************Getteur Setteur*************************** */
}
