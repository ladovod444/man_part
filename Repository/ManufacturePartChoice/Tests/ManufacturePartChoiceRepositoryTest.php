<?php

namespace BaksDev\Manufacture\Part\Repository\ManufacturePartChoice;

use BaksDev\Manufacture\Part\Repository\ManufacturePartChoice\ManufacturePartChoiceInterface;
use BaksDev\Manufacture\Part\Repository\ManufacturePartChoice\ManufacturePartChoiceRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group manufacture-part-test
 */
#[When(env: 'test')]
class ManufacturePartChoiceRepositoryTest extends KernelTestCase
{

    private ManufacturePartChoiceInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(ManufacturePartChoiceInterface::class);
    }


    public function testManufacturePartChoices()
    {
        /** @var ManufacturePartChoiceInterface $repository */
//        $repository = self::getContainer()->get(ManufacturePartChoiceInterface::class);
//        $repository = self::getContainer()->get(ManufacturePartChoiceInterface::class);
//        $repository = self::getContainer()->get(ManufacturePartChoiceRepository::class);

        $result = $this->repository->findAll();

        dd(iterator_to_array($result));
        //        dd($result);

        self::assertTrue(true);
    }
}