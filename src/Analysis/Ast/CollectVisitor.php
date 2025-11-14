<?php

declare(strict_types=1);

namespace Reaperman\Analysis\Ast;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;

final class CollectVisitor extends NodeVisitorAbstract
{
    /** @var array<string, array{file:string,line:int,methods:array<string, array{visibility:string,line:int}>}> */
    public array $classes = [];

    /** @var array<string, array{file:string,line:int}> */
    public array $functions = [];

    /** @var array<string, array<string, bool>> classFqn => [methodName => true] */
    public array $methodReferences = [];

    /** @var array<string, bool> */
    public array $functionReferences = [];

    /** @var array<string, bool> */
    public array $instantiations = [];

    private ?string $currentClass = null;
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            if ($node->name === null) {
                return null; // anonymous class
            }
            $fqn = $this->fqnFromNode($node);
            $this->currentClass = $fqn;
            $this->classes[$fqn] = $this->classes[$fqn] ?? [
                'file' => $this->file,
                'line' => $node->getStartLine(),
                'methods' => [],
            ];
        }

        if ($node instanceof ClassMethod && $this->currentClass) {
            $visibility = $node->isPrivate() ? 'private' : ($node->isProtected() ? 'protected' : 'public');
            $this->classes[$this->currentClass]['methods'][$node->name->toString()] = [
                'visibility' => $visibility,
                'line' => $node->getStartLine(),
            ];
        }

        if ($node instanceof Function_) {
            $fqn = $this->fqnFromNode($node);
            $this->functions[$fqn] = [
                'file' => $this->file,
                'line' => $node->getStartLine(),
            ];
        }

        if ($node instanceof FuncCall) {
            if ($node->name instanceof Name) {
                $name = $this->resolvedName($node->name);
                $this->functionReferences[$name] = true;
            }
        }

        if ($node instanceof MethodCall && $this->currentClass) {
            if ($node->var instanceof Node\Expr\Variable && $node->var->name === 'this' && $node->name instanceof Identifier) {
                $this->methodReferences[$this->currentClass][$node->name->toString()] = true;
            }
        }

        if ($node instanceof StaticCall) {
            if ($node->class instanceof Name && $node->name instanceof Identifier) {
                $className = $node->class->toString();
                if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
                    if ($this->currentClass) {
                        $this->methodReferences[$this->currentClass][$node->name->toString()] = true;
                    }
                } else {
                    $resolvedClass = $this->resolvedName($node->class);
                    $this->methodReferences[$resolvedClass][$node->name->toString()] = true;
                }
            }
        }

        if ($node instanceof New_) {
            if ($node->class instanceof Name) {
                $this->instantiations[$this->resolvedName($node->class)] = true;
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Class_) {
            $this->currentClass = null;
        }
        return null;
    }

    private function fqnFromNode(Node $node): string
    {
        /** @var mixed $nn */
        $nn = $node->getAttribute('namespacedName');
        if ($nn instanceof Name) {
            return '\\' . ltrim($nn->toString(), '\\');
        }
        if ($node instanceof Class_ && $node->name) {
            return $node->name->toString();
        }
        if ($node instanceof Function_) {
            return $node->name->toString();
        }
        return '';
    }

    private function resolvedName(Name $name): string
    {
        /** @var mixed $rn */
        $rn = $name->getAttribute('resolvedName');
        if ($rn instanceof Name) {
            return '\\' . ltrim($rn->toString(), '\\');
        }
        return '\\' . ltrim($name->toString(), '\\');
    }
}
