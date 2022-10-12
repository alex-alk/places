<?php

namespace App\Controller\Admin;

use App\Entity\Place;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;

class PlaceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Place::class;
    }

    public function configureFields(string $pageName): iterable
{
    return [
        IdField::new('id')->setDisabled(),
        Field::new('name'),
        Field::new('city'),
        SlugField::new('slug')->setTargetFieldName('name')->setUnlockConfirmationMessage(
            'It is highly recommended to use the automatic slugs, but you can customize them'
        )
    ];
}
}