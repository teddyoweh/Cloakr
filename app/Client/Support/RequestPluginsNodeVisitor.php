<?php

namespace App\Client\Support;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RequestPluginsNodeVisitor extends NodeVisitorAbstract
{
    protected array $plugins;

    public function __construct(array $plugins)
    {
        $this->plugins = $plugins;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'request_plugins') {
            $node->value = new Node\Expr\Array_(array_map(function ($plugin) {
                return new Node\Expr\ArrayItem(
                    new Node\Expr\ClassConstFetch(
                        new Node\Name\FullyQualified($plugin), 'class'
                    )
                );
            }, $this->plugins),
                [
                    'kind' => Node\Expr\Array_::KIND_SHORT
                ]
            );

            return $node;
        }
    }
}
