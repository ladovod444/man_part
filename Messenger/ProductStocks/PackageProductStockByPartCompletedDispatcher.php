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

namespace BaksDev\Manufacture\Part\Messenger\ProductStocks;


use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Manufacture\Part\Entity\Event\ManufacturePartEvent;
use BaksDev\Manufacture\Part\Messenger\ManufacturePartMessage;
use BaksDev\Manufacture\Part\Repository\ManufacturePartCurrentEvent\ManufacturePartCurrentEventInterface;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus\ManufacturePartStatusCompleted;
use BaksDev\Manufacture\Part\UseCase\Admin\NewEdit\ManufacturePartDTO;
use BaksDev\Manufacture\Part\UseCase\Admin\NewEdit\Products\ManufacturePartProductsDTO;
use BaksDev\Manufacture\Part\UseCase\Admin\NewEdit\Products\Orders\ManufacturePartProductOrderDTO;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\Admin\Access\AccessOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Package\PackageOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Package\Products\PackageOrderProductDTO;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\UseCase\Admin\Package\Orders\ProductStockOrderDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockHandler;
use BaksDev\Products\Stocks\UseCase\Admin\Package\Products\ProductStockDTO;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFboWildberries;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * На добавленные в производственную партию заказы - создает складскую заявку, готовые к упаковке (total === access)
 * @see ManufacturePartProductOrderByPartCompletedDispatcher
 */
