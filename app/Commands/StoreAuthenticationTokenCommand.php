<?php

namespace App\Commands;

use App\Client\Support\TokenNodeVisitor;
use App\Commands\Plugins\CommandPlugin;
use App\Commands\Plugins\SetupCloakrProToken;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

use function Termwind\render;

class StoreAuthenticationTokenCommand extends Command
{
    protected $signature = 'token {token?}';

    protected $description = 'Set or retrieve the authentication token to use with Cloakr.';

    public function handle()
    {
        $token = $this->argument('token');

        render('<div class="ml-2 my-1"><div class="text-pink-500 font-bold"><span class="font-bold pr-0.5">></span> Cloakr</div>');


        if (! is_null($token)) {
            render("<div class='ml-3'>Setting up new Cloakr token <span class='font-bold'>$token</span>...</div>");

            $preSetupAction = SetupCloakrProToken::class;
            if (class_exists($preSetupAction) && is_subclass_of($preSetupAction, CommandPlugin::class)) {
                (new $preSetupAction)($token);
            }

            $configFile = implode(DIRECTORY_SEPARATOR, [
                $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
                '.cloakr',
                'config.php',
            ]);

            if (! file_exists($configFile)) {
                @mkdir(dirname($configFile), 0777, true);
                $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/cloakr.php'), $this->argument('token'));
            } else {
                $updatedConfigFile = $this->modifyConfigurationFile($configFile, $this->argument('token'));
            }

            file_put_contents($configFile, $updatedConfigFile);

            render('<div class="ml-3">✅ Done.</p></div>');


            return;
        }

        if (is_null($token = config('cloakr.auth_token'))) {
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">There is no authentication token specified.</div>');
        } else {
            render("<div class='ml-3'>Current authentication token: <span class='font-bold'>$token</span></div>");
        }
    }

    protected function modifyConfigurationFile(string $configFile, string $token)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startTokenPos',
                'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new TokenNodeVisitor($token));

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}
