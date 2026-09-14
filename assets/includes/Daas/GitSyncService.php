<?php
/**
 * Git sync for DaaS platform updates.
 * Authenticated fetch/pull via GIT_ASKPASS (token never in remote URL).
 */

require_once dirname(__DIR__) . '/daas_env.php';

class GitSyncService
{
    /** @var string */
    private $repoPath;

    /** @var string */
    private $branch;

    /** @var string */
    private $token;

    /** @var string */
    private $expectedRepo;

    /** @var string|null */
    private $logFile;

    public function __construct($repoPath = null)
    {
        daas_env_load();
        // __DIR__ = .../assets/includes/Daas → three levels up = httpdocs
        $this->repoPath = $repoPath ?: dirname(dirname(dirname(__DIR__)));
        $this->branch = daas_env('GITHUB_DEFAULT_BRANCH', 'master');
        $this->token = (string) daas_env('GITHUB_TOKEN', '');
        $this->expectedRepo = rtrim((string) daas_env('GITHUB_REPO', ''), '/');
        $this->expectedRepo = preg_replace('#\.git$#', '', $this->expectedRepo);
        $logDir = $this->repoPath . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $this->logFile = $logDir . '/daas-sync.log';
    }

    /**
     * @param string|null $expectedSha
     * @return array{ok:bool,message:string,sha?:string,code?:int}
     */
    public function pull($expectedSha = null)
    {
        if (!is_dir($this->repoPath . '/.git')) {
            return $this->fail('Git repository not found', 422);
        }

        if ($this->token === '') {
            return $this->fail('GITHUB_TOKEN is not configured', 422);
        }

        $dirty = $this->runGit(['status', '--porcelain']);
        if ($dirty['exit'] !== 0) {
            return $this->fail('Unable to read git status: ' . $dirty['output'], 422);
        }
        if (trim($dirty['output']) !== '') {
            return $this->fail('Working tree has local changes; refuse pull', 422);
        }

        $remote = $this->runGit(['remote', 'get-url', 'origin']);
        if ($remote['exit'] !== 0) {
            return $this->fail('origin remote missing', 422);
        }
        $originUrl = trim($remote['output']);
        if (preg_match('#https?://[^/@]+@#', $originUrl)) {
            return $this->fail('origin URL must not embed credentials', 422);
        }
        $normalizedOrigin = preg_replace('#\.git$#', '', rtrim($originUrl, '/'));
        if ($this->expectedRepo !== '' && strcasecmp($normalizedOrigin, $this->expectedRepo) !== 0) {
            return $this->fail('origin does not match GITHUB_REPO', 422);
        }

        $fetch = $this->runGitAuth(['fetch', 'origin']);
        if ($fetch['exit'] !== 0) {
            return $this->fail('git fetch failed: ' . $fetch['output'], 422);
        }

        $checkout = $this->runGit(['checkout', $this->branch]);
        if ($checkout['exit'] !== 0) {
            return $this->fail('git checkout failed: ' . $checkout['output'], 422);
        }

        $pull = $this->runGitAuth(['pull', '--ff-only', 'origin', $this->branch]);
        if ($pull['exit'] !== 0) {
            return $this->fail('git pull --ff-only failed: ' . $pull['output'], 422);
        }

        $head = $this->runGit(['rev-parse', 'HEAD']);
        if ($head['exit'] !== 0) {
            return $this->fail('Unable to read HEAD', 422);
        }
        $sha = trim($head['output']);

        if (!empty($expectedSha) && strpos($sha, $expectedSha) !== 0 && strpos($expectedSha, $sha) !== 0) {
            // Allow prefix match either way (short vs full sha)
            if ($sha !== $expectedSha) {
                return $this->fail('HEAD sha mismatch after pull (got ' . $sha . ', expected ' . $expectedSha . ')', 422);
            }
        }

        $this->log('pull ok sha=' . $sha);
        return [
            'ok' => true,
            'message' => 'Repository updated',
            'sha' => $sha,
            'code' => 200,
        ];
    }

