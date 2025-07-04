<?php

namespace BaksDev\Manufacture\Part\Repository\ManufacturePartChoice;

use BaksDev\Manufacture\Part\Entity\Event\ManufacturePartEvent;
use BaksDev\Manufacture\Part\Type\Event\ManufacturePartEventUid;
use BaksDev\Manufacture\Part\Type\Id\ManufacturePartUid;

final readonly class ManufacturePartChoiceResult
{

    public function __construct(
        private string|null $id,
        private string|null $event,
        private ?string $action_name,

        private string|null $number,
//        private int|null $quantity,
        private string|null $complete,
        private string|null $users_profile_username,
//        private string $part_date, // "2025-06-25 11:21:15"
//        private string $part_working_uid
//        private string $part_working
        //    "action_name"  "Производство автомобильных шин"
        private string $actions_event, // "0197a27f-447d-7751-b454-e289cb498bf6"
//        private string $category_id, // "01876af0-ddfc-70c3-ab25-5f85f55a9907"
//        private string $category_name, // => "Triangle"

    ) {}



    public function getId(): ?ManufacturePartUid
    {
        return new ManufacturePartUid($this->id);
    }

    public function getEvent(): ManufacturePartEventUid
    {
        return new ManufacturePartEventUid($this->event);
    }

    public function getActionName(): ?string
    {
        return $this->action_name;
    }


    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getComplete(): ?string
    {
        return $this->complete;
    }

    public function getUsersProfileUsername(): ?string
    {
        return $this->users_profile_username;
    }

    public function getActionsEvent(): string
    {
        return $this->actions_event;
    }

}