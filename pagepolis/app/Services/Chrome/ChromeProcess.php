<?php

namespace App\Services\Chrome;

use RuntimeException;

/**
 * Arranca un Chrome headless con el puerto de depuración abierto y permite
 * crear pestañas. Cada pestaña corre en su PROPIO proceso de renderizado, así
 * que capturar N miniaturas a la vez reparte el trabajo entre varios núcleos
 * en lugar de hacer cola en uno solo.
 */
class ChromeProcess
{
    /** @var resource|null */
    private $process = null;

    private array $pipes = [];

    private int $port;

    private string $profileDir;

    private string $logFile = '';

    public function __construct(private ?string $binary = null)
    {
        $this->binary ??= self::locateBinary();
        $this->port = self::freePort();
        $this->profileDir = rtrim(sys_get_temp_dir(), '\\/') . '/pagepolis-thumbs-' . getmypid();
    }

    /** Rutas habituales del ejecutable, por sistema. Se puede forzar con CHROME_PATH. */
    public static function locateBinary(): string
    {
        $fromEnv = env('CHROME_PATH') ?: getenv('CHROME_PATH');
        if ($fromEnv && is_file($fromEnv)) {
            return $fromEnv;
        }

        $candidates = [
            // Windows
            'C:\Program Files\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
            getenv('LOCALAPPDATA') . '\Google\Chrome\Application\chrome.exe',
            'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
            // macOS
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            // Linux
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/snap/bin/chromium',
        ];

        foreach ($candidates as $path) {
            if ($path && is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException(
            'No se encontró Chrome. Instálalo o define CHROME_PATH con la ruta al ejecutable.'
        );
    }

    private static function freePort(): int
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            return random_int(45000, 60000);
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    public function start(float $timeout = 30.0): void
    {
        @mkdir($this->profileDir, 0755, true);

        $args = [
            $this->binary,
            '--headless=new',
            '--remote-debugging-port=' . $this->port,
            '--user-data-dir=' . $this->profileDir,
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-extensions',
            '--disable-background-networking',
            '--disable-sync',
            '--mute-audio',
            '--hide-scrollbars',
            // Sin esto, en headless el compositor puede no pintar los vídeos
            // ni las animaciones de las plantillas antes de la captura.
            '--autoplay-policy=no-user-gesture-required',
            '--disable-features=CalculateNativeWinOcclusion',
            'about:blank',
        ];

        $command = implode(' ', array_map(fn ($a) => escapeshellarg($a), $args));

        // La salida de Chrome va a un ARCHIVO, no a una tubería. Con tuberías
        // hay que ir vaciándolas: headless escribe avisos sin parar y, cuando
        // se llena el búfer del sistema operativo, el proceso se queda
        // bloqueado escribiendo. Se notaba como que la generación arrancaba
        // bien y luego se arrastraba hasta pararse.
        $this->logFile = $this->profileDir . '/chrome.log';
        $descriptors = [
            1 => ['file', $this->logFile, 'a'],
            2 => ['file', $this->logFile, 'a'],
        ];
        $process = proc_open($command, $descriptors, $this->pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('No se pudo arrancar Chrome.');
        }
        $this->process = $process;

        // Red de seguridad: si el comando muere por una excepción o se corta a
        // medias, el navegador se cierra igual. Sin esto cada ejecución
        // interrumpida dejaba una veintena de procesos sueltos.
        register_shutdown_function(function () {
            $this->stop();
        });

        // Espera a que el puerto de depuración responda.
        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            $version = $this->http('GET', '/json/version', 2.0);
            if ($version !== null && str_contains($version, 'webSocketDebuggerUrl')) {
                return;
            }
            usleep(200_000);
        }

        $this->stop();
        throw new RuntimeException('Chrome arrancó pero no abrió el puerto de depuración.');
    }

    /**
     * Petición HTTP al endpoint de depuración, por socket y con tiempos de
     * espera explícitos.
     *
     * NO se usa file_get_contents: el wrapper http de PHP lee hasta EOF y el
     * servidor de DevTools mantiene la conexión abierta, así que cada consulta
     * se quedaba esperando hasta agotar default_socket_timeout (60 s). Eso
     * dejaba la generación “colgada” justo al arrancar. Aquí se respeta el
     * Content-Length y se cierra.
     */
    private function http(string $method, string $path, float $timeout = 10.0): ?string
    {
        $socket = @stream_socket_client(
            "tcp://127.0.0.1:{$this->port}",
            $errno,
            $errstr,
            $timeout
        );
        if ($socket === false) {
            return null;
        }
        stream_set_timeout($socket, (int) ceil($timeout));

        $request = "{$method} {$path} HTTP/1.1\r\n"
            . "Host: 127.0.0.1:{$this->port}\r\n"
            . "Connection: close\r\n\r\n";
        if (@fwrite($socket, $request) === false) {
            @fclose($socket);

            return null;
        }

        $response = '';
        $deadline = microtime(true) + $timeout;
        $bodyAt   = false;
        $length   = null;

        while (microtime(true) < $deadline) {
            // Se corta en cuanto el cuerpo está completo según Content-Length.
            // Esperar a feof() no sirve: DevTools deja la conexión abierta
            // pese al "Connection: close", así que cada consulta agotaba su
            // tiempo entero aunque la respuesta ya hubiera llegado (eran 10 s
            // por pestaña, 60 s solo en abrirlas).
            if ($bodyAt === false) {
                $bodyAt = strpos($response, "\r\n\r\n");
                if ($bodyAt !== false) {
                    $headers = substr($response, 0, $bodyAt);
                    if (preg_match('/^Content-Length:\s*(\d+)/mi', $headers, $m)) {
                        $length = (int) $m[1];
                    }
                    $bodyAt += 4;
                }
            }
            if ($bodyAt !== false && $length !== null && strlen($response) - $bodyAt >= $length) {
                break;
            }
            if (feof($socket)) {
                break;
            }

            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                $info = stream_get_meta_data($socket);
                if (!empty($info['timed_out'])) {
                    break;
                }
                usleep(2000);
                continue;
            }
            $response .= $chunk;
        }
        @fclose($socket);

        if ($bodyAt === false) {
            $bodyAt = strpos($response, "\r\n\r\n");
            if ($bodyAt === false) {
                return null;
            }
            $bodyAt += 4;
        }

        return $length === null
            ? substr($response, $bodyAt)
            : substr($response, $bodyAt, $length);
    }

