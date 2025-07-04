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

namespace BaksDev\Manufacture\Part\Repository\ManufacturePartInvariable;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Manufacture\Part\Entity\Invariable\ManufacturePartInvariable;
use BaksDev\Manufacture\Part\Entity\ManufacturePart;
use BaksDev\Manufacture\Part\Type\Id\ManufacturePartUid;
use InvalidArgumentException;


final class ManufacturePartInvariableRepository implements ManufacturePartInvariableInterface
{
    private ManufacturePartUid|false $part = false;

    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    public function forPart(ManufacturePart|ManufacturePartUid|string $part): self
    {
        if(empty($part))
        {
            $this->part = false;
            return $this;
        }

        if(is_string($part))
        {
            $part = new ManufacturePartUid($part);
        }

        if($part instanceof ManufacturePart)
        {
            $part = $part->getId();
        }

        $this->part = $part;

        return $this;
    }

    /**
     * Метод возвращает активное состояние Invariable
     */
    public function find(): ManufacturePartInvariable|false
    {
        if(false === ($this->part instanceof ManufacturePartUid))
        {
            throw new InvalidArgumentException('Invalid Argument ManufacturePart');
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('invariable')
            ->from(ManufacturePartInvariable::class, 'invariable')
            ->where('invariable.main = :main')
            ->setParameter(
                key: 'main',
                value: $this->part,
                type: ManufacturePartUid::TYPE
            );

        return $orm->getQuery()->getOneOrNullResult() ?: false;
    }
}