<?php

namespace PrestaShop\Module\CarsManagement\Controller\Admin;

use PrestaShop\Module\CarsManagement\Statistics\CarStatistics;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;

class StatisticsController extends PrestaShopAdminController
{
    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function indexAction(CarStatistics $statistics): Response
    {
        return $this->render('@Modules/carsmanagement/views/templates/admin/statistics/index.html.twig', [
            'summary' => $statistics->getStockSummary(),
            'priceRanges' => $statistics->getSoldPriceRanges(),
            'topByExpenses' => $statistics->getTopCarsByExpenses(5),
            'margins' => $statistics->getMargins(),
            'averageMargin' => $statistics->getAverageMarginOfSoldCars(),
            'layoutTitle' => $this->trans('Statistics', [], 'Modules.Carsmanagement.Admin'),
        ]);
    }
}
