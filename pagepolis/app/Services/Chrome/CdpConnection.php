<?php

namespace App\Services\Chrome;

use RuntimeException;

/**
 * Cliente mínimo del Chrome DevTools Protocol sobre WebSocket.
 *
 * POR QUÉ A MANO. Solo se usa para generar las miniaturas de la galería
 * (`php artisan templates:thumbnails`), un proceso que se ejecuta de vez en
 * cuando en local o al desplegar. Traer Puppeteer/Node o una librería de
 * WebSocket a producción por esto sería desproporcionado: el protocolo que
 * hace falta son cuatro mensajes JSON y el handshake estándar de RFC 6455.
 *
 * No pretende ser un cliente completo: es síncrono, habla con UNA pestaña y
 * asume que el servidor (Chrome, en localhost) no fragmenta de forma hostil.
 */
class CdpConnection
{
    /** @var resource */
    private $socket;

    private int $lastId = 0;

    /** Mensajes recibidos que no eran la respuesta esperada (eventos). */
    private array $pending = [];

    public function __construct(string $wsUrl, float $timeout = 30.0)
    {
        $parts = parse_url($wsUrl);
        $host  = $parts['host'] ?? '127.0.0.1';
        $port  = $parts['port'] ?? 9222;
        $path  = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);
        if ($socket === false) {
            throw new RuntimeException("No se pudo conectar a Chrome en {$host}:{$port}: {$errstr}");
        }
        stream_set_timeout($socket, (int) $timeout);
        $this->socket = $socket;

        $key = base64_encode(random_bytes(16));
        $handshake = "GET {$path} HTTP/1.1\r\n"
            . "Host: {$host}:{$port}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: {$key}\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        fwrite($socket, $handshake);

