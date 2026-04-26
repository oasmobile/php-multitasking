<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-31
 * Time: 20:08
 */

namespace Oasis\Mlib\Multitasking;

class MessageQueue
{
    protected readonly string $id;
    protected readonly int $key;
    protected readonly Semaphore $sem;
    protected readonly int $messageSizeLimit;

    protected \SysvMessageQueue|null $queue = null;

    public function __construct(string $id, int $messageSizeLimit = 2048)
    {
        $this->id               = $id;
        $this->key              = hexdec(substr(md5(md5($id) . "oasis-msg-queue"), 0, 8));
        $this->sem              = new Semaphore(__CLASS__ . "#" . $id);
        $this->messageSizeLimit = $messageSizeLimit;
    }

    public function initialize(): void
    {
        if (!$this->queue) {
            $queue = msg_get_queue($this->key);
            if ($queue === false) {
                throw new \RuntimeException('msg_get_queue() failed for key=0x' . dechex($this->key));
            }
            $this->queue = $queue;

            $this->sem->acquire();
            try {
                $stat = msg_stat_queue($this->queue);
                if ($stat['msg_qbytes'] != $this->messageSizeLimit) {
                    msg_set_queue($this->queue, ['msg_qbytes' => $this->messageSizeLimit]);
                }
            } finally {
                $this->sem->release();
            }
        }
    }

    public function send(mixed $msg, int $type = 1, bool $blocking = true): bool
    {
        if ($type <= 0) {
            throw new \InvalidArgumentException("Message type should be a positive integer!");
        }

        $this->initialize();

        if (!$blocking) $this->sem->acquire();
        try {
            if (!($ret = msg_send(
                $this->queue,
                $type,
                $msg,
                true,
                $blocking,
                $errorCode
            ))
            ) {
                merror(
                    "Error sending msg, error code = %d, description = %s",
                    $errorCode,
                    posix_strerror($errorCode)
                );
            }

        } finally {
            if (!$blocking) $this->sem->release();
        }

        return $ret;
    }

    public function receive(mixed &$receivedMessage, mixed &$receivedType, int $expectedType = 0, bool $blocking = true): bool
    {
        $this->initialize();

        if (!$blocking) $this->sem->acquire();
        try {
            $flag = 0;
            if (!$blocking) {
                $flag |= MSG_IPC_NOWAIT;
            }

            if (!($ret = msg_receive(
                $this->queue,
                $expectedType,
                $receivedType,
                $this->messageSizeLimit,
                $receivedMessage,
                true,
                $flag,
                $errorCode
            ))
            ) {
                if (MSG_ENOMSG == $errorCode) {
                    // [review-skip] 空队列是正常场景，静默处理避免日志噪音
                } else {
                    throw new \RuntimeException(
                        sprintf(
                            "Error receiving msg, error code = %d, description = %s",
                            $errorCode,
                            posix_strerror($errorCode)
                        )
                    );
                }
            }
        } finally {
            if (!$blocking) $this->sem->release();
        }

        return $ret;
    }

    public function remove(): void
    {
        $this->initialize();
        mnotice("Removing message queue, key = %s", $this->key);

        $this->sem->acquire();
        try {
            msg_remove_queue($this->queue);
            $this->queue = null;
        } finally {
            $this->sem->release();
            $this->sem->remove();
        }

    }
}
