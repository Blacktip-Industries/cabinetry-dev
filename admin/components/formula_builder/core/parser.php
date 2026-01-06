<?php
/**
 * Formula Builder Component - Formula Parser
 * Parses JavaScript-like formula syntax into AST
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/formula_parser.php';

// Token types
define('TOKEN_STRING', 'STRING');
define('TOKEN_NUMBER', 'NUMBER');
define('TOKEN_IDENTIFIER', 'IDENTIFIER');
define('TOKEN_KEYWORD', 'KEYWORD');
define('TOKEN_OPERATOR', 'OPERATOR');
define('TOKEN_PUNCTUATION', 'PUNCTUATION');
define('TOKEN_COMMENT', 'COMMENT');

// Keywords
define('KEYWORDS', ['var', 'let', 'const', 'if', 'else', 'for', 'while', 'foreach', 'return', 'function', 'true', 'false', 'null']);

// Operators (multi-character first, then single)
define('OPERATORS', ['===', '!==', '==', '!=', '<=', '>=', '&&', '||', '++', '--', '+=', '-=', '*=', '/=', '%=', '+', '-', '*', '/', '%', '=', '<', '>', '!', '&', '|']);

/**
 * Token class
 */
class FormulaToken {
    public $type;
    public $value;
    public $line;
    public $column;
    
    public function __construct($type, $value, $line = 1, $column = 1) {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
        $this->column = $column;
    }
    
    public function __toString() {
        return "{$this->type}:{$this->value}";
    }
}

/**
 * Enhanced tokenizer
 * @param string $formulaCode Formula code
 * @return array Array of FormulaToken objects
 */
function formula_builder_tokenize($formulaCode) {
    $tokens = [];
    $length = strlen($formulaCode);
    $i = 0;
    $line = 1;
    $column = 1;
    
    while ($i < $length) {
        $char = $formulaCode[$i];
        $startColumn = $column;
        
        // Skip whitespace (but track line numbers)
        if (ctype_space($char)) {
            if ($char === "\n") {
                $line++;
                $column = 1;
            } else {
                $column++;
            }
            $i++;
            continue;
        }
        
        // Comments (single line // and multi-line /* */)
        if ($char === '/' && $i + 1 < $length) {
            if ($formulaCode[$i + 1] === '/') {
                // Single line comment
                $i += 2;
                $column += 2;
                while ($i < $length && $formulaCode[$i] !== "\n") {
                    $i++;
                    $column++;
                }
                continue;
            } elseif ($formulaCode[$i + 1] === '*') {
                // Multi-line comment
                $i += 2;
                $column += 2;
                while ($i + 1 < $length) {
                    if ($formulaCode[$i] === '*' && $formulaCode[$i + 1] === '/') {
                        $i += 2;
                        $column += 2;
                        break;
                    }
                    if ($formulaCode[$i] === "\n") {
                        $line++;
                        $column = 1;
                    } else {
                        $column++;
                    }
                    $i++;
                }
                continue;
            }
        }
        
        // Strings (single or double quotes)
        if ($char === '"' || $char === "'") {
            $quote = $char;
            $value = '';
            $i++;
            $column++;
            $escaped = false;
            
            while ($i < $length) {
                $ch = $formulaCode[$i];
                if ($escaped) {
                    switch ($ch) {
                        case 'n': $value .= "\n"; break;
                        case 't': $value .= "\t"; break;
                        case 'r': $value .= "\r"; break;
                        case '\\': $value .= "\\"; break;
                        case $quote: $value .= $quote; break;
                        default: $value .= $ch; break;
                    }
                    $escaped = false;
                    $column++;
                } elseif ($ch === '\\') {
                    $escaped = true;
                    $column++;
                } elseif ($ch === $quote) {
                    $i++;
                    $column++;
                    break;
                } else {
                    $value .= $ch;
                    $column++;
                }
                $i++;
            }
            
            $tokens[] = new FormulaToken(TOKEN_STRING, $value, $line, $startColumn);
            continue;
        }
        
        // Numbers (integers, floats, scientific notation)
        if (ctype_digit($char) || ($char === '.' && $i + 1 < $length && ctype_digit($formulaCode[$i + 1]))) {
            $value = '';
            $hasDot = false;
            $hasExp = false;
            
            while ($i < $length) {
                $ch = $formulaCode[$i];
                if (ctype_digit($ch)) {
                    $value .= $ch;
                    $column++;
                } elseif ($ch === '.' && !$hasDot && !$hasExp) {
                    $value .= $ch;
                    $hasDot = true;
                    $column++;
                } elseif (($ch === 'e' || $ch === 'E') && !$hasExp) {
                    $value .= $ch;
                    $hasExp = true;
                    $column++;
                    // Check for + or - after e/E
                    if ($i + 1 < $length && ($formulaCode[$i + 1] === '+' || $formulaCode[$i + 1] === '-')) {
                        $i++;
                        $value .= $formulaCode[$i];
                        $column++;
                    }
                } else {
                    break;
                }
                $i++;
            }
            
            $tokens[] = new FormulaToken(TOKEN_NUMBER, $value, $line, $startColumn);
            continue;
        }
        
        // Operators (check multi-character first)
        $matched = false;
        foreach (OPERATORS as $op) {
            $opLen = strlen($op);
            if ($i + $opLen <= $length && substr($formulaCode, $i, $opLen) === $op) {
                $tokens[] = new FormulaToken(TOKEN_OPERATOR, $op, $line, $startColumn);
                $i += $opLen;
                $column += $opLen;
                $matched = true;
                break;
            }
        }
        if ($matched) continue;
        
        // Punctuation
        if (in_array($char, [';', ',', '.', '[', ']', '{', '}', '(', ')', '?', ':'])) {
            $tokens[] = new FormulaToken(TOKEN_PUNCTUATION, $char, $line, $startColumn);
            $i++;
            $column++;
            continue;
        }
        
        // Identifiers and keywords
        if (ctype_alpha($char) || $char === '_' || $char === '$') {
            $value = '';
            while ($i < $length && (ctype_alnum($formulaCode[$i]) || $formulaCode[$i] === '_' || $formulaCode[$i] === '$')) {
                $value .= $formulaCode[$i];
                $i++;
                $column++;
            }
            
            // Check if it's a keyword
            if (in_array(strtolower($value), KEYWORDS)) {
                $tokens[] = new FormulaToken(TOKEN_KEYWORD, strtolower($value), $line, $startColumn);
            } else {
                $tokens[] = new FormulaToken(TOKEN_IDENTIFIER, $value, $line, $startColumn);
            }
            continue;
        }
        
        // Unknown character - skip with warning
        error_log("Formula Builder: Unknown character '{$char}' at line {$line}, column {$column}");
        $i++;
        $column++;
    }
    
    return $tokens;
}

