<?php
/*
 -------------------------------------------------------------------------
 GappEssentials plugin for GLPI
 Copyright (C) 2019 by TICgal.
 https://tic.gal
 https://github.com/pluginsGLPI/gappessentials
 -------------------------------------------------------------------------
 LICENSE
 This file is part of GappEssentials.
 GappEssentials is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 GappEssentials is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with GappEssentials. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

class PluginGappEssentialsApirest extends Glpi\Api\API
{
	protected $request_uri;
	protected $url_elements;
	protected $verb;
	protected $parameters;
	protected $debug = 0;
	protected $format = "json";

	public static function getTypeName($nb = 0)
	{
		return __('GappEssentials rest API', 'gappessentials');
	}

	public function manageUploadedFiles()
	{
		foreach (array_keys($_FILES) as $filename) {
			$rand_name = uniqid('', true);
			foreach ($_FILES[$filename]['name'] as &$name) {
				$name = $rand_name . $name;
			}
			$upload_result
				= GLPIUploadHandler::uploadFiles([
					'name'           => $filename,
					'print_response' => false
				]);
			foreach ($upload_result as $uresult) {
				$this->parameters['input']->_filename[] = $uresult[0]->name;
				$this->parameters['input']->_prefix_filename[] = $uresult[0]->prefix;
			}
			$this->parameters['upload_result'][] = $upload_result;
		}
	}

	public function parseIncomingParams($is_inline_doc = false)
	{

		$parameters = [];

		// first of all, pull the GET vars
		if (isset($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $parameters);
		}

		// now how about PUT/POST bodies? These override what we got from GET
		$body = trim($this->getHttpBody());
		if (strlen($body) > 0 && $this->verb == "GET") {
			// GET method requires an empty body
			$this->returnError(
				"GET Request should not have json payload (http body)",
				400,
				"ERROR_JSON_PAYLOAD_FORBIDDEN"
			);
		}

		$content_type = "";
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$content_type = $_SERVER['CONTENT_TYPE'];
		} else if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
			$content_type = $_SERVER['HTTP_CONTENT_TYPE'];
		} else {
			if (!$is_inline_doc) {
				$content_type = "application/json";
			}
		}

		if (strpos($content_type, "application/json") !== false) {
			if ($body_params = json_decode($body)) {
				foreach ($body_params as $param_name => $param_value) {
					$parameters[$param_name] = $param_value;
				}
			} else if (strlen($body) > 0) {
				$this->returnError(
					"JSON payload seems not valid",
					400,
					"ERROR_JSON_PAYLOAD_INVALID",
					false
				);
			}
			$this->format = "json";
		} else if (strpos($content_type, "multipart/form-data") !== false) {
			if (count($_FILES) <= 0) {
				// likely uploaded files is too big so $_REQUEST will be empty also.
				// see http://us.php.net/manual/en/ini.core.php#ini.post-max-size
				$this->returnError(
					"The file seems too big",
					400,
					"ERROR_UPLOAD_FILE_TOO_BIG_POST_MAX_SIZE",
					false
				);
			}

			// with this content_type, php://input is empty... (see http://php.net/manual/en/wrappers.php.php)
			if (!$uploadManifest = json_decode($_REQUEST['uploadManifest'])) {
				$this->returnError(
					"JSON payload seems not valid",
					400,
					"ERROR_JSON_PAYLOAD_INVALID",
					false
				);
			}
			foreach ($uploadManifest as $field => $value) {
				$parameters[$field] = $value;
			}
			$this->format = "json";

			// move files into _tmp folder
			$parameters['upload_result'] = [];
			$parameters['input']->_filename = [];
			$parameters['input']->_prefix_filename = [];
		} else if (strpos($content_type, "application/x-www-form-urlencoded") !== false) {
			/** @var array $postvars */
			parse_str($body, $postvars);
			foreach ($postvars as $field => $value) {
				$parameters[$field] = $value;
			}
			$this->format = "html";
		} else {
			$this->format = "html";
		}

		// retrieve HTTP headers
		$headers = [];
		if (function_exists('getallheaders')) {
			//apache specific
			$headers = getallheaders();
			if (false !== $headers && count($headers) > 0) {
				$fixedHeaders = [];
				foreach ($headers as $key => $value) {
					$fixedHeaders[ucwords(strtolower($key), '-')] = $value;
				}
				$headers = $fixedHeaders;
			}
		} else {
			// other servers
			foreach ($_SERVER as $server_key => $server_value) {
				if (substr($server_key, 0, 5) == 'HTTP_') {
					$headers[str_replace(
						' ',
						'-',
						ucwords(strtolower(str_replace(
							'_',
							' ',
							substr($server_key, 5)
						)))
					)] = $server_value;
				}
			}
		}

		// try to retrieve basic auth
		if (
			isset($_SERVER['PHP_AUTH_USER'])
			&& isset($_SERVER['PHP_AUTH_PW'])
		) {
			$parameters['login']    = $_SERVER['PHP_AUTH_USER'];
			$parameters['password'] = $_SERVER['PHP_AUTH_PW'];
		}

		// try to retrieve user_token in header
		if (
			isset($headers['Authorization'])
			&& (strpos($headers['Authorization'], 'user_token') !== false)
		) {
			$auth = explode(' ', $headers['Authorization']);
			if (isset($auth[1])) {
				$parameters['user_token'] = $auth[1];
			}
		}

		// try to retrieve session_token in header
		if (isset($headers['Session-Token'])) {
			$parameters['session_token'] = $headers['Session-Token'];
		}

		// try to retrieve app_token in header
		if (isset($headers['App-Token'])) {
			$parameters['app_token'] = $headers['App-Token'];
		}

		// check boolean parameters
		foreach ($parameters as $key => &$parameter) {
			if ($parameter === "true") {
				$parameter = true;
			}
			if ($parameter === "false") {
				$parameter = false;
			}
		}

		$this->parameters = $parameters;

		return "";
	}

	private function inputObjectToArray($input)
	{
		if (is_object($input)) {
			$input = get_object_vars($input);
		}

		if (is_array($input)) {
			foreach ($input as &$sub_input) {
				$sub_input = self::inputObjectToArray($sub_input);
			}
		}

		return $input;
	}

	protected function initEndpoint($unlock_session = true, $endpoint = "")
	{

		if ($endpoint === "") {
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$endpoint = $backtrace[1]['function'];
		}
		$this->checkAppToken();
		$this->logEndpointUsage($endpoint);
		self::checkSessionToken();
		if ($unlock_session) {
			self::unlockSessionIfPossible();
		}
	}

	/**
	 * Check if the app_toke in case of config ask to
	 *
	 * @return void
	 */
	private function checkAppToken()
	{

		// check app token (if needed)
		if (!isset($this->parameters['app_token'])) {
			$this->parameters['app_token'] = "";
		}
		if (!$this->apiclients_id = array_search($this->parameters['app_token'], $this->app_tokens)) {
			if ($this->parameters['app_token'] != "") {
				$this->returnError(__("parameter app_token seems wrong"), 400, "ERROR_WRONG_APP_TOKEN_PARAMETER");
			} else {
				$this->returnError(__("missing parameter app_token"), 400, "ERROR_APP_TOKEN_PARAMETERS_MISSING");
			}
		}
	}


	/**
	 * Log usage of the api into glpi historical or log files (defined by api config)
	 *
	 * It stores the ip and the username of the current session.
	 *
	 * @param string $endpoint function called by api to log (default '')
	 *
	 * @return void
	 */
	private function logEndpointUsage($endpoint = "")
	{

		$username = "";
		if (isset($_SESSION['glpiname'])) {
			$username = "(" . $_SESSION['glpiname'] . ")";
		}

		$apiclient = new APIClient;
		if ($apiclient->getFromDB($this->apiclients_id)) {
			$changes = [
				0,
				"",
				"Enpoint '$endpoint' called by " . $this->iptxt . " $username"
			];

			switch ($apiclient->fields['dolog_method']) {
				case APIClient::DOLOG_HISTORICAL:
					Log::history($this->apiclients_id, 'APIClient', $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);
					break;

				case APIClient::DOLOG_LOGS:
					Toolbox::logInFile("api", $changes[2] . "\n");
					break;
			}
		}
	}


	/**
	 * Unlock the current session (readonly) to permit concurrent call
	 *
	 * @return void
	 */
	private function unlockSessionIfPossible()
	{

		if (!$this->session_write) {
			session_write_close();
		}
	}

	private function getGlpiLastMessage()
	{
		global $DEBUG_SQL;

		$all_messages             = [];

		$messages_after_redirect  = [];

		if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"]) && count($_SESSION["MESSAGE_AFTER_REDIRECT"]) > 0) {
			$messages_after_redirect = $_SESSION["MESSAGE_AFTER_REDIRECT"];
			// Clean messages
			$_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
		};

		// clean html
		foreach ($messages_after_redirect as $messages) {
			foreach ($messages as $message) {
				$all_messages[] = Toolbox::stripTags($message);
			}
		}

		// get sql errors
		if (count($all_messages) <= 0 && $DEBUG_SQL['errors'] !== null) {
			$all_messages = $DEBUG_SQL['errors'];
		}

		if (!end($all_messages)) {
			return '';
		}
		return end($all_messages);
	}

	/**
	 * Retrieve in url_element the current id. If we have a multiple id (ex /Ticket/1/TicketFollwup/2),
	 * it always find the second
	 *
	 * @return integer|boolean id of current itemtype (or false if not found)
	 */
	private function getId()
	{

		$id            = isset($this->url_elements[1]) && is_numeric($this->url_elements[1])
			? intval($this->url_elements[1])
			: false;
		$additional_id = isset($this->url_elements[3]) && is_numeric($this->url_elements[3])
			? intval($this->url_elements[3])
			: false;

		if ($additional_id || isset($this->parameters['parent_itemtype'])) {
			$this->parameters['parent_id'] = $id;
			$id = $additional_id;
		}

		return $id;
	}



	private function pluginActivated()
	{

		$plugin = new Plugin();

		if (!$plugin->isActivated('gappessentials')) {
			$this->returnError("Plugin disabled", 400, "ERROR_PLUGIN_DISABLED");
		}
	}

	/**
	 * List of API ressources for which a valid session isn't required
	 *
	 * @return array
	 */
	protected function getRessourcesAllowedWithoutSession(): array
	{
		return [];
	}

	/**
	 * List of API ressources that may write php session data
	 *
	 * @return array
	 */
	protected function getRessourcesWithSessionWrite(): array
	{
		return [];
	}

	public function call()
	{

		$this->request_uri  = $_SERVER['REQUEST_URI'];
		$this->verb         = $_SERVER['REQUEST_METHOD'];
		$path_info          = (isset($_SERVER['PATH_INFO'])) ? str_replace("api/", "", trim($_SERVER['PATH_INFO'], '/')) : '';
		$this->url_elements = explode('/', $path_info);

		// retrieve requested resource
		$resource      = trim(strval($this->url_elements[0]));
		$is_inline_doc = (strlen($resource) == 0) || ($resource == "api");

		// Add headers for CORS
		$this->cors($this->verb);

		// retrieve paramaters (in body, query_string, headers)
		$this->parseIncomingParams($is_inline_doc);

		// show debug if required
		if (isset($this->parameters['debug'])) {
			$this->debug = $this->parameters['debug'];
			if (empty($this->debug)) {
				$this->debug = 1;
			}

			if ($this->debug >= 2) {
				$this->showDebug();
			}
		}

		// retrieve session (if exist)
		$this->retrieveSession();
		$this->initApi();
		$this->manageUploadedFiles();

		// retrieve param who permit session writing
		if (isset($this->parameters['session_write'])) {
			$this->session_write = (bool)$this->parameters['session_write'];
		}

		// Do not unlock the php session for ressources that may handle it
		if (in_array($resource, $this->getRessourcesWithSessionWrite())) {
			$this->session_write = true;
		}

		// Check API session unless blacklisted (init session, ...)
		if (!$is_inline_doc && !in_array($resource, $this->getRessourcesAllowedWithoutSession())) {
			$this->initEndpoint(true, $resource);
		}

		$this->pluginActivated();

		switch ($resource) {
			case 'pluginList':
				return $this->returnResponse($this->pluginList($this->parameters));
				break;
			case 'documentsTicket':
				return $this->returnResponse($this->documentsTicket($this->parameters));
				break;
			case 'basicInfo':
				return $this->returnResponse($this->basicInfo($this->parameters));
				break;
			case 'itilCategory':
				return $this->returnResponse($this->itilCategory($this->parameters));
				break;
			case 'location':
				return $this->returnResponse($this->location($this->parameters));
				break;
			default:
				$this->messageLostError();
				break;
		}
	}


	public function returnResponse($response, $httpcode = 200, $additionalheaders = [])
	{

		if (empty($httpcode)) {
			$httpcode = 200;
		}

		foreach ($additionalheaders as $key => $value) {
			header("$key: $value");
		}

		http_response_code($httpcode);
		$this->header($this->debug);

		if ($response !== null) {
			$json = json_encode($response, JSON_UNESCAPED_UNICODE
				| JSON_UNESCAPED_SLASHES
				| JSON_NUMERIC_CHECK
				| ($this->debug
					? JSON_PRETTY_PRINT
					: 0));
		} else {
			$json = '';
		}

		if ($this->debug) {
			echo "<pre>";
			var_dump($response);
			echo "</pre>";
		} else {
			echo $json;
		}
		exit;
	}


	protected function documentsTicket($params = [])
	{
		global $DB;

		$ID = Session::getLoginUserID();
		$ticket_id = $this->getId();
		$ticket = new Ticket();

		if (!$ticket->getFromDB($ticket_id)) {
			return $this->messageNotfoundError();
		}
		if (!$ticket->can($ticket_id, READ)) {
			return $this->messageRightError();
		}
		if (!isset($params['add_keys_names'])) {
			$params['add_keys_names'] = [];
		}
		$fields = [];
		$document = new Document();
		$document_item_obj = new Document_Item();
		$document_items = $document_item_obj->find([
			$ticket->getAssociatedDocumentsCriteria(),
			'timeline_position'  => ['>', CommonITILObject::NO_TIMELINE]
		]);
		foreach ($document_items as $document_item) {
			$document->getFromDB($document_item['documents_id']);
			$file = GLPI_DOC_DIR . "/" . $document->fields['filepath'];
			$data = $document->fields;
			$data['filesize']  = filesize($file);
			$data['date_mod']  = $document_item['date_mod'];
			$data['users_id']  = $document_item['users_id'];
			$data['timeline_position'] = $document_item['timeline_position'];
			$data['items_id'] = $document_item['items_id'];
			$data['itemtype'] = $document_item['itemtype'];
			$data['tickets_id'] = $ticket_id;
			if (count($params['add_keys_names']) > 0) {
				$data["_keys_names"] = $this->getFriendlyNames(
					$data,
					$params,
					$ticket->getType()
				);
			}
			$fields[] = $data;
		}

		$fields = self::parseDropdowns($fields, $params);

		return $fields;
	}



	protected function pluginList($params = [])
	{

		$plugin = new Plugin();
		return $plugin->getList();
	}


	protected function basicInfo($params = [])
	{
		global $DB;

		$info = [];
		$info['documenttype'] = [];

		$info['max_size'] = Toolbox::return_bytes_from_ini_vars(ini_get("upload_max_filesize"));

		$sql = [
			'SELECT' => [
				'name',
				'ext',
				'mime'
			],
			'FROM' => 'glpi_documenttypes',
			'WHERE' => [
				'is_uploadable' => 1
			]
		];
		$iterator = $DB->request($sql);
		foreach ($iterator as $data) {
			$info['documenttype'][] = $data;
		}

		return $info;
	}

	protected function itilCategory($params = [])
	{
		global $DB;

		$info = [];
		$item = new ITILCategory();
		$query = [
			'SELECT' => [
				'id',
				'name',
				'completename',
				'is_incident',
				'is_request',
				'is_helpdeskvisible',
				'level',
				'itilcategories_id',
				'comment'
			],
			'FROM' => 'glpi_itilcategories',
			'WHERE' => getEntitiesRestrictCriteria('glpi_itilcategories', '', $_SESSION['glpiactive_entity'], $item->maybeRecursive()),
			'ORDER' => 'completename ASC'
		];
		if (isset($params['is_helpdeskvisible'])) {
			$query['WHERE']['is_helpdeskvisible'] = $params['is_helpdeskvisible'];
		}
		if (isset($params['is_incident'])) {
			$query['WHERE']['is_incident'] = $params['is_incident'];
		}
		if (isset($params['is_request'])) {
			$query['WHERE']['is_request'] = $params['is_request'];
		}
		if ($result = $DB->request($query)) {
			foreach ($result as $data) {
				$info[] = $data;
			}
		}

		return $info;
	}


	protected function location($params = [])
	{
		global $DB;

		$info = [];
		$item = new Location();
		$query = [
			'SELECT' => [
				'id',
				'name',
				'completename',
				'level',
				'locations_id'
			],
			'FROM' => 'glpi_locations',
			'WHERE' => getEntitiesRestrictCriteria('glpi_locations', '', $_SESSION['glpiactive_entity'], $item->maybeRecursive()),
		];
		if ($result = $DB->request($query)) {
			foreach ($result as $data) {
				$info[] = $data;
			}
		}

		return $info;
	}
}
