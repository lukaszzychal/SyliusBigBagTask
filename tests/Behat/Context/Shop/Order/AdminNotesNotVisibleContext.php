<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context\Shop\Order;

use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use Sylius\Behat\Page\Shop\Order\ShowPageInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Webmozart\Assert\Assert;

final class AdminNotesNotVisibleContext implements Context
{
    public function __construct(
        private readonly ShowPageInterface $showPage,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @When I am on the order :orderNumber summary page with token
     */
    public function iAmOnTheOrderSummaryPageWithToken(string $orderNumber): void
    {
        $order = $this->findOrderByNumber($orderNumber);
        $this->showPage->open(['tokenValue' => $order->getTokenValue()]);
    }

    /**
     * @When I am on my order :orderNumber page
     */
    public function iAmOnMyOrderPage(string $orderNumber): void
    {
        $order = $this->findOrderByNumber($orderNumber);
        $this->showPage->open(['tokenValue' => $order->getTokenValue()]);
    }

    /**
     * @Then I should not see :text
     */
    public function iShouldNotSee(string $text): void
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $pageText = $page->getText();
        Assert::notContains($pageText, $text);
    }

    /**
     * @Then I should not see :text text
     */
    public function iShouldNotSeeText(string $text): void
    {
        $session = $this->getSession();
        $page = $session->getPage();
        Assert::notContains($page->getText(), $text);
    }

    /**
     * @Then the form should not contain :fieldName field
     */
    public function theFormShouldNotContainField(string $fieldName): void
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $field = $page->findField($fieldName);
        Assert::null($field, sprintf('Field "%s" should not be present in shop forms', $fieldName));
    }

    private function getSession(): Session
    {
        $reflection = new \ReflectionObject($this->showPage);
        $method = $reflection->getMethod('getSession');
        $method->setAccessible(true);

        /** @var Session $session */
        $session = $method->invoke($this->showPage);

        return $session;
    }

    private function findOrderByNumber(string $orderNumber): OrderInterface
    {
        $normalizedNumber = ltrim($orderNumber, '#');
        $order = $this->orderRepository->findOneByNumber($normalizedNumber);
        Assert::notNull($order, sprintf('Order with number "%s" not found', $orderNumber));

        return $order;
    }
}
