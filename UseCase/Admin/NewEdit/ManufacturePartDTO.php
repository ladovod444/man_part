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

namespace BaksDev\Manufacture\Part\UseCase\Admin\NewEdit;

use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Manufacture\Part\Entity\Depends\ManufacturePartDepends;
use BaksDev\Manufacture\Part\Entity\Event\ManufacturePartEventInterface;
use BaksDev\Manufacture\Part\Entity\ManufacturePart;
use BaksDev\Manufacture\Part\Repository\ManufacturePartChoice\ManufacturePartChoiceResult;
use BaksDev\Manufacture\Part\Type\Event\ManufacturePartEventUid;
use BaksDev\Manufacture\Part\UseCase\Admin\NewEdit\Invariable\ManufacturePartInvariableDTO;
use BaksDev\Manufacture\Part\UseCase\Admin\NewEdit\Products\ManufacturePartProductsDTO;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\UsersTable\Type\Actions\Event\UsersTableActionsEventUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ManufacturePartEvent */
final class ManufacturePartDTO implements ManufacturePartEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private ?ManufacturePartEventUid $id = null;


    /**
     * Идентификатор процесса производства (!! События)
     */
    //#[Assert\NotBlank]
    #[Assert\Uuid]
    private UsersTableActionsEventUid $action;

    /**
     * Профиль ответственного.
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private UserProfileUid $fixed;

    /**
     * Завершающий этап
     */
    private ?DeliveryUid $complete = null;


    #[Assert\Valid]
    private ManufacturePartInvariableDTO $invariable;


    private ArrayCollection $product;


    // Производственные партии от которых зависит текущая ПП
    private ?ArrayCollection $depends = null;


//    private ManufacturePartChoiceResult $depends;
//    private ManufacturePartDepends $depends;

//    public function getDepends(): ManufacturePartChoiceResult
//    public function getDepends(): ManufacturePartDepends
//    {
//        return $this->depends;
//    }
//
////    public function setDepends(ManufacturePartChoiceResult $depends): self
//    public function setDepends(ManufacturePartDepends $depends): self
//    {
//        $this->depends = $depends;
//        return $this;
//    }

    /* Вспомогательные свойства */

    /**
     * Категория производства
     */
    #[Assert\Uuid]
    private ?CategoryProductUid $category = null;
    /**
     * Комментарий
     */
    #[Assert\Length(max: 255)]
    private ?string $comment = null;

    public function __construct()
    {
        $this->invariable = new ManufacturePartInvariableDTO();
        $this->product = new ArrayCollection();

//        $this->depends = new ArrayCollection();
    }

    /**
     * Идентификатор события
     */
    public function getEvent(): ?ManufacturePartEventUid
    {
        return $this->id;
    }


    /**
     * Категория производства
     */

    public function getCategory(): ?CategoryProductUid
    {
        return $this->category;
    }

    public function setCategory(CategoryProductUid|string $category): void
    {
        if(is_string($category))
        {
            $category = new CategoryProductUid($category);
        }

        $this->category = $category;
    }

    /**
     * Complete
     */
    public function getComplete(): ?DeliveryUid
    {
        return $this->complete;
    }

    public function setComplete(?DeliveryUid $complete): self
    {
        if($complete)
        {
            $this->complete = $complete;
        }

        return $this;
    }

    /**
     * Comment
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Action
     */
    public function getAction(): UsersTableActionsEventUid
    {
        return $this->action;
    }

    public function setAction(UsersTableActionsEventUid $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Invariable
     */
    public function getInvariable(): ManufacturePartInvariableDTO
    {
        return $this->invariable;
    }

    /**
     * Fixed
     */
    public function getFixed(): UserProfileUid
    {
        return $this->fixed;
    }

    public function setFixed(UserProfileUid $fixed): self
    {
        $this->fixed = $fixed;
        return $this;
    }

    /**
     * Product
     */
    public function getProduct(): ArrayCollection
    {
        return $this->product;
    }

    public function addProduct(ManufacturePartProductsDTO $product): self
    {
        $this->product->add($product);

        return $this;
    }



    // TODO
    public function getDepends(): ?ArrayCollection
    {
        return $this->depends;
    }
//
    public function addDepends(?ManufacturePart $depends): self
//    public function addDepends(ManufacturePartDepends $depends): self
    {
        dump($depends);
        if (null !== $depends && null !== $this->depends)
        {
            $this->depends->add($depends);
        }

        return $this;
    }


    public function setDepends(ArrayCollection|ManufacturePart|array|null $depends): self
    {

//        if (is_array($depends)) {
//            foreach($depends as $depend) {
//                $this->addDepends($depend);
//            }
//        }

        if ($depends instanceof ManufacturePart) {
            $this->addDepends($depends);
        }

        if ($depends instanceof ArrayCollection) {
            foreach($depends as $depend) {
                $this->addDepends($depend);
            }
//            $this->addDepends($depends);
        }
        if ($depends instanceof ArrayCollection) {
            foreach($depends as $depend) {
                $this->addDepends($depend);
            }
            //            $this->addDepends($depends);
        }
        else
        {
//            dd($depends);
//            $this->depends = $depends;

            $this->depends = new ArrayCollection();
            foreach($depends as $depend) {
                $this->depends->add($depend);
            }
        }
        return $this;
    }



}