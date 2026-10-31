<?php

namespace PrestaShop\Module\CarsManagement\Controller\Admin;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\CarsManagement\DemoData\DemoDataLoader;
use PrestaShop\Module\CarsManagement\Entity\Car;
use PrestaShop\Module\CarsManagement\Entity\CarExpense;
use PrestaShop\Module\CarsManagement\Form\CarType;
use PrestaShop\Module\CarsManagement\Form\ExpenseType;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CarController extends PrestaShopAdminController
{
    private const DOMAIN = 'Modules.Carsmanagement.Admin';

    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function indexAction(EntityManagerInterface $entityManager): Response
    {
        $cars = $entityManager->getRepository(Car::class)->findBy([], ['id' => 'DESC']);

        return $this->render('@Modules/carsmanagement/views/templates/admin/car/index.html.twig', [
            'cars' => $cars,
            'layoutTitle' => $this->trans('Cars', [], self::DOMAIN),
            'layoutHeaderToolbarBtn' => [
                'add' => [
                    'href' => $this->generateUrl('admin_carsmanagement_cars_create'),
                    'desc' => $this->trans('Add new car', [], self::DOMAIN),
                    'icon' => 'add_circle_outline',
                ],
            ],
        ]);
    }

    #[AdminSecurity("is_granted('create', request.get('_legacy_controller'))", redirectRoute: 'admin_carsmanagement_cars_index')]
    public function createAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $car = new Car();
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->isCarUnique($form, $car, $entityManager)) {
            $entityManager->persist($car);

            if ($this->saveChanges($entityManager)) {
                $this->addFlash('success', $this->trans('The car was added.', [], self::DOMAIN));

                return $this->redirectToRoute('admin_carsmanagement_cars_edit', ['carId' => $car->getId()]);
            }
        }

        return $this->render('@Modules/carsmanagement/views/templates/admin/car/form.html.twig', [
            'carForm' => $form->createView(),
            'car' => null,
            'layoutTitle' => $this->trans('Add new car', [], self::DOMAIN),
        ]);
    }

    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))", redirectRoute: 'admin_carsmanagement_cars_index')]
    public function editAction(int $carId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $car = $entityManager->find(Car::class, $carId);
        if ($car === null) {
            return $this->redirectToCarListWithError();
        }

        // Two forms on one page. Each has its own name ("car" / "expense"),
        // so handleRequest() only picks up the form that was actually submitted.
        $carForm = $this->createForm(CarType::class, $car);
        $carForm->handleRequest($request);

        if ($carForm->isSubmitted() && $carForm->isValid() && $this->isCarUnique($carForm, $car, $entityManager)) {
            if ($this->saveChanges($entityManager)) {
                $this->addFlash('success', $this->trans('The car was updated.', [], self::DOMAIN));

                return $this->redirectToRoute('admin_carsmanagement_cars_edit', ['carId' => $carId]);
            }
        }

        $expense = new CarExpense($car);
        $expenseForm = $this->createForm(ExpenseType::class, $expense);
        $expenseForm->handleRequest($request);

        if ($expenseForm->isSubmitted() && $expenseForm->isValid()) {
            $entityManager->persist($expense);
            $entityManager->flush();
            $this->addFlash('success', $this->trans('The expense was added.', [], self::DOMAIN));

            return $this->redirectToRoute('admin_carsmanagement_cars_edit', ['carId' => $carId]);
        }

        // The database adds up the amounts (exact DECIMAL), PHP floats could lose cents.
        $totalExpenses = $entityManager
            ->createQuery('SELECT COALESCE(SUM(e.amount), 0) FROM ' . CarExpense::class . ' e WHERE e.car = :car')
            ->setParameter('car', $car)
            ->getSingleScalarResult();

        return $this->render('@Modules/carsmanagement/views/templates/admin/car/form.html.twig', [
            'carForm' => $carForm->createView(),
            'expenseForm' => $expenseForm->createView(),
            'car' => $car,
            'expenseTypes' => CarExpense::TYPES,
            'totalExpenses' => $totalExpenses,
            'layoutTitle' => $this->trans('Edit car %car%', ['%car%' => $car->getRegistrationNumber() ?? $car->getVin()], self::DOMAIN),
        ]);
    }

    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))", redirectRoute: 'admin_carsmanagement_cars_index')]
    public function deleteAction(int $carId, EntityManagerInterface $entityManager): RedirectResponse
    {
        $car = $entityManager->find(Car::class, $carId);
        if ($car === null) {
            return $this->redirectToCarListWithError();
        }

        // The expenses are deleted by the database (ON DELETE CASCADE on car_expense.id_car).
        $entityManager->remove($car);
        $entityManager->flush();
        $this->addFlash('success', $this->trans('The car was deleted.', [], self::DOMAIN));

        return $this->redirectToRoute('admin_carsmanagement_cars_index');
    }

    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))", redirectRoute: 'admin_carsmanagement_cars_index')]
    public function deleteExpenseAction(int $expenseId, EntityManagerInterface $entityManager): RedirectResponse
    {
        $expense = $entityManager->find(CarExpense::class, $expenseId);
        if ($expense === null) {
            return $this->redirectToCarListWithError();
        }

        $carId = $expense->getCar()->getId();
        $entityManager->remove($expense);
        $entityManager->flush();
        $this->addFlash('success', $this->trans('The expense was deleted.', [], self::DOMAIN));

        return $this->redirectToRoute('admin_carsmanagement_cars_edit', ['carId' => $carId]);
    }

    #[AdminSecurity("is_granted('create', request.get('_legacy_controller'))", redirectRoute: 'admin_carsmanagement_cars_index')]
    public function loadDemoDataAction(DemoDataLoader $demoDataLoader): RedirectResponse
    {
        $added = $demoDataLoader->load();

        if ($added > 0) {
            $this->addFlash('success', $this->trans('%count% demo cars were added.', ['%count%' => $added], self::DOMAIN));
        } else {
            $this->addFlash('info', $this->trans('The demo cars are already loaded.', [], self::DOMAIN));
        }

        return $this->redirectToRoute('admin_carsmanagement_statistics');
    }

    /**
     * The database has UNIQUE keys, but checking here gives a clear message next to the field
     * instead of an SQL error. The plate is compared after normalization (see Car::setRegistrationNumber).
     */
    private function isCarUnique(FormInterface $form, Car $car, EntityManagerInterface $entityManager): bool
    {
        $repository = $entityManager->getRepository(Car::class);
        $isUnique = true;

        // A new import may have no plates yet (NULL), then there is nothing to check.
        if ($car->hasPlates()) {
            if (!preg_match('/^[A-Z0-9]{2,16}$/', $car->getRegistrationNumber())) {
                $form->get('registrationNumber')->addError(new FormError(
                    $this->trans('The registration number may contain only letters and digits (2 to 16).', [], self::DOMAIN)
                ));
                $isUnique = false;
            }

            $samePlate = $repository->findOneBy(['registrationNumber' => $car->getRegistrationNumber()]);
            if ($samePlate !== null && $samePlate->getId() !== $car->getId()) {
                $form->get('registrationNumber')->addError(new FormError(
                    $this->trans('A car with this registration number already exists.', [], self::DOMAIN)
                ));
                $isUnique = false;
            }
        }

        $sameVin = $repository->findOneBy(['vin' => $car->getVin()]);
        if ($sameVin !== null && $sameVin->getId() !== $car->getId()) {
            $form->get('vin')->addError(new FormError(
                $this->trans('A car with this VIN already exists.', [], self::DOMAIN)
            ));
            $isUnique = false;
        }

        return $isUnique;
    }

    /**
     * Last line of defence: if two people save the same plate at the same moment,
     * the UNIQUE key in the database still stops the duplicate.
     */
    private function saveChanges(EntityManagerInterface $entityManager): bool
    {
        try {
            $entityManager->flush();

            return true;
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('error', $this->trans('A car with this registration number or VIN already exists.', [], self::DOMAIN));

            return false;
        }
    }

    private function redirectToCarListWithError(): RedirectResponse
    {
        $this->addFlash('error', $this->trans('The requested item was not found.', [], self::DOMAIN));

        return $this->redirectToRoute('admin_carsmanagement_cars_index');
    }
}
