<?php

namespace Cloakr\Client\Support;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class InsertRequestPluginsNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'memory_limit') {
            $requestPluginsNode = new Node\Expr\ArrayItem(
                new Node\Expr\Array_(
                    [
                        new Node\Expr\ArrayItem(
                            new Node\Expr\ClassConstFetch(
                                new Node\Name\FullyQualified('Cloakr\\Client\\Logger\\Plugins\\PaddleBillingPlugin'), 'class'
                            )
                        ),
                        new Node\Expr\ArrayItem(
                            new Node\Expr\ClassConstFetch(
                                new Node\Name\FullyQualified('Cloakr\\Client\\Logger\\Plugins\\GitHubPlugin'), 'class'
                            )
                        ),

                    ],
                    [
                        'kind' => Node\Expr\Array_::KIND_SHORT
                    ]
                ),
                new Node\Scalar\String_('request_plugins')
            );

            return [
                $node,
                $requestPluginsNode,
            ];
        }
    }
}
