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
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detects the use of functions that may expose sensitive information.
 */
class SensitiveInfoExposureRule implements Rule
{
    private const SENSITIVE_FUNCTIONS = [
        'phpinfo',
        'var_dump',
        'print_r',
    ];

    public function getNodeType(): string
    {
        return FuncCall::class; // Analyzes function calls.
    }

    /**
     * @param FuncCall $node
     * @param Scope $scope
     * @return array
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name instanceof Node\Name) {
            $functionName = (string) $node->name;
            if (in_array($functionName, self::SENSITIVE_FUNCTIONS, true)) {
                // Special handling for print_r
                if ($functionName === 'print_r') {
                    // Check if the second argument is set and is `true`
                    if (isset($node->args[1])) {
                        $secondArg = $node->args[1]->value;

                        // Allow only if the second argument is a literal `true`
                        if ($secondArg->name->toLowerString() === 'true') {
                            return []; // No error if print_r($var, true)
                        }
                    }

                    // If no second argument or it's not `true`, report an error
                    return [
                        RuleErrorBuilder::message(
                            'The use of "print_r" without the second argument as `true` may expose sensitive information and should be avoided in production environments.',
                        )
                        ->tip('Use "print_r($var, true)" to avoid direct output or consider removing this call.')
                        ->build(),
                    ];
                }

                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            'The use of the function "%s" may expose sensitive information and should be avoided in production environments.',
                            $functionName,
                        ),
                    )->build(),
                ];
            }
        }

        return [];
    }
}
