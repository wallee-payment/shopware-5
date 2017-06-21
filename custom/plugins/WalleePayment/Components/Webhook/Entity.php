<?php
namespace WalleePayment\Components\Webhook;

class Entity
{
    private $id;

    private $name;

    private $states;

    private $notifyEveryChange;

    public function __construct($id, $name, array $states, $notifyEveryChange = false)
    {
        $this->id = $id;
        $this->name = $name;
        $this->states = $states;
        $this->notifyEveryChange = $notifyEveryChange;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStates()
    {
        return $this->states;
    }

    public function isNotifyEveryChange()
    {
        return $this->notifyEveryChange;
    }
}
