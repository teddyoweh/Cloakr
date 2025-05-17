<?php

namespace Cloakr\Client\Support;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class ClearServerNodeVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_server') {
            $node->value = new String_('main');

            return $node;
        }
    }
}
