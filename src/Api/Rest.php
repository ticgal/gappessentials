<?php

/**
 *  -------------------------------------------------------------------------
 *  GappEssentials plugin for GLPI
 *  Copyright (C) 2019 - 2026 by TICGAL.
 *  https://tic.gal
 *  https://github.com/pluginsGLPI/gappessentials
 *  -------------------------------------------------------------------------
 *
 *  LICENSE
 *
 *  This file is part of GappEssentials.
 *
 *  GappEssentials is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  GappEssentials is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GappEssentials. If not, see <http://www.gnu.org/licenses/>.
 *  --------------------------------------------------------------------------
 *  * @package   gappessentials
 *  * @author    the TICGAL team
 *  * @copyright Copyright (C) 2019 - 2026 TICGAL team
 *  * @license   AGPL License 3.0 or (at your option) any later version
 *  *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  * @link      https://www.tic.gal
 *  * @since     2019
 *  * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Gappessentials\Api;

use Change;
use CommonITILObject;
use Document;
use Document_Item;
use Glpi\Api\APIRest;
use GlpiPlugin\Gappessentials\Config;
use Html;
use ITILCategory;
use Location;
use Plugin;
use Problem;
use Session;
use Ticket;

/**
 * REST API of the Gapp mobile app. It reuses the core REST API (request parsing, session and app token
 * checks, logging, error responses) and only adds its own resources.
 */
class Rest extends APIRest
{
    public static function getTypeName($nb = 0)
    {
        return __('GappEssentials rest API', 'gappessentials');
    }

    /**
     * This API has no upload endpoint: uploaded files are never processed.
     *
     * @return void
     */
    public function manageUploadedFiles() {}

    public function parseIncomingParams($is_inline_doc = false)
    {
        // Refuse uploads before the parent parser tries to handle them (and before any authentication)
        $content_type = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains($content_type, 'multipart/form-data')) {
            $this->returnError(
                __('Uploads are not supported by this API', 'gappessentials'),
                400,
                'ERROR_UPLOAD_NOT_SUPPORTED',
                false,
            );
        }

