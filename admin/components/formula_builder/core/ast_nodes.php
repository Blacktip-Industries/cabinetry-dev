<?php
/**
 * Formula Builder Component - AST Node Classes
 * Abstract Syntax Tree node definitions
 */

/**
 * AST Node base class
 */
abstract class ASTNode {
    public $type;
    public $line;
    public $column;
    
    public function __construct($line = 1, $column = 1) {
        $this->line = $line;
        $this->column = $column;
    }
}

/**
 * Variable Declaration Node
 */
class VariableDeclarationNode extends ASTNode {
    public $name;
    public $value;
    public $declarationType; // 'var', 'let', 'const'
    
    public function __construct($name, $value, $declarationType = 'var', $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'VariableDeclaration';
        $this->name = $name;
        $this->value = $value;
        $this->declarationType = $declarationType;
    }
}

/**
 * Function Call Node
 */
class FunctionCallNode extends ASTNode {
    public $name;
    public $arguments;
    
    public function __construct($name, $arguments = [], $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'FunctionCall';
        $this->name = $name;
        $this->arguments = $arguments;
    }
}

/**
 * Binary Expression Node
 */
class BinaryExpressionNode extends ASTNode {
    public $operator;
    public $left;
    public $right;
    
    public function __construct($operator, $left, $right, $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'BinaryExpression';
        $this->operator = $operator;
        $this->left = $left;
        $this->right = $right;
    }
}

/**
 * Conditional Node
 */
class ConditionalNode extends ASTNode {
    public $condition;
    public $thenBranch;
    public $elseBranch;
    
    public function __construct($condition, $thenBranch, $elseBranch = null, $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'Conditional';
        $this->condition = $condition;
        $this->thenBranch = $thenBranch;
        $this->elseBranch = $elseBranch;
    }
}

/**
 * Loop Node
 */
class LoopNode extends ASTNode {
    public $loopType; // 'for', 'while', 'foreach'
    public $init;
    public $condition;
    public $update;
    public $body;
    
    public function __construct($loopType, $init, $condition, $update, $body, $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'Loop';
        $this->loopType = $loopType;
        $this->init = $init;
        $this->condition = $condition;
        $this->update = $update;
        $this->body = $body;
    }
}

/**
 * Return Node
 */
class ReturnNode extends ASTNode {
    public $value;
    
    public function __construct($value, $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'Return';
        $this->value = $value;
    }
}

/**
 * Identifier Node
 */
class IdentifierNode extends ASTNode {
    public $name;
    
    public function __construct($name, $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'Identifier';
        $this->name = $name;
    }
}

/**
 * Literal Node
 */
class LiteralNode extends ASTNode {
    public $value;
    
    public function __construct($value, $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'Literal';
        $this->value = $value;
    }
}

/**
 * Object Literal Node
 */
class ObjectLiteralNode extends ASTNode {
    public $properties; // array of key-value pairs
    
    public function __construct($properties = [], $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'ObjectLiteral';
        $this->properties = $properties;
    }
}

/**
 * Array Literal Node
 */
class ArrayLiteralNode extends ASTNode {
    public $elements;
    
    public function __construct($elements = [], $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'ArrayLiteral';
        $this->elements = $elements;
    }
}

/**
 * Member Access Node (object.property or array[index])
 */
class MemberAccessNode extends ASTNode {
    public $object;
    public $property;
    public $isComputed; // true for array[index], false for object.property
    
    public function __construct($object, $property, $isComputed = false, $line = 1, $column = 1) {
        parent::__construct($line, $column);
        $this->type = 'MemberAccess';
        $this->object = $object;
        $this->property = $property;
        $this->isComputed = $isComputed;
    }
}

