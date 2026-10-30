<?php
if (!defined('_PS_VERSION_')) {
    exit;
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
            'min' => '8.0.0',
            'max' => '9.99.99',
        ];
        $this->bootstrap = true;

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
