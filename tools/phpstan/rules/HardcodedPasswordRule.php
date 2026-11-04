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
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detect hardcoded passwords, API keys, or tokens in the code.
 */
class HardcodedPasswordRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\Assign::class; // Analyzes variable assignments.
    }

    /**
     * @param Node\Expr\Assign $node
     * @param Scope $scope
     * @return string[] Error messages if hardcoded passwords or API keys are detected.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->var instanceof Node\Expr\Variable && $node->expr instanceof Node\Scalar\String_) {
            $variableName = $node->var->name;
            $value = $node->expr->value;

            // Ignore empty variable or initialization.
            if ($value === '') {
                return [];
            }

            // Detect variables with suspicious names.
            if (preg_match('/password|passwd|pwd|secret|api[_-]?key|token/i', $variableName)) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            'A possible hardcoded password or API key was detected in the variable $%s.',
                            $variableName,
                        ),
                    )->build(),
                ];
            }

            // Detect suspicious values (e.g., long strings that look like API keys or tokens).
            if (is_string($value) && $this->isSuspiciousValue($value)) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            'A possible hardcoded API key or token was detected in the value assigned to $%s.',
                            $variableName,
                        ),
                    )->build(),
                ];
            }
        }

        return [];
    }

    /**
     * Checks if a string value looks like a hardcoded API key or token.
     *
     * @param string $value
     * @return bool
     */
    private function isSuspiciousValue(string $value): bool
    {
        $suspicious = false;
        $commonPatterns = [
            // 20+ characters with uppercase, lowercase, digits, and special symbols
            '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[-_!@#$%^&*])[A-Za-z\d-_!@#$%^&*]{20,}$/',
            '/^[A-Za-z0-9]{32}$/', // 32-character alphanumeric string (e.g., MD5 hash)
            '/^[A-Za-z0-9_-]{40}$/', // 40-character alphanumeric string (e.g., SHA1 hash)
            '/^[A-Za-z0-9_-]{64}$/', // 64-character alphanumeric string (e.g., SHA256 hash)
        ];

        foreach ($commonPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $suspicious = true;
                break;
            }
        }

        return $suspicious;
    }
}
