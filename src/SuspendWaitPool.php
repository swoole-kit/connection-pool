<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-08
 * Time: 10:55
 */

namespace SwoKit\Pool;

use Inhere\Pool\AbstractPool;
use Swoole\Coroutine;

/**
 * Class ResourcePool
 * - wait by coroutine switch. please see @link https://wiki.swoole.com/wiki/page/773.html
 * @package SwoKit\Pool
 */
abstract class SuspendWaitPool extends AbstractPool
{
    /**
     * @var \SplQueue
     * [
     *  CoroutineId0,
     *  CoroutineId1,
     *  CoroutineId2,
     * ... ...
     * ]
     */
    private $waitingQueue;

    protected function init()
    {
        $this->waitingQueue = new \SplQueue();

        parent::init();
    }

    /**
     * 等待并返回可用资源
     * @return bool|mixed
     */
    protected function wait()
    {
        $coId = Coroutine::getuid();

        // 保存等待的协程ID
        $this->waitingQueue->push($coId);

        // 无空闲资源可用， 挂起协程
        Coroutine::suspend($coId);

        // 恢复后， 返回可用资源
        return $this->getFreeQueue()->pop();
    }

    /**
     * {@inheritdoc}
     */
    public function put($resource)
    {
        parent::put($resource);

        // 有等待的协程
        if ($this->hasWaiting()) {
            $coId = $this->waitingQueue->pop();

            // 恢复等待的协程
            Coroutine::resume($coId);
        }
    }

    /**
     * @return int
     */
    public function countWaiting(): int
    {
        return $this->waitingQueue->count();
    }

    /**
     * @return bool
     */
    public function hasWaiting(): bool
    {
        return $this->waitingQueue->count() > 0;
    }
}
