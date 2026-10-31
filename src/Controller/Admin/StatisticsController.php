<?php

namespace PrestaShop\Module\CarsManagement\Controller\Admin;

use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;

class StatisticsController extends PrestaShopAdminController
{
    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function indexAction(): Response
    {
        return $this->render('@Modules/carsmanagement/views/templates/admin/statistics/index.html.twig', [
            'layoutTitle' => $this->trans('Statistics', [], 'Modules.Carsmanagement.Admin'),
        ]);
    }
}
