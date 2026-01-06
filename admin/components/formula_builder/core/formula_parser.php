<?php
/**
 * Formula Builder Component - Formula Parser Class
 * Recursive descent parser for JavaScript-like syntax
 */

require_once __DIR__ . '/ast_nodes.php';

/**
 * Parser class
 */
class FormulaParser {
    private $tokens;
    private $position;
    private $length;
    
    public function __construct($tokens) {
        $this->tokens = $tokens;
        $this->position = 0;
        $this->length = count($tokens);
    }
    
    private function current() {
        if ($this->position >= $this->length) {
            return null;
        }
        return $this->tokens[$this->position];
    }
    
    private function peek($offset = 0) {
        $pos = $this->position + $offset;
        if ($pos >= $this->length) {
            return null;
        }
        return $this->tokens[$pos];
    }
    
    private function advance() {
        if ($this->position < $this->length) {
            $this->position++;
        }
        return $this->position < $this->length ? $this->tokens[$this->position - 1] : null;
    }
    
    private function match($type, $value = null) {
        $token = $this->current();
        if ($token === null) {
            return false;
        }
        if ($token->type !== $type) {
            return false;
        }
        if ($value !== null && $token->value !== $value) {
            return false;
        }
        $this->advance();
        return true;
    }
    
    private function expect($type, $value = null) {
        $token = $this->current();
        if ($token === null) {
            throw new Exception("Unexpected end of input, expected {$type}");
        }
        if ($token->type !== $type) {
            throw new Exception("Expected {$type}, got {$token->type} at line {$token->line}, column {$token->column}");
        }
        if ($value !== null && $token->value !== $value) {
            throw new Exception("Expected '{$value}', got '{$token->value}' at line {$token->line}, column {$token->column}");
        }
        return $this->advance();
    }
    
    /**
     * Parse program (list of statements)
     */
    public function parse() {
        $statements = [];
        while ($this->position < $this->length) {
            $stmt = $this->parseStatement();
            if ($stmt !== null) {
                $statements[] = $stmt;
            }
        }
        return $statements;
    }
    
    /**
     * Parse statement
     */
    private function parseStatement() {
        $token = $this->current();
        if ($token === null) {
            return null;
        }
        
        // Variable declaration
        if ($token->type === TOKEN_KEYWORD && in_array($token->value, ['var', 'let', 'const'])) {
            return $this->parseVariableDeclaration();
        }
        
        // Return statement
        if ($token->type === TOKEN_KEYWORD && $token->value === 'return') {
            return $this->parseReturn();
        }
        
        // If statement
        if ($token->type === TOKEN_KEYWORD && $token->value === 'if') {
            return $this->parseIf();
        }
        
        // For loop
        if ($token->type === TOKEN_KEYWORD && $token->value === 'for') {
            return $this->parseFor();
        }
        
        // While loop
        if ($token->type === TOKEN_KEYWORD && $token->value === 'while') {
            return $this->parseWhile();
        }
        
        // Expression statement
        $expr = $this->parseExpression();
        if ($this->match(TOKEN_PUNCTUATION, ';')) {
            return $expr;
        }
        return $expr;
    }
    
    /**
     * Parse variable declaration
     */
    private function parseVariableDeclaration() {
        $declType = $this->expect(TOKEN_KEYWORD)->value;
        $name = $this->expect(TOKEN_IDENTIFIER)->value;
        $line = $this->tokens[$this->position - 2]->line;
        $column = $this->tokens[$this->position - 2]->column;
        
        $value = null;
        if ($this->match(TOKEN_OPERATOR, '=')) {
            $value = $this->parseExpression();
        }
        
        $this->match(TOKEN_PUNCTUATION, ';'); // Optional semicolon
        
        return new VariableDeclarationNode($name, $value, $declType, $line, $column);
    }
    
    /**
     * Parse return statement
     */
    private function parseReturn() {
        $token = $this->expect(TOKEN_KEYWORD, 'return');
        $line = $token->line;
        $column = $token->column;
        
        $value = null;
        if (!$this->match(TOKEN_PUNCTUATION, ';')) {
            $value = $this->parseExpression();
            $this->match(TOKEN_PUNCTUATION, ';'); // Optional semicolon
        }
        
        return new ReturnNode($value, $line, $column);
    }
    
