<?php

namespace BaksDev\Manufacture\Part\Repository\ManufacturePartChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Manufacture\Part\Entity\Event\ManufacturePartEvent;
use BaksDev\Manufacture\Part\Entity\Invariable\ManufacturePartInvariable;
use BaksDev\Manufacture\Part\Entity\ManufacturePart;
use BaksDev\Manufacture\Part\Entity\Modify\ManufacturePartModify;
use BaksDev\Manufacture\Part\Entity\Working\ManufacturePartWorking;
use BaksDev\Manufacture\Part\Type\Id\ManufacturePartUid;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\UsersTable\Entity\Actions\Event\UsersTableActionsEvent;
use BaksDev\Users\UsersTable\Entity\Actions\Trans\UsersTableActionsTrans;
use BaksDev\Users\UsersTable\Entity\Actions\Working\Trans\UsersTableActionsWorkingTrans;
use BaksDev\Users\UsersTable\Entity\Actions\Working\UsersTableActionsWorking;
use Generator;

class ManufacturePartChoiceRepository implements ManufacturePartChoiceInterface
{

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage
    ) {}

    public function findAll(): Generator|false
    {
        //
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        /** ManufacturePartInvariable */

        $dbal
            ->addSelect('invariable.number')
//            ->addSelect('invariable.quantity')
            ->from(ManufacturePartInvariable::class, 'invariable');


        $dbal
            ->andWhere('invariable.profile = :profile')
            ->setParameter(
                key: 'profile',
                value: $this->UserProfileTokenStorage->getProfile(),
                type: UserProfileUid::TYPE
            );

        /**
         * ManufacturePart
         */

        $dbal
            ->addSelect('part.id')
            ->addSelect('part.event')
            ->join(
                'invariable',
                ManufacturePart::class,
                'part',
                'part.id = invariable.main'
            );

        $dbal
//            ->addSelect('event.status')
            ->addSelect('event.complete')
            ->join(
                'part',
                ManufacturePartEvent::class,
                'event',
                'event.id = part.event'
//                (!$this->search?->getQuery() && $this->filter?->getStatus() ? ' AND event.status = :status' : '')
            )
//            ->setParameter(
//                'status',
//                $this->filter?->getStatus(),
//                ManufacturePartStatus::TYPE
//            )
        ;

        /** Ответственное лицо (Профиль пользователя) */

        $dbal->leftJoin(
            'event',
            UserProfile::class,
            'users_profile',
            'users_profile.id = invariable.profile'
        );

        $dbal
            ->addSelect('users_profile_personal.username AS users_profile_username')
            ->leftJoin(
                'users_profile',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile.event'
            );


        $dbal
//            ->addSelect('part_modify.mod_date AS part_date')
            ->join(
                'part',
                ManufacturePartModify::class,
                'part_modify',
                'part_modify.event = part.event'
            );


//        if(!$this->search?->getQuery() && $this->filter->getDate())
//        {
//            $date = $this->filter->getDate() ?: new DateTimeImmutable();
//
//            $dbal
//                ->andWhere('DATE(part_modify.mod_date) BETWEEN :start AND :end')
//                ->setParameter('start', $date, Types::DATE_IMMUTABLE)
//                ->setParameter('end', $date, Types::DATE_IMMUTABLE);
//
//        }


        $dbal
//            ->addSelect('part_working.working AS part_working_uid')
            ->leftJoin(
                'part',
                ManufacturePartWorking::class,
                'part_working',
                'part_working.event = part.event'
            );


        /**
         * Действие
         */
        $dbal->leftJoin(
            'part_working',
            UsersTableActionsWorking::class,
            'action_working',
            'action_working.id = part_working.working'
        );

        $dbal
//            ->addSelect('action_working_trans.name AS part_working')
            ->leftJoin(
                'action_working',
                UsersTableActionsWorkingTrans::class,
                'action_working_trans',
                'action_working_trans.working = action_working.id AND action_working_trans.local = :local'
            );

        /**
         * Производственный процесс
         */
        $dbal
            ->addSelect('action_trans.name AS action_name')
            ->leftJoin(
                'event',
                UsersTableActionsTrans::class,
                'action_trans',
                'action_trans.event = event.action AND action_trans.local = :local'
            );


        /** Категория производства */

        $dbal
            ->addSelect('actions_event.id AS actions_event')
            ->leftJoin(
                'event',
                UsersTableActionsEvent::class,
                'actions_event',
                'actions_event.id = event.action'
            );

        $dbal
//            ->addSelect('category.id AS category_id')
            ->leftJoin(
                'actions_event',
                CategoryProduct::class,
                'category',
                'category.id = actions_event.category'
            );

        $dbal
//            ->addSelect('trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'trans',
                'trans.event = category.event AND trans.local = :local'
            )
            ->bindLocal();

//
//        if($this->search?->getQuery())
//        {
//            $dbal
//                ->createSearchQueryBuilder($this->search, true)
//                ->addSearchEqualUid('part.id')
//                ->addSearchLike('invariable.number');
//        }

        $dbal->orderBy('part_modify.mod_date', 'DESC');

//        dd($dbal->fetchAllAssociative());

        return $dbal
            ->enableCache('manufacture_part', 86400)
            ->fetchAllHydrate(ManufacturePartChoiceResult::class);
//            ->fetchAllHydrate(ManufacturePart::class);

    }
}