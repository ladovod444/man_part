<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Manufacture\Part\Listeners\Event;


use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus\Collection\ManufacturePartStatusCollection;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatusType;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Слушатель инициирует ManufacturePartStatusInterface для определения в типе доктрины.
 */
#[AsEventListener(event: ControllerEvent::class)]
#[AsEventListener(event: ConsoleEvents::COMMAND)]
final class ManufacturePartStatusListener
{
    private ManufacturePartStatusCollection $collection;

    public function __construct(ManufacturePartStatusCollection $collection)
    {
        $this->collection = $collection;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Инициируем Marketplace
        if(in_array(ManufacturePartStatusType::class, get_declared_classes(), true))
        {
            $this->collection->cases();
        }
    }


    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        // Всегда инициируем Manufacture Status в консольной комманде
        $this->collection->cases();
    }

}
