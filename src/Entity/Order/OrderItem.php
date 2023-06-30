<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\OrderItem as BaseOrderItem;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order_item")
 */
#[ORM\Entity]
#[ORM\Table(name: 'sylius_order_item')]
class OrderItem extends BaseOrderItem
{
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $startReservationDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\GreaterThanOrEqual(propertyPath="startReservationDate", message="La date de fin doit être supérieure ou égale à la date de début.")
     */
    private $endReservationDate;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $daysReservation = [];

    public function getStartReservationDate(): ?\DateTimeInterface
    {
        return $this->startReservationDate;
    }

    public function setStartReservationDate(\DateTimeInterface $startReservationDate): self
    {
        $this->startReservationDate = $startReservationDate;

        return $this;
    }

    public function getEndReservationDate(): ?\DateTimeInterface
    {
        return $this->endReservationDate;
    }

    public function setEndReservationDate(\DateTimeInterface $endReservationDate): self
    {
        $this->endReservationDate = $endReservationDate;

        // Update the daysReservation array whenever the end date is set
        $this->daysReservation = $this->getDaysReservation();

        return $this;
    }

    public function getDaysReservation(): array
    {
        // Si les dates de début ou de fin ne sont pas définies, retourne un tableau vide
        if ($this->startReservationDate === null || $this->endReservationDate === null) {
            return [];
        }

        if ($this->startReservationDate->diff($this->endReservationDate)->days === 0) {
            return [$this->startReservationDate->format('Y-m-d')];
        }

        $interval = new \DateInterval('P1D');

        // Clone the endReservationDate object and add 1 day
        $endReservationDate = clone $this->endReservationDate;
        $endReservationDate->add($interval);

        $period = new \DatePeriod($this->startReservationDate, $interval, $endReservationDate);

        $days = [];
        foreach ($period as $date) {
            $days[] = $date->format('Y-m-d');
        }

        return $days;
    }

    public function setDaysReservation(array $daysReservation): self
    {
        $this->daysReservation = $daysReservation;

        return $this;
    }

    public function recalculateUnitsTotal(): void
    {
        parent::recalculateUnitsTotal();

        $product = $this->getVariant()->getProduct();

        if ($this->startReservationDate === null || $this->endReservationDate === null) {
            return;
        }

        $reservationDays = $this->startReservationDate->diff($this->endReservationDate)->days + 1;

        // Check if "IsPriceList" is checked, if not calculate the usual way
        $isPriceListAttribute = $product->getAttributeByCodeAndLocale('IsPriceList');
        if ($isPriceListAttribute === null || $isPriceListAttribute->getValue() === false) {
            $this->unitPrice *= $reservationDays;
            $this->unitsTotal = $this->unitPrice * $this->quantity;
            $this->total = $this->unitsTotal;
            return;
        }
        // Check if "IsPriceList" is checked, if yes calculate the promotion price
        if ($isPriceListAttribute->getValue() === true) {
            // Apply pricing rules
            $lastAvailablePrice = $this->unitPrice;
            if ($reservationDays >= 1) {
                $price = $product->getAttributeByCodeAndLocale('price_1_day');
                $lastAvailablePrice = $price ? $price->getValue() : $lastAvailablePrice;
            }
            if ($reservationDays >= 2) {
                $price = $product->getAttributeByCodeAndLocale('price_2_days');
                $lastAvailablePrice = $price ? $price->getValue() : $lastAvailablePrice;
            }
            if ($reservationDays >= 3) {
                $price = $product->getAttributeByCodeAndLocale('price_3_days');
                $lastAvailablePrice = $price ? $price->getValue() : $lastAvailablePrice;
            }
            if ($reservationDays >= 4 && $reservationDays <= 6) {
                $price = $product->getAttributeByCodeAndLocale('price_4_to_6_days');
                $lastAvailablePrice = $price ? $price->getValue() : $lastAvailablePrice;
            }
            if ($reservationDays >= 7) {
                $price = $product->getAttributeByCodeAndLocale('price_1_week');
                $lastAvailablePrice = $price ? $price->getValue() : $lastAvailablePrice;
            }
            if ($reservationDays > 7) {
                $price = $product->getAttributeByCodeAndLocale('price_more_1_week');
                $lastAvailablePrice = $price ? $price->getValue() : $lastAvailablePrice;
            }
            $this->unitPrice = $lastAvailablePrice;
        }
        $this->unitPrice *= $reservationDays;
        $this->unitsTotal = $this->unitPrice * $this->quantity;
        $this->total = $this->unitsTotal;
    }
}