#[AsMessageHandler(priority: 40)]
final readonly class PackageProductStockByPartCompletedDispatcher
{
    public function __construct(
        #[Target('manufacturePartLogger')] private LoggerInterface $logger,
        private ManufacturePartCurrentEventInterface $ManufacturePartCurrentEvent,
        private CurrentProductIdentifierInterface $CurrentProductIdentifier,
        private PackageProductStockHandler $PackageProductStockHandler,
        private CurrentOrderEventInterface $CurrentOrderEvent,
        private DeduplicatorInterface $deduplicator,
    ) {}

    /**
     * @see ManufacturePartProductOrderByPartCompletedDispatch
     */
    public function __invoke(ManufacturePartMessage $message): bool
    {
        $DeduplicatorExecuted = $this
            ->deduplicator
            ->namespace('manufacture-part')
            ->deduplication([(string) $message->getId(), self::class]);

        if($DeduplicatorExecuted->isExecuted())
        {
            return true;
        }

        $ManufacturePartEvent = $this->ManufacturePartCurrentEvent
            ->fromPart($message->getId())
            ->find();

        if(false === ($ManufacturePartEvent instanceof ManufacturePartEvent))
        {
            $this->logger->critical(
                'manufacture-part: ManufacturePartEvent не определено',
                [var_export($message, true), self::class.':'.__LINE__]
            );

            return false;
        }

        if(false === $ManufacturePartEvent->equalsManufacturePartStatus(ManufacturePartStatusCompleted::class))
        {
            return true;
        }


        /**
         * Определяем тип производства для заказов
         * доступно только для заказов типа FBS + FBO (DBS перемещаются в ручную)
         */

        $orderType = match (true)
        {
            /* FBS Wb */
            $ManufacturePartEvent->equalsManufacturePartComplete(TypeDeliveryFbsWildberries::class) => TypeDeliveryFbsWildberries::TYPE,
            /* FBO Wb*/
            $ManufacturePartEvent->equalsManufacturePartComplete(TypeDeliveryFboWildberries::class) => TypeDeliveryFboWildberries::TYPE,
            default => false,
        };

        if(false === $orderType)
        {
            return false;
        }

        $ManufacturePartDTO = new ManufacturePartDTO();
        $ManufacturePartEvent->getDto($ManufacturePartDTO);

        /** @var ManufacturePartProductsDTO $ManufacturePartProductsDTO */
        foreach($ManufacturePartDTO->getProduct() as $ManufacturePartProductsDTO)
        {

            /** @var ManufacturePartProductOrderDTO $ManufacturePartProductOrderDTO */
            foreach($ManufacturePartProductsDTO->getOrd() as $ManufacturePartProductOrderDTO)
            {
                $OrderUid = $ManufacturePartProductOrderDTO->getOrd();
                $OrderEvent = $this->CurrentOrderEvent->forOrder($OrderUid)->find();

                if(false === $OrderEvent)
                {
                    continue;
                }

                /** Только заказы в статусе НОВЫЙ */
                if(false === $OrderEvent->isStatusEquals(OrderStatusNew::class))
                {
                    continue;
                }

                $DeduplicatorOrder = $this->deduplicator
                    ->deduplication([$OrderUid, self::class]);

                if($DeduplicatorOrder->isExecuted())
                {
                    continue;
                }

                /**
                 * Проверяем что вся продукция в заказе готова к сборке
                 */

                $AccessOrderDTO = new AccessOrderDTO();
                $OrderEvent->getDto($AccessOrderDTO);

                $isPackage = true;

                foreach($AccessOrderDTO->getProduct() as $AccessOrderProductDTO)
                {
                    $AccessOrderPriceDTO = $AccessOrderProductDTO->getPrice();

                    if(false === $AccessOrderPriceDTO->isAccess())
                    {
                        $isPackage = false;
                        break;
                    }
                }

                /**
                 * Обновляем статус заказа и присваиваем профиль склада упаковки.
                 */

                if(true === $isPackage)
                {

                    $this->logger->info(
                        sprintf('%s: Создаем складскую заявку на упаковку', $OrderEvent->getOrderNumber()),
                        [self::class.':'.__LINE__]
                    );

                    $DeduplicatorOrder->save();

                    /**
                     * Создаем складскую заявку на упаковку для резерва продукции
                     */

                    $PackageProductStockDTO = new PackageProductStockDTO();
                    $OrderEvent->getDto($PackageProductStockDTO);

                    // Присваиваем заявке идентификатор заказа
                    $ProductStockOrderDTO = new ProductStockOrderDTO();
                    $ProductStockOrderDTO->setOrd($OrderEvent->getMain());

                    $PackageProductStockDTO->setProduct(new ArrayCollection());
                    $PackageProductStockDTO->setOrd($ProductStockOrderDTO);


                    $PackageOrderInvariableDTO = $PackageProductStockDTO->getInvariable();
                    $PackageOrderInvariableDTO
                        ->setUsr($ManufacturePartDTO->getInvariable()->getUsr())
                        ->setProfile($ManufacturePartDTO->getInvariable()->getProfile())
                        ->setNumber($OrderEvent->getOrderNumber());


                    /** Получаем PackageOrderDTO для коллекции продукции  */
                    $PackageOrderDTO = new PackageOrderDTO();
                    $OrderEvent->getDto($PackageOrderDTO);

                    /** @var PackageOrderProductDTO $PackageOrderProductDTO */
                    foreach($PackageOrderDTO->getProduct() as $PackageOrderProductDTO)
                    {
                        $ProductStockDTO = new ProductStockDTO();

                        /** Получаем идентификаторы продукции по событию заказа */

                        $CurrentProductIdentifier = $this->CurrentProductIdentifier
                            ->forEvent($PackageOrderProductDTO->getProduct())
                            ->forOffer($PackageOrderProductDTO->getOffer())
                            ->forVariation($PackageOrderProductDTO->getVariation())
                            ->forModification($PackageOrderProductDTO->getModification())
                            ->find();

                        if(false === ($CurrentProductIdentifier instanceof CurrentProductIdentifierResult))
                        {
                            continue;
                        }

                        $ProductStockDTO
                            ->setProduct($CurrentProductIdentifier->getProduct())
                            ->setOffer($CurrentProductIdentifier->getOfferConst())
                            ->setVariation($CurrentProductIdentifier->getVariationConst())
                            ->setModification($CurrentProductIdentifier->getModificationConst())
                            ->setTotal($PackageOrderProductDTO->getPrice()->getTotal());

                        $PackageProductStockDTO->addProduct($ProductStockDTO);
                    }

                    $ProductStock = $this->PackageProductStockHandler->handle($PackageProductStockDTO);

                    if(false === ($ProductStock instanceof ProductStock))
                    {
                        $this->logger->critical(
                            sprintf('manufacture-part: Ошибка %s при обновлении отправке заказа %s на упаковку', $ProductStock, $OrderEvent->getOrderNumber()),
                            [$message, self::class.':'.__LINE__]
                        );

                        return false;
                    }
                }
            }
        }

        /**
         * Приступаем к обновлению заказов
         * @see PackageOrdersByPartCompletedDispatcher
         */

        $DeduplicatorExecuted->save();

        return true;

    }
}
