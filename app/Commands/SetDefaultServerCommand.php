<?php

namespace App\Commands;

use App\Client\Support\DefaultServerNodeVisitor;
use App\Client\Support\InsertDefaultServerNodeVisitor;
use App\Commands\Plugins\SetUpCloakrDefaultServer;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

use function Laravel\Prompts\confirm;
use function Termwind\render;

class SetDefaultServerCommand extends Command
{
    protected $signature = 'default-server {server?}';

    protected $description = 'Set or retrieve the default server to use with Cloakr.';

    public function handle()
    {
        $server = $this->argument('server');

        if (! is_null($server)) {

            render("<div class='ml-3'>✅ Set Cloakr default server to <span class='font-bold'>$server</span>.</div>");

            $configFile = implode(DIRECTORY_SEPARATOR, [
                $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
                '.cloakr',
                'config.php',
            ]);

            if (! file_exists($configFile)) {
                @mkdir(dirname($configFile), 0777, true);
                $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/cloakr.php'), $server);
            } else {
                $updatedConfigFile = $this->modifyConfigurationFile($configFile, $server);
            }

            file_put_contents($configFile, $updatedConfigFile);

            return;
        }

        render('<div class="ml-2 my-1"><div class="text-pink-500 font-bold"><span class="font-bold pr-0.5">></span> Cloakr</div>');


        if (is_null($server = config('cloakr.default_server'))) {
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">There is no default server specified.</div>');
        } else {
            render("<div class='ml-3'>Current default server: <span class='font-bold'>$server</span>.</div>");
        }


        if(confirm('Would you like to set a new default server?')) {
            (new SetUpCloakrDefaultServer)(config('cloakr.auth_token'));
        }
    }

    protected function modifyConfigurationFile(string $configFile, string $server)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeFinder = new NodeFinder;

        $defaultServerNode = $nodeFinder->findFirst($newStmts, function (Node $node) {
            return $node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_server';
        });

        if (is_null($defaultServerNode)) {
            $nodeTraverser = new NodeTraverser;
            $nodeTraverser->addVisitor(new InsertDefaultServerNodeVisitor());
            $newStmts = $nodeTraverser->traverse($newStmts);
        }

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new DefaultServerNodeVisitor($server));

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}
