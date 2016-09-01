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
    protected $id;
    protected $key;
    protected $sem;
    protected $messageSizeLimit;
    
    protected $queue;
    
    /**
     * MessageQueue constructor.
     *
     * @param string $id
     * @param int    $messageSizeLimit
     */
    public function __construct($id, $messageSizeLimit = 2048)
    {
        $this->id               = $id;
        $this->key              = hexdec(substr(md5(md5($id) . "oasis-msg-queue"), 0, 8));
        $this->sem              = new Semaphore(__CLASS__ . "#" . $id);
        $this->messageSizeLimit = $messageSizeLimit;
    }
    
    public function initialize()
    {
        if (!$this->queue) {
            $this->queue = msg_get_queue($this->key);
            
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
    
    public function send($msg, $type = 1, $blocking = true)
    {
        if ($type <= 0) {
            throw new \InvalidArgumentException("Message type should be a positive integer!");
        }
        
        $this->initialize();
        
        $this->sem->acquire();
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
            $this->sem->release();
        }
        
        return $ret;
    }
    
    public function receive(&$receivedMessage, &$receivedType, $expectedType = 0, $blocking = true)
    {
        $this->initialize();
        
        $this->sem->acquire();
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
                merror(
                    "Error receiving msg, error code = %d, description = %s",
                    $errorCode,
                    posix_strerror($errorCode)
                );
            }
        } finally {
            $this->sem->release();
        }
        
        return $ret;
    }
    
    public function remove()
    {
        $this->initialize();
        mdebug("Removing message queue, key = %s", $this->key);
        
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
