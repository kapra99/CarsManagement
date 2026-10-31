<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

class CarsManagement extends Module
{
    public function __construct()
    {
        $this->name = 'carsmanagement';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Aleksandar Kapra';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '9.0.0',
            'max' => '9.99.99',
        ];
        $this->bootstrap = true;

        // Back office menu under SELL: "Cars" with the pages "Cars" and "Statistics".
        // PrestaShop creates these tabs on install and removes them on uninstall.
        // Statistics is a child of Cars: PrestaShop then copies "Cars" as the menu root
        // (class name + "_MTR") and keeps the original as its first sub-item.
        $this->tabs = [
            [
                'class_name' => 'AdminCarsManagementCars',
                'parent_class_name' => 'SELL',
                'route_name' => 'admin_carsmanagement_cars_index',
                'name' => 'Cars',
                'icon' => 'directions_car',
                'visible' => true,
                'wording' => 'Cars',
                'wording_domain' => 'Modules.Carsmanagement.Admin',
            ],
            [
                'class_name' => 'AdminCarsManagementStatistics',
                'parent_class_name' => 'AdminCarsManagementCars',
                'route_name' => 'admin_carsmanagement_statistics',
                'name' => 'Statistics',
                'visible' => true,
                'wording' => 'Statistics',
                'wording_domain' => 'Modules.Carsmanagement.Admin',
            ],
        ];

        parent::__construct();

        $this->displayName = $this->trans('Cars Management', [], 'Modules.Carsmanagement.Admin');
        $this->description = $this->trans('Module for car management', [], 'Modules.Carsmanagement.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Carsmanagement.Admin');
    }

    public function install(): bool
    {
        return parent::install() && $this->installDb();
    }

    public function uninstall(): bool
    {
        return $this->uninstallDb() && parent::uninstall();
    }

    private function installDb(): bool
    {
        $queries = [
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'car` (
                `id_car` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `registration_number` VARCHAR(16) NOT NULL,
                `vin` CHAR(17) NOT NULL,
                `brand` VARCHAR(64) NOT NULL,
                `model` VARCHAR(64) NOT NULL,
                `purchase_price` DECIMAL(12,2) NOT NULL,
                `sale_price` DECIMAL(12,2) NULL DEFAULT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_car`),
                UNIQUE KEY `uniq_registration_number` (`registration_number`),
                UNIQUE KEY `uniq_vin` (`vin`),
                CHECK (`purchase_price` >= 0),
                CHECK (`sale_price` IS NULL OR `sale_price` >= 0)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',


            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'car_expense` (
            `id_car_expense` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_car` INT UNSIGNED NOT NULL,
            `expense_date` DATE NOT NULL,
            `type_of_expense` VARCHAR(32) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_car_expense`),
            KEY `idx_id_car` (`id_car`),
            FOREIGN KEY (`id_car`) REFERENCES `' . _DB_PREFIX_ . 'car` (`id_car`) ON DELETE CASCADE
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        ];

        foreach ($queries as $sql) {
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }
        return true;
    }

    private function uninstallDb(): bool
    {
        $queries = [
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'car_expense`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'car`',
        ];

        foreach ($queries as $sql) {
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }
        return true;
    }
}
