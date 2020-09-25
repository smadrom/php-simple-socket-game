<?php
declare(strict_types=1);

(new Client('127.0.0.1', 29000))->run();

/**
 * Class Client
 */
class Client
{
    private string $ip;
    private int $port;
    /**
     * @var resource
     */
    private $socket;

    public function __construct(string $ip, int $port)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    public function run(): void
    {
        $this->create()->connect();

        $this->message($this->read());

        while (true) {

            $command = readline('Message: ');

            $this->write($command);

            $message = $this->read();

            $this->message($message);
        }
    }

    private function create(): self
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->socket === false) {
            throw new RuntimeException('Socket create error: ' . $this->getError());
        }

        return $this;
    }

    private function connect(): self
    {
        if (!socket_connect($this->socket, $this->ip, $this->port)) {
            throw new RuntimeException('Socket connect error: ' . $this->getError());
        }

        return $this;
    }

    private function write(string $message): int
    {
        $message .= "\n";

        $result = socket_write($this->socket, $message, mb_strlen($message));

        if ($result === false) {
            throw new RuntimeException('Socket write error: ' . $this->getError());
        }

        return $result;
    }

    private function close(): void
    {
        socket_close($this->socket);
    }

    private function read(): string
    {
        $buffer = socket_read($this->socket, 2048, PHP_NORMAL_READ);

        if ($buffer === false) {
            throw new RuntimeException('Socket read error: ' . $this->getError());
        }

        $buffer = trim($buffer);

        if ($buffer === false) {
            throw new RuntimeException('Socket read error: ' . $this->getError());
        }

        return $buffer;
    }

    private function message(string $message): void
    {
        echo $message . PHP_EOL;
    }

    private function getError(): string
    {
        return socket_strerror(socket_last_error());
    }
}