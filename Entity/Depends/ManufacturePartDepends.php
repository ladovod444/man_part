<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Manufacture\Part\Entity\Depends;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Manufacture\Part\Entity\Event\ManufacturePartEvent;
use BaksDev\Manufacture\Part\Entity\ManufacturePart;
use BaksDev\Manufacture\Part\Type\Id\ManufacturePartUid;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ManufacturePartDepends
 *
 * @see ManufacturePartEvent
 */
#[ORM\Entity]
#[ORM\Table(name: 'manufacture_part_depends')]
class ManufacturePartDepends extends EntityEvent
{
    /** Связь на событие */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: ManufacturePartEvent::class, inversedBy: 'depends', cascade: ['persist'])] // TODO
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private ?ManufacturePartEvent $event;

    public function getEvent(): ManufacturePartEvent
    {
        return $this->event;
    }

    /** Значение свойства */
    #[Assert\NotBlank]
    #[ORM\Column(type: ManufacturePartUid::TYPE)]
    private ManufacturePartUid $depend;

    public function setDepend(ManufacturePartUid $depend): self
    {
        $this->depend = $depend;
        return $this;
    }


    public function __construct(?ManufacturePartEvent $event)
    {
        $this->event = $event;
    }

    public function __toString(): string
    {
        return (string) $this->event;
    }

    public function getDepend(): ManufacturePartUid
    {
        return $this->depend;
    }


    /** @return ManufacturePartDependsInterface */
    public function getDto($dto): mixed
    {
        if(is_string($dto) && class_exists($dto))
        {
            $dto = new $dto();
        }

        if($dto instanceof ManufacturePartDependsInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /** @var ManufacturePartDependsInterface $dto */
    public function setEntity($dto): mixed
    {

//        if($dto instanceof ManufacturePartDependsInterface || $dto instanceof self)
        if($dto instanceof ManufacturePart || $dto instanceof self)
        {
//            dd($dto);
            return parent::setEntity($dto);
        }

        // TODO временно закомментить
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}