<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context\Admin\Order;

use App\Entity\Order\Order as AppOrder;
use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Sylius\Behat\Page\Admin\Order\ShowPageInterface;
use Sylius\Behat\Page\Admin\Order\UpdatePageInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Webmozart\Assert\Assert;

final class AdminNotesContext implements Context
{
    private ?int $currentOrderId = null;

    public function __construct(
        private readonly ShowPageInterface $showPage,
        private readonly UpdatePageInterface $updatePage,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @When I am on the order :orderNumber details page
     */
    public function iAmOnTheOrderDetailsPage(string $orderNumber): void
    {
        $order = $this->findOrderByNumber($orderNumber);
        $this->currentOrderId = (int) $order->getId();
        $this->showPage->open(['id' => $order->getId()]);
    }

    /**
     * @Then I should be on the order :orderNumber details page
     */
    public function iShouldBeOnTheOrderDetailsPage(string $orderNumber): void
    {
        $order = $this->findOrderByNumber($orderNumber);
        Assert::true(
            $this->showPage->isOpen(['id' => $order->getId()]),
            sprintf('Expected to be on order "%s" details page, but was not', $orderNumber),
        );
    }

    /**
     * @Then I should see the admin notes section
     */
    public function iShouldSeeTheAdminNotesSection(): void
    {
        $session = $this->getSessionFromPage($this->showPage);
        $page = $session->getPage();
        Assert::true($page->has('css', '[data-test-admin-notes]'));
    }

    /**
     * @Then the admin notes section should be empty
     */
    public function theAdminNotesSectionShouldBeEmpty(): void
    {
        $session = $this->getSessionFromPage($this->showPage);
        $page = $session->getPage();
        $adminNotesElement = $page->find('css', '[data-test-admin-notes]');
        Assert::notNull($adminNotesElement);
        $text = $adminNotesElement->getText();
        Assert::contains($text, '-');
    }

    /**
     * @When I click :buttonText button in admin notes section
     */
    public function iClickButtonInAdminNotesSection(string $buttonText): void
    {
        Assert::notNull($this->currentOrderId, 'Order id not set, visit the order details page first.');
        $this->updatePage->open(['id' => $this->currentOrderId]);
    }

    /**
     * @When I fill in :field with :value
     */
    public function iFillInWith(string $field, string $value): void
    {
        $session = $this->getSessionFromPage($this->updatePage);
        $page = $session->getPage();
        $fieldElement = $this->findFieldElement($page, $field);
        Assert::notNull($fieldElement);
        $fieldElement->setValue($value);
    }

    /**
     * @When I save the form
     */
    public function iSaveTheForm(): void
    {
        $this->updatePage->saveChanges();
    }

    /**
     * @Then I should see :text in admin notes section
     */
    public function iShouldSeeInAdminNotesSection(string $text): void
    {
        $session = $this->getSessionFromPage($this->showPage);
        $page = $session->getPage();
        $adminNotesElement = $page->find('css', '[data-test-admin-notes]');
        Assert::notNull($adminNotesElement);
        Assert::contains($adminNotesElement->getText(), $text);
    }

    /**
     * @When I clear the :field field
     */
    public function iClearTheField(string $field): void
    {
        $session = $this->getSessionFromPage($this->updatePage);
        $page = $session->getPage();
        $fieldElement = $this->findFieldElement($page, $field);
        Assert::notNull($fieldElement);
        $fieldElement->setValue('');
    }

    /**
     * @When I fill in :field with a string of :length characters
     */
    public function iFillInWithAStringOfCharacters(string $field, int $length): void
    {
        $longString = str_repeat('a', $length);
        $session = $this->getSessionFromPage($this->updatePage);
        $page = $session->getPage();
        $fieldElement = $this->findFieldElement($page, $field);
        Assert::notNull($fieldElement);
        $fieldElement->setValue($longString);
    }

    /**
     * @Then I should see validation error :message
     */
    public function iShouldSeeValidationError(string $message): void
    {
        $session = $this->getSessionFromPage($this->updatePage);
        $page = $session->getPage();
        $errorText = $page->getText();
        Assert::contains($errorText, $message);
    }

    /**
     * @When I set admin notes :note to order :orderNumber
     */
    public function iSetAdminNotesToOrder(string $note, string $orderNumber): void
    {
        $order = $this->findOrderByNumber($orderNumber);
        $order->setAdminNotes($note);
        $this->orderRepository->add($order);
    }

    /**
     * @Then the admin notes should be truncated to :length characters
     */
    public function theAdminNotesShouldBeTruncatedToCharacters(int $length): void
    {
        $session = $this->getSessionFromPage($this->showPage);
        $page = $session->getPage();
        $adminNotesElement = $page->find('css', '[data-test-admin-notes]');
        Assert::notNull($adminNotesElement);
        $text = $adminNotesElement->getText();
        $noteText = preg_replace('/Notes \(Admin\):.*?Edit/', '', $text);
        $noteText = trim($noteText);
        Assert::length($noteText, $length);
    }

    private function getSessionFromPage(object $page): Session
    {
        $reflection = new \ReflectionObject($page);
        $method = $reflection->getMethod('getSession');
        $method->setAccessible(true);

        /** @var Session $session */
        $session = $method->invoke($page);

        return $session;
    }

    private function findFieldElement(DocumentElement $page, string $field): ?NodeElement
    {
        $fieldElement = $page->findField($field);
        if (null !== $fieldElement) {
            return $fieldElement;
        }

        if ($field === 'adminNotes') {
            $fieldElement = $page->find('css', '[name="sylius_admin_order[adminNotes]"]');
            if (null !== $fieldElement) {
                return $fieldElement;
            }
        }

        return $page->find('css', sprintf('[name="%s"]', $field));
    }

    private function findOrderByNumber(string $orderNumber): AppOrder
    {
        $normalizedNumber = ltrim($orderNumber, '#');
        $order = $this->orderRepository->findOneByNumber($normalizedNumber);
        Assert::notNull($order, sprintf('Order with number "%s" not found', $orderNumber));

        Assert::isInstanceOf($order, AppOrder::class);

        return $order;
    }
}
