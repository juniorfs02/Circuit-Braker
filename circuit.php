<?php

class CircuitBreaker
{
    private $state = 'closed';
    private $failureThreshold = 3;
    private $resetTimeout = 60;
    private $failureCount = 0;
    private $lastFailureTime = null;

    public function execute(callable $operation)
    {
        try {
            if ($this->state === 'open' && $this->isResetTimeoutExpired()) {
                $this->state = 'half-open';
                $this->reset();
            }

            if ($this->state === 'closed' || $this->state === 'half-open') {
                $result = $operation();
                $this->reset();
                return $result;
            }

        } catch (\Exception $e) {
            $this->handleFailure();
            throw $e;
        }
    }

    private function handleFailure()
    {
        $this->failureCount++;

        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = 'open';
            $this->lastFailureTime = time();
        }
    }

    private function reset()
    {
        $this->failureCount = 0;
        $this->lastFailureTime = null;
    }

    private function isResetTimeoutExpired()
    {
        if ($this->lastFailureTime === null) {
            return true;
        }

        $currentTime = time();
        $elapsedTime = $currentTime - $this->lastFailureTime;

        return $elapsedTime >= $this->resetTimeout;
    }
}

// Exemplo de uso
$circuitBreaker = new CircuitBreaker();

try {
    $result = $circuitBreaker->execute(function () {
        $contents = file_get_contents('example.txt');
        return 'Conteúdo do arquivo: ' . $contents;
    });

    echo $result;
} catch (\Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
