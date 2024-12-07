<?php declare(strict_types=1);

namespace Imhotep\Database\Traits;

use Closure;
use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Contracts\Database\DeadlockException;
use Throwable;

trait ConnectionTransactions
{
    protected int $transactions = 0;

    public function transaction(Closure $callback, int $attempts = 1)
    {
        for ($curAttempt = 1; $curAttempt <= $attempts; $curAttempt++) {
            $this->beginTransaction();

            try {
                $callbackResult = $callback($this);
            }
            catch (Throwable $e) {
                $this->handleTransactionException($e, $curAttempt, $attempts);

                continue;
            }

            try {
                if ($this->transactions === 1) {
                    $this->getPdo()->commit();
                }

                $this->transactions = max(0, $this->transactions - 1);

            }
            catch (Throwable $e) {
                $this->handleTransactionCommitException($e, $curAttempt, $attempts);

                continue;
            }

            return $callbackResult;
        }
    }

    protected function handleTransactionException(Throwable $e, int $curAttempt, int $maxAttempts): void
    {
        $isConcurrencyError = $this->causedByConcurrencyError($e);

        if ($isConcurrencyError && $this->transactions > 1) {
            $this->transactions--;

            throw new DeadlockException($e->getMessage(), is_int($e->getCode()) ? $e->getCode() : 0, $e);
        }

        $this->rollBack();

        if ($isConcurrencyError && $curAttempt < $maxAttempts) {
            return;
        }

        throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
    }

    protected function handleTransactionCommitException(Throwable $e, int $curAttempt, int $maxAttempts): void
    {
        $this->transactions = max(0, $this->transactions - 1);

        if ($this->causedByConcurrencyError($e) && $curAttempt < $maxAttempts) {
            return;
        }

        if ($this->causedByLostConnection($e)) {
            $this->transactions = 0;
        }

        throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
    }

    public function transactionLevel(): int
    {
        return $this->transactions;
    }

    public function beginTransaction(): void
    {
        $this->createTransaction();

        $this->transactions++;
    }

    protected function createTransaction(): void
    {
        if ($this->transactions === 0) {
            $this->reconnectIfMissingConnection();

            try {
                $this->getPdo()->beginTransaction();
            }
            catch (Throwable $e) {
                if ($this->causedByLostConnection($e)) {
                    $this->reconnect();

                    $this->getPdo()->beginTransaction();
                }
                else {
                    throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
                }
            }
        }
        elseif ($this->transactions >= 1 && $this->queryGrammar->supportSavepoints()) {
            $this->createSavepoint();
        }
    }

    protected function createSavepoint(): void
    {
        $this->getPdo()->exec(
            $this->queryGrammar->compileSavepoint('point'. ($this->transactions+1))
        );
    }

    public function commit(): void
    {
        if ($this->transactions === 1) {
            $this->getPdo()->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);
    }

    public function rollBack(int $toLevel = null): bool
    {
        $toLevel = $toLevel ?: $this->transactions-1;

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return false;
        }

        try {
            if ($toLevel === 0) {
                $this->getPdo()->rollBack();
            }
            elseif ($this->queryGrammar->supportSavepoints()) {
                $this->getPdo()->exec(
                    $this->queryGrammar->compileSavepointRollBack('point'.($toLevel + 1))
                );
            }
        }
        catch (Throwable $e) {
            if ($this->causedByLostConnection($e)) {
                $this->transactions = 0;
            }

            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        $this->transactions = $toLevel;

        return true;
    }
}