    /**
     * @return array{branch:string,sha:string,dirty:bool,dirty_files:string,origin:string}
     */
    public function status()
    {
        $branch = $this->runGit(['rev-parse', '--abbrev-ref', 'HEAD']);
        $sha = $this->runGit(['rev-parse', 'HEAD']);
        $dirty = $this->runGit(['status', '--porcelain']);
        $origin = $this->runGit(['remote', 'get-url', 'origin']);

        return [
            'branch' => $branch['exit'] === 0 ? trim($branch['output']) : '',
            'sha' => $sha['exit'] === 0 ? trim($sha['output']) : '',
            'dirty' => $dirty['exit'] === 0 && trim($dirty['output']) !== '',
            'dirty_files' => $dirty['exit'] === 0 ? trim($dirty['output']) : '',
            'origin' => $origin['exit'] === 0 ? trim($origin['output']) : '',
        ];
    }

    /**
     * @param array $args
     * @return array{exit:int,output:string}
     */
    private function runGit(array $args)
    {
        return $this->execGit($args, false);
    }

    /**
     * @param array $args
     * @return array{exit:int,output:string}
     */
    private function runGitAuth(array $args)
    {
        return $this->execGit($args, true);
    }

    /**
     * @param array $args
     * @param bool $withAuth
     * @return array{exit:int,output:string}
     */
    private function execGit(array $args, $withAuth)
    {
        $askpass = null;
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = array_merge(
            ['git', '-c', 'safe.directory=' . $this->repoPath, '-C', $this->repoPath],
            $args
        );
        $env = $_ENV;
        // Ensure critical env vars for child
        foreach (['PATH', 'HOME', 'LANG', 'USER', 'LOGNAME'] as $k) {
            if (!isset($env[$k]) && getenv($k) !== false) {
                $env[$k] = getenv($k);
            }
        }
        $env['GIT_TERMINAL_PROMPT'] = '0';
        $env['GIT_CONFIG_NOSYSTEM'] = '1';
        $env['LC_ALL'] = 'C';

        if ($withAuth) {
            $askpass = tempnam(sys_get_temp_dir(), 'daas_askpass_');
            if ($askpass === false) {
                return ['exit' => 1, 'output' => 'Unable to create askpass script'];
            }
            $script = "#!/bin/sh\n"
                . "case \"\$1\" in\n"
                . "  *sername*) echo \"x-access-token\" ;;\n"
                . "  *) echo \"\$DAAS_GIT_PASSWORD\" ;;\n"
                . "esac\n";
            file_put_contents($askpass, $script);
            chmod($askpass, 0700);
            $env['GIT_ASKPASS'] = $askpass;
            $env['DAAS_GIT_PASSWORD'] = $this->token;
            // Also set SSH_ASKPASS for some git builds
            $env['SSH_ASKPASS'] = $askpass;
            $env['DISPLAY'] = $env['DISPLAY'] ?? ':0';
        }

        $process = proc_open($cmd, $descriptors, $pipes, $this->repoPath, $env);
        if (!is_resource($process)) {
            if ($askpass) {
                @unlink($askpass);
            }
            return ['exit' => 1, 'output' => 'proc_open failed'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($askpass) {
            @unlink($askpass);
        }

        $output = trim($stdout . (strlen(trim($stderr)) ? "\n" . $stderr : ''));
        $output = $this->redact($output);

        if ($exit !== 0) {
            $this->log('git ' . implode(' ', $args) . ' exit=' . $exit . ' ' . $output);
        }

        return ['exit' => $exit, 'output' => $output];
    }

    private function redact($text)
    {
        $text = preg_replace('/github_pat_[A-Za-z0-9_]+/', 'github_pat_***', $text);
        $text = preg_replace('#https://[^:\s]+:[^@\s]+@#', 'https://***:***@', $text);
        return $text;
    }

    private function fail($message, $code = 422)
    {
        $message = $this->redact($message);
        $this->log('fail: ' . $message);
        return [
            'ok' => false,
            'message' => $message,
            'code' => $code,
        ];
    }

    private function log($message)
    {
        if (!$this->logFile) {
            return;
        }
        $line = date('c') . ' ' . $this->redact($message) . "\n";
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
