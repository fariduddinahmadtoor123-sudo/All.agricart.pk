<?php

namespace Modules\Forex\Support;

use Modules\Forex\Models\Mt5Account;
use RuntimeException;
use ZipArchive;

class ConnectorPackage
{
    public function downloadName(Mt5Account $account): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $account->name) ?: 'mt5';

        return 'desk-mt5-'.$safe.'-'.$account->login.'.zip';
    }

    public function build(Mt5Account $account): string
    {
        $token = $account->ensureConnectorToken();
        $files = $this->files($account, $token);
        $temp = storage_path('app/connectors/'.$account->id.'-'.uniqid('pkg_', true).'.zip');

        if (! is_dir(dirname($temp))) {
            mkdir(dirname($temp), 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the connector package.');
        }

        foreach ($files as $name => $contents) {
            if (is_file($contents)) {
                $zip->addFile($contents, $name);
            } else {
                $zip->addFromString($name, $contents);
            }
        }

        $zip->close();

        return $temp;
    }

    public function prepareLocal(Mt5Account $account): string
    {
        $token = $account->ensureConnectorToken();
        $directory = $this->localDirectory($account);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create the local connector folder.');
        }

        foreach ($this->files($account, $token) as $name => $contents) {
            $target = $directory.DIRECTORY_SEPARATOR.$name;

            if (is_file($contents)) {
                if (! copy($contents, $target)) {
                    throw new RuntimeException('Could not copy '.$name.' into the local connector folder.');
                }
            } elseif (file_put_contents($target, $contents) === false) {
                throw new RuntimeException('Could not write '.$name.' into the local connector folder.');
            }
        }

        return $directory;
    }

    /**
     * Prepare files, install the shared Python runtime once, then start the connector window.
     */
    public function startLocal(Mt5Account $account): string
    {
        $directory = $this->prepareLocal($account);
        $this->ensureRuntime($directory);
        $bat = $directory.DIRECTORY_SEPARATOR.'start-mt5.bat';

        if (PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException('The MT5 connector can only start on Windows.');
        }

        $command = 'start "Desk MT5 Connector" cmd /k call '.escapeshellarg($bat);
        pclose(popen($command, 'r'));

        return $bat;
    }

    public function localDirectory(Mt5Account $account): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $account->name) ?: 'mt5';

        return storage_path('app/connectors/ready/'.$account->id.'-'.$safe);
    }

    public function runtimePython(): string
    {
        $local = getenv('LOCALAPPDATA') ?: (getenv('USERPROFILE') ?: '').DIRECTORY_SEPARATOR.'AppData'.DIRECTORY_SEPARATOR.'Local';

        return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $local), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'desk-mt5-bridge'
            .DIRECTORY_SEPARATOR.'venv'
            .DIRECTORY_SEPARATOR.'Scripts'
            .DIRECTORY_SEPARATOR.'python.exe';
    }

    public function ensureRuntime(?string $connectorDirectory = null): void
    {
        $runtime = $this->runtimePython();

        if (is_file($runtime)) {
            return;
        }

        $base = $this->detectPython();

        if ($base === null) {
            throw new RuntimeException('Python was not found. Laragon Python or Python 3.10+ is required on this PC.');
        }

        $venv = dirname($runtime, 2);

        if (! is_dir(dirname($venv))) {
            mkdir(dirname($venv), 0755, true);
        }

        $create = escapeshellarg($base).' -m venv '.escapeshellarg($venv);
        exec($create.' 2>&1', $out, $code);

        if ($code !== 0 || ! is_file($runtime)) {
            throw new RuntimeException('Could not create the connector Python environment.');
        }

        $requirements = ($connectorDirectory ?: dirname(__DIR__).DIRECTORY_SEPARATOR.'bridge')
            .DIRECTORY_SEPARATOR.'requirements.txt';

        $pip = escapeshellarg($runtime).' -m pip install --disable-pip-version-check -r '.escapeshellarg($requirements);
        exec($pip.' 2>&1', $pipOut, $pipCode);

        if ($pipCode !== 0) {
            throw new RuntimeException('Could not install MetaTrader 5 Python packages.');
        }
    }

    public function detectPython(): ?string
    {
        $candidates = [];

        foreach (glob('C:/laragon/bin/python/*/python.exe') ?: [] as $path) {
            $candidates[] = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        $candidates = array_merge($candidates, [
            'C:\\laragon\\bin\\python\\python-3.13\\python.exe',
            'C:\\laragon\\bin\\python\\python-3.12\\python.exe',
            'C:\\Python313\\python.exe',
            'C:\\Python312\\python.exe',
            'C:\\Python311\\python.exe',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path) && ! str_contains($path, 'WindowsApps')) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function files(Mt5Account $account, string $token): array
    {
        $bridgeDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'bridge';
        $bridge = $bridgeDir.DIRECTORY_SEPARATOR.'mt5_bridge.py';
        $requirements = $bridgeDir.DIRECTORY_SEPARATOR.'requirements.txt';

        if (! is_file($bridge) || ! is_file($requirements)) {
            throw new RuntimeException('Connector bridge files are missing.');
        }

        return [
            'start-mt5.bat' => $this->batchFile($account, $token),
            'mt5_bridge.py' => $bridge,
            'requirements.txt' => $requirements,
            'README.txt' => $this->readme($account),
        ];
    }

    private function batValue(string $value): string
    {
        return str_replace(["\r", "\n", '"', '%', '!', '&', '|', '<', '>'], '', $value);
    }

    private function batchFile(Mt5Account $account, string $token): string
    {
        $api = $this->batValue(rtrim((string) config('app.url'), '/'));
        $login = $this->batValue((string) $account->login);
        $server = $this->batValue((string) $account->server);
        $accountId = (string) $account->id;
        $title = $this->batValue((string) $account->name);
        $token = $this->batValue($token);
        $hint = $this->batValue((string) ($this->detectPython() ?? ''));
        $symbols = $this->batValue(implode(',', Symbols::allowed()));

        return <<<BAT
@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Desk MT5 Connector - {$title}

set "LARAVEL_API_URL={$api}"
set "DESK_CONNECTOR_TOKEN={$token}"
set "DESK_MT5_ACCOUNT_ID={$accountId}"
set "MT5_LOGIN={$login}"
set "MT5_SERVER={$server}"
set "MT5_SYMBOLS={$symbols}"
set "MT5_QUOTE_SECONDS=0.01"
set "MT5_PUSH_SECONDS=0.5"
set "DESK_BRIDGE_HOST=127.0.0.1"
set "DESK_BRIDGE_PORT=8766"
set "DESK_BASE_PYTHON={$hint}"

set "PY=%LOCALAPPDATA%\\desk-mt5-bridge\\venv\\Scripts\\python.exe"

if not exist "%PY%" goto setup
goto ready

:setup
call :find_python
if not defined BASE_PY goto need_python
echo Setting up the Desk connector on this PC. This can take a minute.
"%BASE_PY%" -m venv "%LOCALAPPDATA%\\desk-mt5-bridge\\venv"
if errorlevel 1 goto venv_failed
"%PY%" -m pip install --disable-pip-version-check -r "%~dp0requirements.txt"
if errorlevel 1 goto pip_failed
goto ready

:need_python
echo.
echo Python was not found on this PC.
echo Desk looked for Laragon Python and normal Python installs.
echo If Laragon is installed, open Laragon and make sure Python is available, then run Start connector again from Desk.
echo.
pause
exit /b 1

:venv_failed
echo.
echo Python was found, but the connector environment could not be created.
echo.
pause
exit /b 1

:pip_failed
echo.
echo The MetaTrader 5 Python package could not be installed.
echo Check the internet connection, then start the connector again from Desk.
echo.
pause
exit /b 1

:find_python
set "BASE_PY="
if defined DESK_BASE_PYTHON if exist "%DESK_BASE_PYTHON%" set "BASE_PY=%DESK_BASE_PYTHON%"
if defined BASE_PY exit /b 0
if exist "C:\\laragon\\bin\\python\\python-3.13\\python.exe" set "BASE_PY=C:\\laragon\\bin\\python\\python-3.13\\python.exe"
if defined BASE_PY exit /b 0
if exist "C:\\laragon\\bin\\python\\python-3.12\\python.exe" set "BASE_PY=C:\\laragon\\bin\\python\\python-3.12\\python.exe"
if defined BASE_PY exit /b 0
for /d %%D in ("C:\\laragon\\bin\\python\\python-*") do if exist "%%~fD\\python.exe" set "BASE_PY=%%~fD\\python.exe"
if defined BASE_PY exit /b 0
where py >nul 2>&1
if not errorlevel 1 (
    for /f "delims=" %%I in ('py -3 -c "import sys; print(sys.executable)" 2^>nul') do set "BASE_PY=%%I"
)
if defined BASE_PY call :reject_store_python
if defined BASE_PY exit /b 0
where python >nul 2>&1
if not errorlevel 1 (
    for /f "delims=" %%I in ('python -c "import sys; print(sys.executable)" 2^>nul') do set "BASE_PY=%%I"
)
if defined BASE_PY call :reject_store_python
exit /b 0

:reject_store_python
echo %BASE_PY% | find /I "WindowsApps" >nul
if not errorlevel 1 set "BASE_PY="
if not defined BASE_PY exit /b 0
"%BASE_PY%" -c "import sys; raise SystemExit(0 if sys.version_info >= (3, 10) else 1)" >nul 2>&1
if errorlevel 1 set "BASE_PY="
exit /b 0

:ready
call :open_terminal
if errorlevel 2 goto need_mt5

if not exist "%~dp0mt5_bridge.py" goto need_script

echo.
echo Desk connector is starting for {$title} ({$login}).
echo Leave this window open. Closing it stops the live rates.
echo.

"%PY%" "%~dp0mt5_bridge.py"
echo.
echo The connector stopped. Use Start connector in Desk to open it again.
pause
exit /b 0

:need_mt5
echo.
echo MetaTrader 5 is not installed or was not found.
echo Open MetaTrader 5 once from your broker, sign in to {$login}, then start the connector again from Desk.
echo.
pause
exit /b 1

:need_script
echo.
echo mt5_bridge.py was not found beside this file.
echo Use Start connector in Desk so the files are prepared again.
echo.
pause
exit /b 1

:open_terminal
set "MT5="
if exist "%ProgramFiles%\\MetaTrader 5\\terminal64.exe" set "MT5=%ProgramFiles%\\MetaTrader 5\\terminal64.exe"
if not defined MT5 if exist "%ProgramFiles%\\MetaTrader 5 EXNESS\\terminal64.exe" set "MT5=%ProgramFiles%\\MetaTrader 5 EXNESS\\terminal64.exe"
if not defined MT5 (
    for /d %%D in ("%ProgramFiles%\\*MetaTrader*") do if exist "%%~fD\\terminal64.exe" set "MT5=%%~fD\\terminal64.exe"
)
if not defined MT5 exit /b 2
set "MT5_TERMINAL_PATH=%MT5%"
tasklist /FI "IMAGENAME eq terminal64.exe" | find /I "terminal64.exe" >nul
if errorlevel 1 start "" "%MT5%"
exit /b 0
BAT;
    }

    private function readme(Mt5Account $account): string
    {
        return <<<TXT
Desk MT5 connector — {$account->name}

Easiest way:
1. Open MetaTrader 5 and sign in to login {$account->login} on server {$account->server}.
2. In Desk → MT5 accounts click Start connector.
3. Leave the black connector window open.

Desk uses Laragon Python on this PC when available. You do not need to install Python by hand for each account.
One shared connector runtime is used for every MT5 account on this computer.
TXT;
    }
}
