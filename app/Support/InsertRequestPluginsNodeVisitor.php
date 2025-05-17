<?php

namespace Cloakr\Client\Support;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class InsertRequestPluginsNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'platform_url') {
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

            $requestPluginsNode->setAttribute('comments', [
                new Doc("
/*
|--------------------------------------------------------------------------
| Request Plugins
|--------------------------------------------------------------------------
|
| Request plugins analyze the incoming HTTP request and extract certain
| data of interest to show in the CLI or UI, for example which event
| was sent by a billing provider or a webhook from a service like GitHub.
|
*/")
            ]);

            return [
                $node,
                $requestPluginsNode,
            ];
        }
    }
}
