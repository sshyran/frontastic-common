<?php

namespace Frontastic\Common\AccountApiBundle\Domain\AccountApi;

use Frontastic\Common\AccountApiBundle\Domain\AccountApi;
use Frontastic\Common\AccountApiBundle\Domain\Account;
use Frontastic\Common\AccountApiBundle\Domain\Payment;
use Frontastic\Common\AccountApiBundle\Domain\Order;
use Frontastic\Common\AccountApiBundle\Domain\LineItem;
use Frontastic\Common\ProductApiBundle\Domain\Variant;

class LifecycleEventDecorator implements AccountApi
{
    private $aggregate;
    private $listerners = [];

    public function __construct(AccountApi $aggregate, iterable $listerners = [])
    {
        $this->aggregate = $aggregate;

        foreach ($listerners as $listerner) {
            $this->addListener($listerner);
        }
    }

    public function addListener($listener)
    {
        $this->listerners[] = $listener;
    }

    public function login(string $email, string $password): Account
    {
        return $this->dispatch(__FUNCTION__, func_get_args());
    }

    public function getDangerousInnerClient()
    {
        return $this->aggregate->getDangerousInnerClient();
    }

    private function dispatch(string $method, array $arguments)
    {
        $beforeEvent = 'before' . ucfirst($method);
        foreach ($this->listerners as $listener) {
            if (is_callable([$listener, $beforeEvent])) {
                call_user_func_array([$listener, $beforeEvent], array_merge([$this->aggregate], $arguments));
            }
        }

        $result = call_user_func_array([$this->aggregate, $method], $arguments);

        $afterEvent = 'after' . ucfirst($method);
        foreach ($this->listerners as $listener) {
            if (is_callable([$listener, $afterEvent])) {
                $listener->$afterEvent($this->aggregate, $result);
            }
        }

        return $result;
    }
}