    /**
     * Parse if statement
     */
    private function parseIf() {
        $token = $this->expect(TOKEN_KEYWORD, 'if');
        $line = $token->line;
        $column = $token->column;
        
        $this->expect(TOKEN_PUNCTUATION, '(');
        $condition = $this->parseExpression();
        $this->expect(TOKEN_PUNCTUATION, ')');
        
        $thenBranch = $this->parseBlock();
        
        $elseBranch = null;
        if ($this->match(TOKEN_KEYWORD, 'else')) {
            if ($this->match(TOKEN_KEYWORD, 'if')) {
                $elseBranch = $this->parseIf();
            } else {
                $elseBranch = $this->parseBlock();
            }
        }
        
        return new ConditionalNode($condition, $thenBranch, $elseBranch, $line, $column);
    }
    
    /**
     * Parse for loop
     */
    private function parseFor() {
        $token = $this->expect(TOKEN_KEYWORD, 'for');
        $line = $token->line;
        $column = $token->column;
        
        $this->expect(TOKEN_PUNCTUATION, '(');
        
        $init = null;
        if (!$this->match(TOKEN_PUNCTUATION, ';')) {
            $init = $this->parseStatement();
            if (!$this->match(TOKEN_PUNCTUATION, ';')) {
                throw new Exception("Expected ';' after for loop init");
            }
        }
        
        $condition = null;
        if (!$this->match(TOKEN_PUNCTUATION, ';')) {
            $condition = $this->parseExpression();
            $this->expect(TOKEN_PUNCTUATION, ';');
        }
        
        $update = null;
        if (!$this->match(TOKEN_PUNCTUATION, ')')) {
            $update = $this->parseExpression();
            $this->expect(TOKEN_PUNCTUATION, ')');
        }
        
        $body = $this->parseBlock();
        
        return new LoopNode('for', $init, $condition, $update, $body, $line, $column);
    }
    
    /**
     * Parse while loop
     */
    private function parseWhile() {
        $token = $this->expect(TOKEN_KEYWORD, 'while');
        $line = $token->line;
        $column = $token->column;
        
        $this->expect(TOKEN_PUNCTUATION, '(');
        $condition = $this->parseExpression();
        $this->expect(TOKEN_PUNCTUATION, ')');
        
        $body = $this->parseBlock();
        
        return new LoopNode('while', null, $condition, null, $body, $line, $column);
    }
    
    /**
     * Parse block (statements in braces)
     */
    private function parseBlock() {
        if ($this->match(TOKEN_PUNCTUATION, '{')) {
            $statements = [];
            while (!$this->match(TOKEN_PUNCTUATION, '}')) {
                $stmt = $this->parseStatement();
                if ($stmt !== null) {
                    $statements[] = $stmt;
                }
            }
            return $statements;
        } else {
            // Single statement without braces
            return [$this->parseStatement()];
        }
    }
    
    /**
     * Parse expression (with operator precedence)
     */
    private function parseExpression() {
        return $this->parseTernary();
    }
    
    /**
     * Parse ternary operator
     */
    private function parseTernary() {
        $expr = $this->parseLogicalOr();
        
        if ($this->match(TOKEN_PUNCTUATION, '?')) {
            $thenExpr = $this->parseExpression();
            $this->expect(TOKEN_PUNCTUATION, ':');
            $elseExpr = $this->parseExpression();
            return new ConditionalNode($expr, $thenExpr, $elseExpr);
        }
        
        return $expr;
    }
    
    /**
     * Parse logical OR
     */
    private function parseLogicalOr() {
        $expr = $this->parseLogicalAnd();
        
        while ($this->match(TOKEN_OPERATOR, '||')) {
            $right = $this->parseLogicalAnd();
            $expr = new BinaryExpressionNode('||', $expr, $right);
        }
        
        return $expr;
    }
    
