<?php

namespace PrestaShop\Module\CarsManagement\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class Car
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id_car", type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="registration_number", type="string", length=16)
     */
    private string $registrationNumber = '';

    /**
     * @ORM\Column(name="vin", type="string", length=17)
     */
    private string $vin = '';

    /**
     * @ORM\Column(name="brand", type="string", length=64)
     */
    private string $brand = '';

    /**
     * @ORM\Column(name="model", type="string", length=64)
     */
    private string $model = '';

    /**
     * @ORM\Column(name="purchase_price", type="decimal", precision=12, scale=2)
     */
    private string $purchasePrice = '0.00';

    /**
     * @ORM\Column(name="sale_price", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $salePrice = null;

    /**
     * @ORM\Column(name="date_add", type="datetime")
     */
    private DateTime $dateAdd;

    /**
     * @ORM\Column(name="date_upd", type="datetime")
     */
    private DateTime $dateUpd;

    /**
     * @ORM\OneToMany(targetEntity="PrestaShop\Module\CarsManagement\Entity\CarExpense", mappedBy="car")
     * @ORM\OrderBy({"expenseDate" = "DESC"})
     */
    private Collection $expenses;

    public function __construct()
    {
        $this->expenses = new ArrayCollection();
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

    public function isSold(): bool
    {
        return $this->salePrice !== null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): self
    {
        // Bulgarian plates use only Cyrillic letters that look like Latin ones.
        // Convert them so "СА1234ВН" (Cyrillic) and "CA1234BH" (Latin) are the same plate.
        $cyrillicToLatin = [
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M', 'Н' => 'H',
            'О' => 'O', 'Р' => 'P', 'С' => 'C', 'Т' => 'T', 'У' => 'Y', 'Х' => 'X',
        ];

        $normalized = mb_strtoupper($registrationNumber);
        $normalized = strtr($normalized, $cyrillicToLatin);
        $normalized = str_replace([' ', '-'], '', $normalized);

        $this->registrationNumber = $normalized;

        return $this;
    }

    public function getVin(): string
    {
        return $this->vin;
    }

    public function setVin(string $vin): self
    {
        $this->vin = strtoupper(trim($vin));

        return $this;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = trim($brand);

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = trim($model);

        return $this;
    }

    public function getPurchasePrice(): string
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(string $purchasePrice): self
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
    }

    public function getSalePrice(): ?string
    {
        return $this->salePrice;
    }

    public function setSalePrice(?string $salePrice): self
    {
        $this->salePrice = $salePrice;

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

    /**
     * @return Collection<int, CarExpense>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }
}
