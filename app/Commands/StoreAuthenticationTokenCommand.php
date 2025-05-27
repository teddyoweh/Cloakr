<?php

namespace Cloakr\Client\Commands;


use Cloakr\Client\Commands\Support\ValidateCloakrToken;
use Cloakr\Client\Contracts\FetchesPlatformDataContract;
use Cloakr\Client\Support\TokenNodeVisitor;
use Cloakr\Client\Traits\FetchesPlatformData;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

use function Cloakr\Common\banner;
use function Cloakr\Common\error;
use function Cloakr\Common\info;
use function Cloakr\Common\success;
use function Laravel\Prompts\text;

class StoreAuthenticationTokenCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected $signature = 'token {token?} {--clean}';

    protected $description = 'Set the authentication token to use with Cloakr.';

    protected ?string $token = '';

    protected bool $newSetup = false;

    public function handle()
    {
        $this->token = $this->argument('token');
        $this->newSetup = empty(config('cloakr.auth_token'));

        if (empty($this->token) && !$this->newSetup) {
            return $this->call('token:get', ['--no-interaction' => $this->option('no-interaction')]);
        }

        if (!$this->option('no-interaction')) {
            banner();
        }

        if (empty($this->token)) {
            info("There is no authentication token specified yet.", newLine: true);
            info("If you don't have a token, visit <a href='https://cloakr.dev'>cloakr.dev</a> to create your free account.");
            $this->token = text(
                label: 'Cloakr token',
                required: true,
            );
        }

        if ($this->cloakrToken()->isInvalid()) {
            error("Token $this->token is invalid. Please check your token and try again. If you don't have a token, visit <a href='https://cloakr.dev'>cloakr.dev</a> to create your free account.");

            if ($this->cloakrToken()->hasError() && $this->getOutput()->isVerbose()) {
                info();
                info($this->cloakrToken()->getError());
            }

            exit;
        }

        $this->rememberPreviousSetup();

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.cloakr',
            'config.php',
        ]);

        if (!file_exists($configFile)) {
            @mkdir(dirname($configFile), 0777, true);
            $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/cloakr.php'), $this->token);
        } else {
            $updatedConfigFile = $this->modifyConfigurationFile($configFile, $this->token);
        }

        file_put_contents($configFile, $updatedConfigFile);

        if (!$this->option('no-interaction')) {

            info("Setting up new Cloakr token <span class='font-bold'>$this->token</span>...");

            (new SetupCloakrProToken)($this->token);
        } else {
            info("Token set to $this->token.");
        }

        if ($this->newSetup) {
            info();
            success("🎉 You're all set! Share your first site by running `cloakr` in your site's directory.");
            info();
            info("If you want to learn more about how to use Cloakr, checkout the <a href='https://cloakr.dev/docs/getting-started/sharing-your-first-site'>documentation</a>.");
        }

    }

    protected function rememberPreviousSetup(): void
    {

        $previousSetup = [
            'token' => config('cloakr.auth_token'),
            'default_server' => config('cloakr.default_server'),
            'default_domain' => config('cloakr.default_domain'),
        ];

        $previousSetupPath = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.cloakr',
            'previous_setup.json',
        ]);

        file_put_contents($previousSetupPath, json_encode($previousSetup));
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

    public function getToken(): string
    {
        return $this->token;
    }
}
