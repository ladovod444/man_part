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

namespace BaksDev\Manufacture\Part\UseCase\Admin\Defect\Event;

use BaksDev\Manufacture\Part\Entity\Event\ManufacturePartEventInterface;
use BaksDev\Manufacture\Part\Type\Event\ManufacturePartEventUid;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus\ManufacturePartStatusDefect;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ManufacturePartEvent */
final class ManufacturePartDTO implements ManufacturePartEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private ManufacturePartEventUid $id;

    /**
     * Рабочее состояние производственной партии
     */
    #[Assert\Valid]
    private ManufacturePartWorkingDTO $working;

    /**
     * Коллекция продукции
     */
    #[Assert\Valid]
    private ArrayCollection $product;


    /**
     * Статус производственной партии
     */
    #[Assert\NotBlank]
    private readonly ManufacturePartStatus $status;


    public function __construct()
    {
        $this->product = new ArrayCollection();
        $this->status = new ManufacturePartStatus(ManufacturePartStatusDefect::class);
        $this->working = new ManufacturePartWorkingDTO();
    }


    /**
     * Идентификатор события
     */
    public function getEvent(): ManufacturePartEventUid
    {
        return $this->id;
    }

    /**
     * Рабочее состояние производственной партии
     */

    public function getWorking(): ManufacturePartWorkingDTO
    {
        return $this->working;
    }

    public function resetWorking(): void
    {
        $this->working->setProfile(null);
        $this->working->setWorking(null);
    }

    /**
     * Возвращает продукт, к которому необходимо применить дефект
     */
    public function getCurrentProduct(ManufacturePartProductsDTO $product): ManufacturePartProductsDTO|false
    {
        $filter = $this->product->filter(function(ManufacturePartProductsDTO $element) use ($product) {

            if($product->getModification())
            {
                return $product->getModification()->equals($element->getModification());
            }

            if($product->getVariation())
            {
                return $product->getVariation()->equals($element->getVariation());
            }

            if($product->getOffer())
            {
                return $product->getOffer()->equals($element->getOffer());
            }

            return $product->getProduct()?->equals($element->getProduct());

        });

        return $filter->current();
    }

    /**
     * Коллекция продукции
     */
    public function getProduct(): ArrayCollection
    {
        return $this->product;
    }

    public function addProduct(ManufacturePartProductsDTO $product): void
    {
        $this->product->add($product);

    }

    public function removeProduct(ManufacturePartProductsDTO $product): void
    {
        $this->product->removeElement($product);
    }

    /**
     * Статус производственной партии
     */
    public function getStatus(): ManufacturePartStatus
    {
        return $this->status;
    }

}