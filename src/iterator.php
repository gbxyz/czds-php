<?php declare(strict_types=1);

namespace gbxyz\czds;

use \Net_DNS2_RR as RR;

class iterator implements \Iterator {

    private int $position;
    private ?RR $currentRR;

    private function read(): void {
        $line = fgets($this->fh);

        if (!is_string($line) || empty($line)) {
            $this->currentRR = null;

        } else {
            $this->currentRR = RR::fromString($line);

        }
    }

    public function __construct(private $fh) {
        $this->position = 0;
        $this->read();
    }

    public function current(): false|RR {
        return $this->currentRR ?? false;
    }

    public function key(): int {
        return $this->position;
    }

    public function next(): void {
        $this->read();
        $this->position++;
    }

    public function rewind(): void {
        $this->position = 0;
    }

    public function valid(): bool {
        return ($this->currentRR instanceof RR);
    }
}
