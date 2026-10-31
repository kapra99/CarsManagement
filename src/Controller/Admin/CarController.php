<?php

namespace PrestaShop\Module\CarsManagement\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\CarsManagement\Entity\Car;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;

class CarController extends PrestaShopAdminController
{
    private const DOMAIN = 'Modules.Carsmanagement.Admin';

    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function indexAction(EntityManagerInterface $entityManager): Response
    {
        $cars = $entityManager->getRepository(Car::class)->findBy([], ['id' => 'DESC']);

        return $this->render('@Modules/carsmanagement/views/templates/admin/car/index.html.twig', [
            'cars' => $cars,
            'layoutTitle' => $this->trans('Cars', [], self::DOMAIN),
        ]);
    }
}