    /** Abre una pestaña nueva y devuelve su conexión CDP. */
    public function newTab(): CdpConnection
    {
        // El endpoint moderno exige PUT; algunas versiones aún aceptan GET.
        $raw = $this->http('PUT', '/json/new?about:blank')
            ?: $this->http('GET', '/json/new?about:blank');
        if ($raw === null) {
            throw new RuntimeException('No se pudo abrir una pestaña en Chrome.');
        }
        $target = json_decode($raw, true);
        if (!isset($target['webSocketDebuggerUrl'])) {
            throw new RuntimeException('Chrome no devolvió la URL de depuración de la pestaña.');
        }

        return new CdpConnection($target['webSocketDebuggerUrl']);
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            $this->http('GET', '/json/close', 2.0);
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    @fclose($pipe);
                }
            }

            // Se mata el ÁRBOL, no solo el proceso lanzado. Chrome levanta un
            // proceso por pestaña más los de GPU y red; con proc_terminate a
            // secas los hijos quedaban vivos y se acumulaban entre ejecuciones
            // (llegaron a ser 149 comiéndose la máquina, lo que hacía que
            // hasta el servidor de desarrollo tardara segundos por petición).
            $status = @proc_get_status($this->process);
            $pid = $status['pid'] ?? null;
            if ($pid && stripos(PHP_OS_FAMILY, 'Windows') === 0) {
                @exec('taskkill /F /T /PID ' . ((int) $pid) . ' 2>NUL');
            }
            @proc_terminate($this->process);
            @proc_close($this->process);
            $this->process = null;
        }
        $this->deleteProfile();
    }

    private function deleteProfile(): void
    {
        if (!is_dir($this->profileDir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->profileDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($this->profileDir);
    }
}