        $response = '';
        while (!str_contains($response, "\r\n\r\n")) {
            $chunk = fread($socket, 1024);
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('Chrome cerró la conexión durante el handshake.');
            }
            $response .= $chunk;
        }
        if (!str_contains($response, '101')) {
            throw new RuntimeException('Chrome rechazó el handshake WebSocket.');
        }
    }

    /**
     * Envía un comando SIN esperar la respuesta y devuelve su id.
     *
     * Permite lanzar el mismo comando en varias pestañas y recoger luego los
     * resultados: las esperas se solapan en vez de sumarse. Con seis pestañas
     * y un tope de espera de 3,5 s eso son 3,5 s en total en lugar de 21.
     */
    public function send(string $method, array $params = []): int
    {
        $id = ++$this->lastId;
        $this->writeFrame(json_encode([
            'id'     => $id,
            'method' => $method,
            'params' => (object) $params,
        ]));

        return $id;
    }

    /** Recoge la respuesta de un comando ya enviado con send(). */
    public function receive(int $id, float $timeout = 30.0): array
    {
        foreach ($this->pending as $i => $message) {
            if (($message['id'] ?? null) === $id) {
                unset($this->pending[$i]);
                if (isset($message['error'])) {
                    throw new RuntimeException('CDP: ' . ($message['error']['message'] ?? 'error'));
                }

                return $message['result'] ?? [];
            }
        }

        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            $message = $this->readMessage($deadline - microtime(true));
            if ($message === null) {
                continue;
            }
            if (($message['id'] ?? null) === $id) {
                if (isset($message['error'])) {
                    throw new RuntimeException('CDP: ' . ($message['error']['message'] ?? 'error'));
                }

                return $message['result'] ?? [];
            }
            $this->pending[] = $message;
        }

        throw new RuntimeException('CDP: se agotó el tiempo de espera.');
    }

    /** Envía un comando y espera SU respuesta (descarta eventos por el camino). */
    public function call(string $method, array $params = [], float $timeout = 30.0): array
    {
        return $this->receive($this->send($method, $params), $timeout);
    }

    /** Espera a que llegue un evento concreto. Devuelve false si expira. */
    public function waitForEvent(string $method, float $timeout = 10.0): bool
    {
        foreach ($this->pending as $i => $message) {
            if (($message['method'] ?? null) === $method) {
                unset($this->pending[$i]);

                return true;
            }
        }

        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            $message = $this->readMessage($deadline - microtime(true));
            if ($message !== null && ($message['method'] ?? null) === $method) {
                return true;
            }
        }

        return false;
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
    }

    /** Lee un mensaje completo, reensamblando fragmentos. Null si expira. */
    private function readMessage(float $timeout): ?array
    {
        if ($timeout <= 0) {
            return null;
        }
        stream_set_timeout($this->socket, (int) ceil($timeout), (int) (fmod($timeout, 1) * 1_000_000));

        $payload = '';
        while (true) {
            $header = $this->readBytes(2, $timeout);
            if ($header === null) {
                return null;
            }
            $byte1  = ord($header[0]);
            $byte2  = ord($header[1]);
            $final  = ($byte1 & 0x80) !== 0;
            $opcode = $byte1 & 0x0F;
            $masked = ($byte2 & 0x80) !== 0;
            $length = $byte2 & 0x7F;

            if ($length === 126) {
                $ext = $this->readBytes(2, $timeout);
                if ($ext === null) {
                    return null;
                }
                $length = unpack('n', $ext)[1];
            } elseif ($length === 127) {
                $ext = $this->readBytes(8, $timeout);
                if ($ext === null) {
                    return null;
                }
                $length = unpack('J', $ext)[1];
            }

            $maskKey = '';
            if ($masked) {
                $maskKey = $this->readBytes(4, $timeout);
                if ($maskKey === null) {
                    return null;
                }
            }

            $data = $length > 0 ? $this->readBytes($length, $timeout) : '';
            if ($data === null) {
                return null;
            }
            if ($masked && $length > 0) {
                for ($i = 0; $i < $length; $i++) {
                    $data[$i] = chr(ord($data[$i]) ^ ord($maskKey[$i % 4]));
                }
            }

            // Ping del servidor: hay que contestar o cierra la conexión.
            if ($opcode === 0x9) {
                $this->writeFrame($data, 0xA);
                continue;
            }
            if ($opcode === 0x8) {
                throw new RuntimeException('Chrome cerró la conexión CDP.');
            }
            if ($opcode === 0xA) {
                continue;
            }

            $payload .= $data;
            if ($final) {
                break;
            }
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** Lee exactamente $count bytes o devuelve null si expira. */
    private function readBytes(int $count, float $timeout): ?string
    {
        $buffer   = '';
        $deadline = microtime(true) + $timeout;
        while (strlen($buffer) < $count) {
            if (microtime(true) > $deadline) {
                return null;
            }
            $chunk = fread($this->socket, $count - strlen($buffer));
            if ($chunk === false) {
                return null;
            }
            if ($chunk === '') {
                $info = stream_get_meta_data($this->socket);
                if (!empty($info['timed_out'])) {
                    return null;
                }
                if (!empty($info['eof'])) {
                    throw new RuntimeException('Chrome cerró la conexión CDP.');
                }
                usleep(1000);
                continue;
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }

    private function writeFrame(string $payload, int $opcode = 0x1): void
    {
        $length = strlen($payload);
        $frame  = chr(0x80 | $opcode);

        // El cliente SIEMPRE enmascara (RFC 6455 §5.3).
        if ($length < 126) {
            $frame .= chr(0x80 | $length);
        } elseif ($length < 65536) {
            $frame .= chr(0x80 | 126) . pack('n', $length);
        } else {
            $frame .= chr(0x80 | 127) . pack('J', $length);
        }

        $mask   = random_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $length; $i++) {
            $frame .= chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
        }

        if (@fwrite($this->socket, $frame) === false) {
            throw new RuntimeException('No se pudo escribir en la conexión CDP.');
        }
    }
}
