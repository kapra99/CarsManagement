<?php

namespace PrestaShop\Module\CarsManagement\DemoData;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\CarsManagement\Entity\Car;
use PrestaShop\Module\CarsManagement\Entity\CarExpense;

/**
 * Loads 18 made-up cars so the statistics page has something to show.
 * The data is fixed (not random) so the statistics are predictable:
 * - sold cars in most price ranges, one range (40 000 – 50 000) left empty on purpose;
 * - one car sold for exactly 10 000 (the range boundary);
 * - one car sold at a loss (Ford Kuga);
 * - 5 unsold cars (one is a new import without plates), and cars with zero, one or several expenses.
 *
 * Loading is idempotent: a car whose VIN or plate already exists is skipped,
 * so pressing the button twice does not create duplicates.
 */
class DemoDataLoader
{
    private const CARS = [
        // registration number (null = no plates yet), VIN, brand, model, purchase price, sale price (null = not sold), expenses [date, type, amount]
        ['CA1234BH', 'WVWZZZ1KZ8W123456', 'Volkswagen', 'Golf', '8200.00', '9900.00', [
            ['2026-02-10', 'service', '250.00'],
            ['2026-02-12', 'cleaning', '60.00'],
        ]],
        ['CB5678KA', 'W0L0AHL4865123987', 'Opel', 'Astra', '5400.00', '6500.00', [
            ['2026-03-03', 'repair', '480.00'],
            ['2026-03-05', 'tires', '320.00'],
        ]],
        ['PB4321AX', 'JTDKN3DU5A0123456', 'Toyota', 'Corolla', '8900.00', '10000.00', [
            ['2026-01-20', 'service', '180.00'],
            ['2026-01-22', 'documents', '120.00'],
        ]],
        ['B7788PP', 'TMBJJ7NE5J0234567', 'Skoda', 'Octavia', '12500.00', '14500.00', [
            ['2026-04-02', 'repair', '650.00'],
            ['2026-04-03', 'tires', '400.00'],
            ['2026-04-04', 'cleaning', '80.00'],
        ]],
        ['CT1122CA', 'UU1HSDAG5D1234567', 'Dacia', 'Duster', '9000.00', '12400.00', [
            ['2026-05-15', 'tires', '350.00'],
        ]],
        ['CA9012MK', 'WBA8E9C51GK345678', 'BMW', '320d', '16000.00', '18900.00', [
            ['2026-03-18', 'repair', '1200.00'],
            ['2026-03-20', 'service', '300.00'],
        ]],
        ['CO3344AB', 'JM3KFBDM1K0456789', 'Mazda', 'CX-5', '19500.00', '23000.00', [
            ['2026-06-01', 'service', '350.00'],
            ['2026-06-02', 'transport', '250.00'],
        ]],
        ['A4455KH', 'WAUZZZF41KA567890', 'Audi', 'A4', '22000.00', '27500.00', [
            ['2026-05-05', 'repair', '2100.00'],
            ['2026-05-06', 'tires', '780.00'],
            ['2026-05-08', 'service', '420.00'],
        ]],
        ['EB6677TT', 'WF0AXXWPMAK678901', 'Ford', 'Kuga', '24800.00', '26000.00', [
            ['2026-07-11', 'repair', '3400.00'],
            ['2026-07-14', 'cleaning', '90.00'],
        ]],
        ['CA2468TX', 'WDD2050041F789012', 'Mercedes-Benz', 'C 220', '27000.00', '31000.00', [
            ['2026-06-20', 'service', '500.00'],
            ['2026-06-21', 'documents', '150.00'],
        ]],
        ['CA8642BB', '5YJ3E7EB2KF890123', 'Tesla', 'Model 3', '30000.00', '34900.00', [
            ['2026-08-08', 'cleaning', '100.00'],
            ['2026-08-09', 'documents', '200.00'],
        ]],
        ['PB1357CM', 'YV1UZA8VCL1901234', 'Volvo', 'XC60', '48000.00', '53500.00', [
            ['2026-07-01', 'repair', '900.00'],
            ['2026-07-02', 'service', '400.00'],
            ['2026-07-03', 'transport', '300.00'],
        ]],
        ['CA5555XX', 'WBAKS610X0L012345', 'BMW', 'X5', '61000.00', '68000.00', [
            ['2026-08-15', 'repair', '4500.00'],
            ['2026-08-16', 'service', '600.00'],
            ['2026-08-18', 'tires', '1100.00'],
        ]],
        ['CA7531HP', 'VF3LCYHZPJS123456', 'Peugeot', '308', '7800.00', null, [
            ['2026-09-10', 'repair', '900.00'],
            ['2026-09-11', 'cleaning', '70.00'],
        ]],
        ['PB2468EK', 'TMAJ381AAKJ234567', 'Hyundai', 'Tucson', '21000.00', null, [
            ['2026-09-20', 'service', '300.00'],
        ]],
        ['CB9753AM', 'VF15RBF0A56345678', 'Renault', 'Clio', '4900.00', null, []],
        ['B3579CC', 'U5YPU81ADKL456789', 'Kia', 'Sportage', '17500.00', null, [
            ['2026-10-01', 'repair', '1800.00'],
            ['2026-10-02', 'tires', '600.00'],
            ['2026-10-03', 'documents', '150.00'],
        ]],
        // New import, no plates yet
        [null, 'WP1ZZZ95ZKLB56789', 'Porsche', 'Macan', '55000.00', null, [
            ['2026-10-05', 'transport', '800.00'],
            ['2026-10-06', 'service', '700.00'],
            ['2026-10-08', 'repair', '2600.00'],
        ]],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return int how many cars were added
     */
    public function load(): int
    {
        $repository = $this->entityManager->getRepository(Car::class);
        $added = 0;

        foreach (self::CARS as [$plate, $vin, $brand, $model, $purchasePrice, $salePrice, $expenses]) {
            if ($repository->findOneBy(['vin' => $vin]) !== null
                || ($plate !== null && $repository->findOneBy(['registrationNumber' => $plate]) !== null)
            ) {
                continue;
            }

            $car = (new Car())
                ->setRegistrationNumber($plate)
                ->setVin($vin)
                ->setBrand($brand)
                ->setModel($model)
                ->setPurchasePrice($purchasePrice)
                ->setSalePrice($salePrice);
            $this->entityManager->persist($car);

            foreach ($expenses as [$date, $type, $amount]) {
                $expense = (new CarExpense($car))
                    ->setExpenseDate(new DateTime($date))
                    ->setTypeOfExpense($type)
                    ->setAmount($amount);
                $this->entityManager->persist($expense);
            }

            ++$added;
        }

        // One flush = one transaction: either all new demo cars are saved or none.
        $this->entityManager->flush();

        return $added;
    }
}
