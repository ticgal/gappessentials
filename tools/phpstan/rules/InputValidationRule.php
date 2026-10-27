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

declare(strict_types=1);

namespace CustomPHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detects the use of superglobals without validation.
 */
class InputValidationRule implements Rule
{
    private const SUPERGLOBALS = [
        '_GET',
        '_POST',
        '_REQUEST',
        '_COOKIE',
    ];

    public function getNodeType(): string
    {
        return Variable::class; // Analyzes variable usage.
    }

    /**
     * @param Variable $node
     * @param Scope $scope
     * @return array
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // Get the file path being analyzed
        $filePath = $scope->getFile();

        // Skip files in "ajax" or "front" directories
        if (strpos($filePath, '/ajax/') !== false || strpos($filePath, '/front/') !== false) {
            return []; // Do not apply the rule to these files
        }

        // If file is apirest.class.php, skip the rule
        if (strpos($filePath, '/apirest.class.php') !== false) {
            return []; // Do not apply the rule to this file
        }

        // Get the function name
        $functionName = $scope->getFunctionName();

        // Allowed functions
        if (in_array($functionName, ['prepareInputForAdd', 'prepareInputForUpdate'], true)) {
            return []; // Do not apply the rule to these functions
        }

        if (is_string($node->name) && in_array($node->name, self::SUPERGLOBALS, true)) {
            // Check if the superglobal is being accessed with a specific key
            // If not, allow assignment Ex. $get = $_GET;
            if ($node->getAttribute('key') === null) {
                return [];
            }

            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'The use of the superglobal "%s" without validation is discouraged out of ajax and front files.',
                        print_r($node->name, true),
                    ),
                )->build(),
            ];
        }

        return [];
    }
}
