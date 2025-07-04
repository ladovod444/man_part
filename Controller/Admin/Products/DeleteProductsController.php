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

namespace BaksDev\Manufacture\Part\Controller\Admin\Products;


use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Manufacture\Part\Entity\Products\ManufacturePartProduct;
use BaksDev\Manufacture\Part\UseCase\Admin\Products\Delete\ManufacturePartProductDeleteDTO;
use BaksDev\Manufacture\Part\UseCase\Admin\Products\Delete\ManufacturePartProductDeleteForm;
use BaksDev\Manufacture\Part\UseCase\Admin\Products\Delete\ManufacturePartProductDeleteHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MANUFACTURE_PART_DELETE')]
final class DeleteProductsController extends AbstractController
{
    /**
     * Удалить продукцию из производственной партии
     */
    #[Route('/admin/manufacture/part/product/delete/{id}', name: 'admin.products.delete', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity] ManufacturePartProduct $ManufacturePartProduct,
        ManufacturePartProductDeleteHandler $ManufacturePartProductDefectHandler,
    ): Response
    {

        $ManufacturePartDTO = new ManufacturePartProductDeleteDTO($ManufacturePartProduct->getId());

        // Форма
        $form = $this->createForm(ManufacturePartProductDeleteForm::class, $ManufacturePartDTO, [
            'action' => $this->generateUrl('manufacture-part:admin.products.delete', ['id' => $ManufacturePartDTO->getId()]),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('manufacture_part_product_delete'))
        {
            $this->refreshTokenForm($form);

            $handle = $ManufacturePartProductDefectHandler->handle(
                $ManufacturePartDTO,
                $this->isGranted('ROLE_ADMIN') ? null : $this->getCurrentProfileUid()
            );

            $this->addFlash(
                'admin.page.product_delete',
                $handle instanceof ManufacturePartProduct ? 'admin.success.product_delete' : 'admin.danger.product_delete',
                'manufacture-part.admin',
                $handle);

            return $this->redirectToReferer();
        }

        return $this->render(['form' => $form->createView()]);
    }
}
