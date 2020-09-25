<?php
declare(strict_types=1);

(new Server('127.0.0.1', 29000))->run();

/**
 * Class Server
 */
class Server
{
    /** @var resource $socket */
    public $socket = null;
    /** @var resource $current */
    public $current = null;

    private string $ip;

    private int $port;

    private const VARIANTS = ['rock', 'paper', 'scissors'];

    private int $state = 1;

    private const MESSAGES = [
        'win' => 'You win!',
        'loss' => 'You loss!',
        'tie' => 'Tie!',
    ];

    public function __construct(string $ip, int $port)
    {
        $this->ip = $ip;
        $this->port = $port;

        set_time_limit(0);
    }

    public function run(): void
    {
        $this->create()->bind()->listen();

        while (true) {

            $this->current = socket_accept($this->socket);

            if ($this->current === false) {
                throw new RuntimeException('Socket accept error: ' . $this->getError());
            }

            $this->message('Client connected');

            $this->write('Hello bro. Play with me?');

            while (true) {

                try {
                    $buffer = $this->read();
                } catch (RuntimeException $exception) {
                    $this->message($exception->getMessage());
                    socket_close($this->current);
                    continue 2;
                }

                $message = strtolower($buffer);

                switch ($this->current()) {
                    case 1:
                        $this->write('Rock, paper, or scissors?');
                        $this->next();
                        break;
                    case 2 && in_array($message, self::VARIANTS, true):
                        $this->write($this->game($message) . ' Restart or exit?');
                        $this->next();
                        break;
                    case 3 && $message === 'restart':
                        $this->write('Rock, paper, or scissors?');
                        $this->restart();
                        break;
                    case 3 && $message === 'exit':
                        $this->exitGame();
                        break;
                    default:
                        $this->write('Enter correct message');
                }
            }
        }
    }

    private function game(string $player): string
    {
        $machineKey = (int)array_rand(self::VARIANTS);
        $machine = self::VARIANTS[$machineKey];

        $message = 'Machine: ' . $machine . '. ';

        if ($player === $machine) {
            return $message . self::MESSAGES['tie'];
        }

        if (($player === 'rock') && $machine === 'scissors') {
            return $message . self::MESSAGES['win'];
        }

        if (($player === 'paper') && $machine === 'rock') {
            return $message . self::MESSAGES['win'];
        }

        if (($player === 'scissors') && $machine === 'paper') {
            return $message . self::MESSAGES['win'];
        }

        return $message . self::MESSAGES['loss'];
    }

    private function current(): int
    {
        return $this->state;
    }

    private function next(): void
    {
        ++$this->state;
    }

    private function restart(): void
    {
        $this->state = 2;
    }

    private function exitGame(): void
    {
        socket_close($this->current);
        $this->state = 1;
    }

    private function message(string $message): void
    {
        echo $message . PHP_EOL;
    }

    private function create(): Server
    {
        if (!$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            throw new RuntimeException('Socket create error: ' . $this->getError());
        }

        return $this;
    }

    private function bind(): Server
    {
        if (!socket_bind($this->socket, $this->ip, $this->port)) {
            throw new RuntimeException('Socket bind error: ' . $this->getError());
        }

        return $this;
    }

    private function listen(): Server
    {
        if (!socket_listen($this->socket, 5)) {
            throw new RuntimeException('Socket listen error: ' . $this->getError());
        }

        return $this;
    }

    private function write(string $message): int
    {
        $message .= "\n";

        $result = socket_write($this->current, $message, mb_strlen($message));

        if ($result === false) {
            throw new RuntimeException('Socket write error: ' . $this->getError());
        }

        return $result;
    }

    private function read(): string
    {
        $buffer = socket_read($this->current, 2048, PHP_NORMAL_READ);

        if ($buffer === false) {
            throw new RuntimeException('Socket read error: ' . $this->getError());
        }

        $buffer = trim($buffer);

        if ($buffer === false) {
            throw new RuntimeException('Socket read error: ' . $this->getError());
        }

        return $buffer;
    }

    private function getError(): string
    {
        return socket_strerror(socket_last_error());
    }

}