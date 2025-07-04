<?php

namespace BaksDev\Manufacture\Part\Repository\ManufacturePartChoice;
use Generator;

interface ManufacturePartChoiceInterface
{
    /**
     * Метод возвращает коллекцию производтсвенных партий с названием (action_name)
     */
    public function findAll(): Generator|false;
}