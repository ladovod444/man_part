<?php

declare(strict_types=1);

namespace BaksDev\Manufacture\Part\UseCase\Admin\ManufacturePart;

use BaksDev\Manufacture\Part\Type\Event\ManufacturePartEventUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ManufacturePartEntityEvent */
final class ManufacturePartEntityDTO /*implements ManufacturePartEntityEventInterface*/
{

    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private ?ManufacturePartEventUid $id = null;


    #[Assert\Uuid]
    private ?ManufacturePartEventUid $event = null;


    /**
     * Идентификатор события
     */
    public function getEvent(): ?ManufacturePartEventUid
    {
        return $this->id;
    }


    public function getId(): ?ManufacturePartEventUid
    {
        return $this->id;
    }

    public function setId(ManufacturePartEntityEventUid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setEvent(?ManufacturePartEventUid $event): self
    {
        $this->event = $event;
        return $this;
    }


}