    /**
     * Parse logical AND
     */
    private function parseLogicalAnd() {
        $expr = $this->parseEquality();
        
        while ($this->match(TOKEN_OPERATOR, '&&')) {
            $right = $this->parseEquality();
            $expr = new BinaryExpressionNode('&&', $expr, $right);
        }
        
        return $expr;
    }
    
    /**
     * Parse equality operators
     */
    private function parseEquality() {
        $expr = $this->parseComparison();
        
        while ($this->match(TOKEN_OPERATOR, '==') || $this->match(TOKEN_OPERATOR, '!=') || 
               $this->match(TOKEN_OPERATOR, '===') || $this->match(TOKEN_OPERATOR, '!==')) {
            $op = $this->tokens[$this->position - 1]->value;
            $right = $this->parseComparison();
            $expr = new BinaryExpressionNode($op, $expr, $right);
        }
        
        return $expr;
    }
    
    /**
     * Parse comparison operators
     */
    private function parseComparison() {
        $expr = $this->parseAddition();
        
        while ($this->match(TOKEN_OPERATOR, '<') || $this->match(TOKEN_OPERATOR, '>') ||
               $this->match(TOKEN_OPERATOR, '<=') || $this->match(TOKEN_OPERATOR, '>=')) {
            $op = $this->tokens[$this->position - 1]->value;
            $right = $this->parseAddition();
            $expr = new BinaryExpressionNode($op, $expr, $right);
        }
        
        return $expr;
    }
    
    /**
     * Parse addition/subtraction
     */
    private function parseAddition() {
        $expr = $this->parseMultiplication();
        
        while ($this->match(TOKEN_OPERATOR, '+') || $this->match(TOKEN_OPERATOR, '-')) {
            $op = $this->tokens[$this->position - 1]->value;
            $right = $this->parseMultiplication();
            $expr = new BinaryExpressionNode($op, $expr, $right);
        }
        
        return $expr;
    }
    
    /**
     * Parse multiplication/division/modulo
     */
    private function parseMultiplication() {
        $expr = $this->parseUnary();
        
        while ($this->match(TOKEN_OPERATOR, '*') || $this->match(TOKEN_OPERATOR, '/') || 
               $this->match(TOKEN_OPERATOR, '%')) {
            $op = $this->tokens[$this->position - 1]->value;
            $right = $this->parseUnary();
            $expr = new BinaryExpressionNode($op, $expr, $right);
        }
        
        return $expr;
    }
    
    /**
     * Parse unary operators
     */
    private function parseUnary() {
        if ($this->match(TOKEN_OPERATOR, '!') || $this->match(TOKEN_OPERATOR, '-')) {
            $op = $this->tokens[$this->position - 1]->value;
            $expr = $this->parseUnary();
            return new BinaryExpressionNode($op, null, $expr);
        }
        
        return $this->parsePrimary();
    }
    
