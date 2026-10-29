<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CarsManagement extends Module
{
    public function __construct()
    {
        $this->name = 'carsmanagement';
        $this->tab = 'front_office_features';
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
        $this->description = $this->trans('Description of my module.', [], 'Modules.Carsmanagement.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Carsmanagement.Admin');

        if (!Configuration::get('CARSMANAGEMENT')) {
            $this->warning = $this->trans('No name provided', [], 'Modules.Carsmanagement.Admin');
        }
    }
}
