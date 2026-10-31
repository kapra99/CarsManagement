<?php

namespace PrestaShop\Module\CarsManagement\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class CarExpense
{
    // Fixed list so expenses can be grouped; stored as these keys in type_of_expense.
    public const TYPES = [
        'repair',
        'service',
        'tires',
        'cleaning',
        'documents',
        'transport',
        'other',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id_car_expense", type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="PrestaShop\Module\CarsManagement\Entity\Car", inversedBy="expenses")
     * @ORM\JoinColumn(name="id_car", referencedColumnName="id_car", nullable=false, onDelete="CASCADE")
     */
    private Car $car;

    /**
     * @ORM\Column(name="expense_date", type="date")
     */
    private DateTime $expenseDate;

    /**
     * @ORM\Column(name="type_of_expense", type="string", length=32)
     */
    private string $typeOfExpense = 'other';

    /**
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2)
     */
    private string $amount = '0.00';

    /**
     * @ORM\Column(name="date_add", type="datetime")
     */
    private DateTime $dateAdd;

    /**
     * @ORM\Column(name="date_upd", type="datetime")
     */
    private DateTime $dateUpd;

    public function __construct(Car $car)
    {
        $this->car = $car;
        $this->expenseDate = new DateTime('today');
        $this->dateAdd = new DateTime();
        $this->dateUpd = new DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->dateUpd = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCar(): Car
    {
        return $this->car;
    }

    public function getExpenseDate(): DateTime
    {
        return $this->expenseDate;
    }

    public function setExpenseDate(DateTime $expenseDate): self
    {
        $this->expenseDate = $expenseDate;

        return $this;
    }

    public function getTypeOfExpense(): string
    {
        return $this->typeOfExpense;
    }

    public function setTypeOfExpense(string $typeOfExpense): self
    {
        $this->typeOfExpense = $typeOfExpense;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDateAdd(): DateTime
    {
        return $this->dateAdd;
    }

    public function getDateUpd(): DateTime
    {
        return $this->dateUpd;
    }
}