    /**
     * Parse primary expressions
     */
    private function parsePrimary() {
        $token = $this->current();
        if ($token === null) {
            throw new Exception("Unexpected end of input");
        }
        
        // Literals
        if ($token->type === TOKEN_NUMBER) {
            $this->advance();
            return new LiteralNode((float)$token->value, $token->line, $token->column);
        }
        
        if ($token->type === TOKEN_STRING) {
            $this->advance();
            return new LiteralNode($token->value, $token->line, $token->column);
        }
        
        if ($token->type === TOKEN_KEYWORD) {
            if ($token->value === 'true') {
                $this->advance();
                return new LiteralNode(true, $token->line, $token->column);
            }
            if ($token->value === 'false') {
                $this->advance();
                return new LiteralNode(false, $token->line, $token->column);
            }
            if ($token->value === 'null') {
                $this->advance();
                return new LiteralNode(null, $token->line, $token->column);
            }
        }
        
        // Parentheses
        if ($this->match(TOKEN_PUNCTUATION, '(')) {
            $expr = $this->parseExpression();
            $this->expect(TOKEN_PUNCTUATION, ')');
            return $expr;
        }
        
        // Object literal
        if ($this->match(TOKEN_PUNCTUATION, '{')) {
            return $this->parseObjectLiteral();
        }
        
        // Array literal
        if ($this->match(TOKEN_PUNCTUATION, '[')) {
            return $this->parseArrayLiteral();
        }
        
        // Identifier (variable or function call)
        if ($token->type === TOKEN_IDENTIFIER) {
            $name = $token->value;
            $line = $token->line;
            $column = $token->column;
            $this->advance();
            
            // Function call
            if ($this->match(TOKEN_PUNCTUATION, '(')) {
                // Security check during parsing
                if (function_exists('formula_builder_is_function_allowed') && 
                    !formula_builder_is_function_allowed($name)) {
                    throw new Exception("Unauthorized function call: {$name} at line {$line}, column {$column}");
                }
                
                $args = [];
                if (!$this->match(TOKEN_PUNCTUATION, ')')) {
                    do {
                        $args[] = $this->parseExpression();
                    } while ($this->match(TOKEN_PUNCTUATION, ','));
                    $this->expect(TOKEN_PUNCTUATION, ')');
                }
                return new FunctionCallNode($name, $args, $line, $column);
            }
            
            // Member access
            $expr = new IdentifierNode($name, $line, $column);
            while ($this->match(TOKEN_PUNCTUATION, '.') || $this->match(TOKEN_PUNCTUATION, '[')) {
                $isComputed = $this->tokens[$this->position - 1]->value === '[';
                if ($isComputed) {
                    $property = $this->parseExpression();
                    $this->expect(TOKEN_PUNCTUATION, ']');
                } else {
                    $propToken = $this->expect(TOKEN_IDENTIFIER);
                    $property = new IdentifierNode($propToken->value, $propToken->line, $propToken->column);
                }
                $expr = new MemberAccessNode($expr, $property, $isComputed);
            }
            
            return $expr;
        }
        
        throw new Exception("Unexpected token '{$token->value}' at line {$token->line}, column {$token->column}");
    }
    
    /**
     * Parse object literal
     */
    private function parseObjectLiteral() {
        $properties = [];
        $line = $this->tokens[$this->position - 1]->line;
        $column = $this->tokens[$this->position - 1]->column;
        
        if (!$this->match(TOKEN_PUNCTUATION, '}')) {
            do {
                $key = $this->expect(TOKEN_IDENTIFIER)->value;
                $this->expect(TOKEN_PUNCTUATION, ':');
                $value = $this->parseExpression();
                $properties[$key] = $value;
            } while ($this->match(TOKEN_PUNCTUATION, ','));
            $this->expect(TOKEN_PUNCTUATION, '}');
        }
        
        return new ObjectLiteralNode($properties, $line, $column);
    }
    
    /**
     * Parse array literal
     */
    private function parseArrayLiteral() {
        $elements = [];
        $line = $this->tokens[$this->position - 1]->line;
        $column = $this->tokens[$this->position - 1]->column;
        
        if (!$this->match(TOKEN_PUNCTUATION, ']')) {
            do {
                $elements[] = $this->parseExpression();
            } while ($this->match(TOKEN_PUNCTUATION, ','));
            $this->expect(TOKEN_PUNCTUATION, ']');
        }
        
        return new ArrayLiteralNode($elements, $line, $column);
    }
}

/**
 * Parse formula code into AST
 * @param string $formulaCode Formula code
 * @return array Array of AST nodes (statements)
 */
function formula_builder_parse_ast($formulaCode) {
    try {
        $tokens = formula_builder_tokenize($formulaCode);
        if (empty($tokens)) {
            throw new Exception("Empty formula code");
        }
        $parser = new FormulaParser($tokens);
        $ast = $parser->parse();
        if (empty($ast)) {
            throw new Exception("No statements parsed from formula");
        }
        return $ast;
    } catch (Exception $e) {
        // Preserve original error message with line/column info
        $message = $e->getMessage();
        if (strpos($message, 'at line') === false && strpos($message, 'Parse error') === false) {
            $message = "Parse error: " . $message;
        }
        throw new Exception($message);
    }
}