        parent::parseIncomingParams($is_inline_doc);
    }

    protected function initEndpoint($unlock_session = true, $endpoint = '')
    {
        // The endpoint name comes from the URL and is written to the API log: strip control characters
        // so that it cannot forge log lines.
        parent::initEndpoint($unlock_session, preg_replace('/[\x00-\x1F\x7F]+/', '', $endpoint));
    }

    /**
     * No resource is available without a valid session.
     *
     * @return array
     */
    protected function getRessourcesAllowedWithoutSession(): array
    {
        return [];
    }

    /**
     * No resource writes PHP session data.
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
        $path_info          = (isset($_SERVER['PATH_INFO'])) ? str_replace('api/', '', trim($_SERVER['PATH_INFO'], '/')) : '';
        $this->url_elements = explode('/', $path_info);

        // retrieve requested resource
        $resource      = trim(strval($this->url_elements[0]));
        $is_inline_doc = (strlen($resource) == 0) || ($resource == 'api');

        // Add headers for CORS
        $this->cors();

        // retrieve parameters (in body, query_string, headers)
        $this->parseIncomingParams($is_inline_doc);

        // show debug if required
        if (isset($this->parameters['debug'])) {
            $this->debug = $this->parameters['debug'];
            if (empty($this->debug)) {
                $this->debug = true;
            }
        }

        // retrieve session (if exist)
        $this->retrieveSession();
        $this->initApi();
        $this->manageUploadedFiles();

        // retrieve param who permit session writing
        if (isset($this->parameters['session_write'])) {
            $this->session_write = (bool) $this->parameters['session_write'];
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
                $this->returnResponse($this->pluginList());
                break;
            case 'documentsTicket':
                $this->returnResponse($this->documentsTicket($this->parameters));
                break;
            case 'getDocument':
                $this->getDocument($this->parameters);
                break;
            case 'getDocuments':
                $this->returnResponse($this->getDocuments($this->parameters));
                break;
            case 'basicInfo':
                $this->returnResponse($this->basicInfo());
                break;
            case 'itilCategory':
                $this->returnResponse($this->itilCategory($this->parameters));
                break;
            case 'location':
                $this->returnResponse($this->location());
                break;
            case 'defaultConfig':
                $this->returnResponse($this->defaultConfig());
                break;
            default:
                $this->messageLostError();
                break;
        }
    }

    /**
     * Always answer as JSON: the parent class switches to text/html (and prints the page header) when `debug`
     * is requested, which would render API data as markup on the GLPI origin.
     *
     * @param bool   $html  ignored
     * @param string $title ignored
     *
     * @return void
     */
    protected function header($html = false, $title = '')
    {
        header('Content-Type: ' . static::$content_type . '; charset=UTF-8');
        Html::header_nocache();
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
            // The app expects numeric values as numbers; `<` and `&` are escaped to keep the output inert
            $json = json_encode(
                $response,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_NUMERIC_CHECK
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | ($this->debug ? JSON_PRETTY_PRINT : 0),
            );
        } else {
            $json = '';
        }

        echo $json;

        exit();
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

    private function pluginActivated(): void
    {
        $plugin = new Plugin();

        if (!$plugin->isActivated('gappessentials')) {
            $this->returnError('Plugin disabled', 400, 'ERROR_PLUGIN_DISABLED');
        }
    }

    /**
     * Documents attached to a ITIL object (directly or through its followups/tasks) that the caller may see.
     *
     * @param CommonITILObject $item       the (already loaded and readable) ITIL object
     * @param array            $params     request parameters
     * @param string           $parent_key name of the key that carries the parent id in each row
     *
     * @return array
     */
    private function listDocuments(CommonITILObject $item, array $params, string $parent_key): array
    {
        $document = new Document();
        $document_items = (new Document_Item())->find([
            $item->getAssociatedDocumentsCriteria(),
            'timeline_position' => ['>', CommonITILObject::NO_TIMELINE],
        ]);

        $fields = [];
        foreach ($document_items as $document_item) {
            $document->getFromDB($document_item['documents_id']);
            $file = GLPI_DOC_DIR . '/' . $document->fields['filepath'];
            $data = $document->fields;
            $data['filesize']  = is_file($file) ? filesize($file) : 0;
            $data['date_mod']  = $document_item['date_mod'];
            $data['users_id']  = $document_item['users_id'];
            $data['timeline_position'] = $document_item['timeline_position'];
            $data['items_id'] = $document_item['items_id'];
            $data['itemtype'] = $document_item['itemtype'];
            $data[$parent_key] = $item->getID();
            if (count($params['add_keys_names'] ?? []) > 0) {
                $data['_keys_names'] = $this->getFriendlyNames($data, $params, $item->getType());
            }
            $fields[] = $data;
        }

        return self::parseDropdowns($fields, $params);
    }

    protected function documentsTicket(array $params = []): array
    {
        $ticket_id = $this->getId();
        $ticket = new Ticket();

        if (!$ticket->getFromDB($ticket_id)) {
            $this->messageNotfoundError();
        }
        if (!$ticket->can($ticket_id, READ)) {
            $this->messageRightError();
        }

        return $this->listDocuments($ticket, $params, 'tickets_id');
    }

    protected function getDocuments(array $params = []): array
    {
        if (!isset($params['itemtype'])) {
            $this->returnError();
        }
        // only ITIL objects have associated documents criteria
        if (!is_string($params['itemtype']) || !in_array($params['itemtype'], [Ticket::class, Change::class, Problem::class], true)) {
            $this->returnError(
                __('Unsupported itemtype: only Ticket, Change and Problem are allowed', 'gappessentials'),
                400,
                'ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM',
            );
        }
        $item_id = $this->getId();
        $item = new $params['itemtype']();

        if (!$item->getFromDB($item_id)) {
            $this->messageNotfoundError();
        }
        if (!$item->can($item_id, READ)) {
            $this->messageRightError();
        }

        return $this->listDocuments($item, $params, 'parent_id');
    }

    protected function getDocument(array $params = []): void
    {
        $id = isset($params['docid']) ? (int) $params['docid'] : $this->getId();
        if ($id === false || $id <= 0) {
            $this->returnError(__('missing resource'), 400, 'ERROR_RESOURCE_MISSING');
            return;
        }
        $document = new Document();

        $options = [];
        if (isset($params['tickets_id'])) {
            $options['tickets_id'] = (int) $params['tickets_id'];
        } elseif (isset($params['itemtype']) && isset($params['items_id'])) {
            $options['itemtype'] = $params['itemtype'];
            $options['items_id'] = (int) $params['items_id'];
        }

        if (empty($options)) {
            $this->returnError(
                __('Missing context parameters (tickets_id or itemtype/items_id)', 'gappessentials'),
                400,
                'ERROR_MISSING_CONTEXT',
            );
            return;
        }

        // Load the document first: canViewFile() on an empty object degrades to a global create check
        if (!$document->getFromDB($id)) {
            $this->messageNotfoundError();
            return;
        }

        // Document::canViewFile() reloads the API session from the raw Session-Token header, but this API's
        // tokens are encrypted: point it at the already decrypted session id so it does not destroy the session.
        $_SERVER['HTTP_SESSION_TOKEN'] = session_id();

        if (!$document->canViewFile($options)) {
            $this->messageRightError();
            return;
        }

        $document->getAsResponse()->send();

        exit();
    }

    protected function pluginList(): array
    {
        if (!Session::haveRight('config', READ)) {
            $this->messageRightError();
        }

        return (new Plugin())->getList();
    }

    protected function basicInfo(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $info = [];
        $info['documenttype'] = [];

        $info['max_size'] = \Toolbox::return_bytes_from_ini_vars(ini_get('upload_max_filesize'));

        $iterator = $DB->request([
            'SELECT' => ['name', 'ext', 'mime'],
            'FROM'   => 'glpi_documenttypes',
            'WHERE'  => ['is_uploadable' => 1],
        ]);
        foreach ($iterator as $data) {
            $info['documenttype'][] = $data;
        }

        return $info;
    }

    protected function itilCategory(array $params = []): array
    {
        /** @var \DBmysql $DB */
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
                'comment',
            ],
            'FROM'  => 'glpi_itilcategories',
            'WHERE' => getEntitiesRestrictCriteria('glpi_itilcategories', '', $_SESSION['glpiactive_entity'], $item->maybeRecursive()),
            'ORDER' => 'completename ASC',
        ];
        if (isset($params['is_helpdeskvisible'])) {
            $query['WHERE']['is_helpdeskvisible'] = $params['is_helpdeskvisible'];
        }
        if (Session::getCurrentInterface() === 'helpdesk') {
            // self-service users only see helpdesk-visible categories in the UI
            $query['WHERE']['is_helpdeskvisible'] = 1;
        }
        if (isset($params['is_incident'])) {
            $query['WHERE']['is_incident'] = $params['is_incident'];
        }
        if (isset($params['is_request'])) {
            $query['WHERE']['is_request'] = $params['is_request'];
        }
        foreach ($DB->request($query) as $data) {
            $info[] = $data;
        }

        return $info;
    }

    /**
     * Design decision (2026-10-27): the Gapp app offers locations to requesters when creating a ticket, so this
     * list is available to self-service sessions on purpose, limited to the active entity. Do not add a right
     * check without checking the app.
     */
    protected function location(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $info = [];
        $item = new Location();
        $query = [
            'SELECT' => ['id', 'name', 'completename', 'level', 'locations_id'],
            'FROM'   => 'glpi_locations',
            'WHERE'  => getEntitiesRestrictCriteria('glpi_locations', '', $_SESSION['glpiactive_entity'], $item->maybeRecursive()),
        ];
        foreach ($DB->request($query) as $data) {
            $info[] = $data;
        }

        return $info;
    }

    protected function defaultConfig(): array
    {
        $config = new Config();
        $config->getFromDB(1);

        return $config->fields;
    }
}