/**
 * Validate formula syntax (basic validation)
 * @param string $formulaCode Formula code to validate
 * @return array Result with success status and errors
 */
function formula_builder_validate_formula($formulaCode) {
    $errors = [];
    
    if (empty($formulaCode)) {
        $errors[] = 'Formula code cannot be empty';
        return ['success' => false, 'errors' => $errors];
    }
    
    // Tokenize to check for basic syntax errors
    try {
        $tokens = formula_builder_tokenize($formulaCode);
        
        // Check for balanced braces
        $openBraces = 0;
        $openParens = 0;
        $openBrackets = 0;
        
        foreach ($tokens as $token) {
            if ($token->type === TOKEN_PUNCTUATION) {
                if ($token->value === '{') $openBraces++;
                elseif ($token->value === '}') $openBraces--;
                elseif ($token->value === '(') $openParens++;
                elseif ($token->value === ')') $openParens--;
                elseif ($token->value === '[') $openBrackets++;
                elseif ($token->value === ']') $openBrackets--;
            }
        }
        
        if ($openBraces !== 0) {
            $errors[] = 'Unbalanced braces in formula';
        }
        if ($openParens !== 0) {
            $errors[] = 'Unbalanced parentheses in formula';
        }
        if ($openBrackets !== 0) {
            $errors[] = 'Unbalanced brackets in formula';
        }
        
        // Check for return statement
        $hasReturn = false;
        foreach ($tokens as $token) {
            if ($token->type === TOKEN_KEYWORD && $token->value === 'return') {
                $hasReturn = true;
                break;
            }
        }
        if (!$hasReturn) {
            $errors[] = 'Formula must contain a return statement';
        }
        
    } catch (Exception $e) {
        $errors[] = 'Tokenization error: ' . $e->getMessage();
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Parse formula code into tokens (legacy function for backward compatibility)
 * @param string $formulaCode Formula code
 * @return array Tokens array
 */
function formula_builder_parse_tokens($formulaCode) {
    $tokens = formula_builder_tokenize($formulaCode);
    return array_map(function($token) {
        return $token->value;
    }, $tokens);
}
