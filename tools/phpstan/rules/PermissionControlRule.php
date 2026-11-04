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
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detects if a class in Config.php or config.class.php has the $rightname attribute with the value 'config'.
 */
class PermissionControlRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class; // Analyzes class declarations.
    }

    /**
     * @param Class_ $node
     * @param Scope $scope
     * @return array
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // Get the file path being analyzed
        $filePath = $scope->getFile();

        // Apply the rule only to Config.php or config.class.php
        if (!preg_match('/(\/|\\\\)(Config\.php|config\.class\.php)$/i', $filePath)) {
            return []; // Skip files that don't match
        }

        // Check if the class has a property named $rightname
        foreach ($node->getProperties() as $property) {
            if ($property instanceof Property && $property->props[0]->name->toString() === 'rightname') {
                $default = $property->props[0]->default;

                // Check if the default value of $rightname is 'config'
                if ($default instanceof String_ && $default->value === 'config') {
                    return []; // No error if $rightname is correctly set to 'config'
                }

                return [
                    RuleErrorBuilder::message(
                        'The class in Config.php or config.class.php must have the $rightname attribute set to "config".',
                    )->build(),
                ];
            }
        }

        // If $rightname is not defined, report an error
        return [
            RuleErrorBuilder::message(
                'The class in Config.php or config.class.php must define the $rightname attribute with the value "config".',
            )->build(),
        ];
    }
}
