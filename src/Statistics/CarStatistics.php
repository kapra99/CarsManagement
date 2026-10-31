<?php

namespace PrestaShop\Module\CarsManagement\Statistics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * All statistics are calculated in SQL, so they stay fast with thousands of cars.
 * Amounts come back from MySQL as strings (DECIMAL), which keeps them exact.
 */
class CarStatistics
{
    public const PRICE_RANGE_STEP = 10000;

    public function __construct(
        private readonly Connection $connection,
        private readonly string $dbPrefix,
    ) {
    }

    /**
     * @return array{sold: int, in_stock: int}
     */
    public function getStockSummary(): array
    {
        // COUNT(column) counts only non-NULL values, so COUNT(sale_price) = sold cars.
        $row = $this->connection->fetchAssociative(
            'SELECT COUNT(sale_price) AS sold, COUNT(*) - COUNT(sale_price) AS in_stock
             FROM ' . $this->table('car')
        );

        return [
            'sold' => (int) $row['sold'],
            'in_stock' => (int) $row['in_stock'],
        ];
    }

    /**
     * Sold cars per sale price range: [0; 10 000), [10 000; 20 000), ...
     * The lower bound is included, so a car sold for exactly 10 000 is in "10 000 – 20 000".
     * Empty ranges between the cheapest and the most expensive car are included with 0.
     *
     * @return list<array{from: int, to: int, cars: int}>
     */
    public function getSoldPriceRanges(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT FLOOR(sale_price / :step) AS price_range, COUNT(*) AS cars
             FROM ' . $this->table('car') . '
             WHERE sale_price IS NOT NULL
             GROUP BY price_range
             ORDER BY price_range',
            ['step' => self::PRICE_RANGE_STEP],
            ['step' => ParameterType::INTEGER]
        );

        if ($rows === []) {
            return [];
        }

        $carsPerRange = [];
        foreach ($rows as $row) {
            $carsPerRange[(int) $row['price_range']] = (int) $row['cars'];
        }

        $ranges = [];
        for ($range = 0; $range <= max(array_keys($carsPerRange)); ++$range) {
            $ranges[] = [
                'from' => $range * self::PRICE_RANGE_STEP,
                'to' => ($range + 1) * self::PRICE_RANGE_STEP,
                'cars' => $carsPerRange[$range] ?? 0,
            ];
        }

        return $ranges;
    }

    /**
     * @return list<array{id_car: int, registration_number: string, brand: string, model: string, total_expenses: string}>
     */
    public function getTopCarsByExpenses(int $limit = 5): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT c.id_car, c.registration_number, c.brand, c.model, SUM(e.amount) AS total_expenses
             FROM ' . $this->table('car') . ' c
             INNER JOIN ' . $this->table('car_expense') . ' e ON e.id_car = c.id_car
             GROUP BY c.id_car, c.registration_number, c.brand, c.model
             ORDER BY total_expenses DESC, c.id_car ASC
             LIMIT :limit',
            ['limit' => $limit],
            ['limit' => ParameterType::INTEGER]
        );
    }

    /**
     * Margin = sale price - purchase price - all expenses. For unsold cars it is NULL
     * (any arithmetic with NULL gives NULL in SQL), because there is no sale yet.
     * Sold cars come first, the most profitable at the top.
     *
     * @return list<array<string, mixed>>
     */
    public function getMargins(): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT c.id_car, c.registration_number, c.brand, c.model, c.purchase_price, c.sale_price,
                    COALESCE(e.total_expenses, 0) AS total_expenses,
                    c.sale_price - c.purchase_price - COALESCE(e.total_expenses, 0) AS margin
             FROM ' . $this->table('car') . ' c
             LEFT JOIN ' . $this->expenseTotalsSubquery() . ' e ON e.id_car = c.id_car
             ORDER BY c.sale_price IS NULL, margin DESC, c.id_car'
        );
    }

    /**
     * NULL when no car is sold yet (AVG of no rows is NULL).
     */
    public function getAverageMarginOfSoldCars(): ?string
    {
        $average = $this->connection->fetchOne(
            'SELECT AVG(c.sale_price - c.purchase_price - COALESCE(e.total_expenses, 0))
             FROM ' . $this->table('car') . ' c
             LEFT JOIN ' . $this->expenseTotalsSubquery() . ' e ON e.id_car = c.id_car
             WHERE c.sale_price IS NOT NULL'
        );

        return $average === null ? null : (string) $average;
    }

    /**
     * Expenses are summed per car BEFORE the join. Joining car_expense directly would
     * repeat each car once per expense, and the average margin would count it several times.
     */
    private function expenseTotalsSubquery(): string
    {
        return '(SELECT id_car, SUM(amount) AS total_expenses
                 FROM ' . $this->table('car_expense') . '
                 GROUP BY id_car)';
    }

    private function table(string $name): string
    {
        return '`' . $this->dbPrefix . $name . '`';
    }
}
