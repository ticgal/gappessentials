<?php
/*
 -------------------------------------------------------------------------
 GappEssentials plugin for GLPI
 Copyright (C) 2019 by the TICgal
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
namespace GlpiPlugin\Gappessentials\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Http\HeaderlessStreamedResponse;
use PluginGappEssentialsApirest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Plugin;

include_once(Plugin::getPhpDir('gappessentials') . "/inc/apirest.class.php");

final class ApiRestController extends AbstractController
{

    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    #[Route(
        "/ApiRest.php{request_parameters}",
        name: "glpi_plugin_api_rest",
        requirements: [
            'request_parameters' => '.*',
        ]
    )]
    public function __invoke(Request $request): Response
    {
        $_SERVER['PATH_INFO'] = $request->get('request_parameters');

        // @phpstan-ignore-next-line method.deprecatedClass (refactoring is planned later)
        return new HeaderlessStreamedResponse(function () {
            $api = new PluginGappEssentialsApirest();
            $api->call();
        });
    }
}