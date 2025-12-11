<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context\Order;

use App\Entity\Order\Order as AppOrder;
use Behat\Behat\Context\Context;
use Sylius\Bundle\CoreBundle\Factory\OrderFactoryInterface;
use Sylius\Component\Core\Factory\CartItemFactory;
use Sylius\Component\Core\Factory\AddressFactoryInterface;
use Sylius\Component\Addressing\Repository\CountryRepositoryInterface;
use Sylius\Component\Addressing\Model\Country;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Webmozart\Assert\Assert;

final class OrderSetupContext implements Context
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly OrderFactoryInterface $orderFactory,
        private readonly CartItemFactory $orderItemFactory,
        private readonly AddressFactoryInterface $addressFactory,
        private readonly CountryRepositoryInterface $countryRepository,
        private readonly OrderItemQuantityModifierInterface $orderItemQuantityModifier,
    ) {
    }

    /**
     * @Given the order :orderNumber has admin notes :note
     */
    public function theOrderHasAdminNotes(string $orderNumber, string $note): void
    {
        $order = $this->findOrderByNumber($orderNumber);
        Assert::isInstanceOf($order, AppOrder::class);
        $order->setAdminNotes($note);
        $this->orderRepository->add($order);
    }

    /**
     * @Given test order :orderNumber exists for :email with product :productCode
     */
    public function testOrderExists(string $orderNumber, string $email, string $productCode): void
    {
        $this->ensureOrderExists($orderNumber, $email, $productCode);
    }

    private function findOrderByNumber(string $orderNumber): AppOrder
    {
        $normalizedNumber = ltrim($orderNumber, '#');
        $order = $this->orderRepository->findOneByNumber($normalizedNumber);
        Assert::notNull($order, sprintf('Order with number "%s" not found', $orderNumber));

        Assert::isInstanceOf($order, AppOrder::class);

        return $order;
    }

    private function ensureOrderExists(string $orderNumber, string $customerEmail, string $productCode): void
    {
        $normalizedNumber = ltrim($orderNumber, '#');
        $existingOrder = $this->orderRepository->findOneByNumber($normalizedNumber);
        if (null !== $existingOrder) {
            return;
        }

        $channel = $this->channelRepository->findOneBy([]);
        Assert::notNull($channel, 'No channel found for order creation.');

        $customer = $this->customerRepository->findOneBy(['email' => $customerEmail]);
        Assert::notNull($customer, sprintf('Customer with email "%s" not found', $customerEmail));

        /** @var ProductInterface|null $product */
        $product = $this->productRepository->findOneBy(['code' => $productCode]);
        Assert::notNull($product, sprintf('Product with code "%s" not found', $productCode));

        /** @var ProductVariantInterface|null $variant */
        $variant = $product->getVariants()->first() ?: null;
        Assert::notNull($variant, sprintf('Product with code "%s" has no variants', $productCode));

        /** @var OrderInterface $order */
        $order = $this->orderFactory->createNew();
        $order->setNumber($normalizedNumber);
        $order->setChannel($channel);
        $order->setLocaleCode($channel->getDefaultLocale()->getCode());
        $order->setCurrencyCode($channel->getBaseCurrency()->getCode());
        $order->setCustomer($customer);
        $order->setState(OrderInterface::STATE_NEW);

        if (null === $order->getTokenValue()) {
            $order->setTokenValue(bin2hex(random_bytes(10)));
        }

        $countryCode = 'US';
        $this->ensureCountryExists($countryCode);

        $billingAddress = $this->createDefaultAddress($countryCode);
        $shippingAddress = clone $billingAddress;
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);

        $orderItem = $this->orderItemFactory->createNew();
        $orderItem->setVariant($variant);

        $channelPricing = $variant->getChannelPricingForChannel($channel);
        Assert::notNull($channelPricing, sprintf('No channel pricing for product code "%s"', $productCode));

        $orderItem->setUnitPrice($channelPricing->getPrice());
        $this->orderItemQuantityModifier->modify($orderItem, 1);

        $order->addItem($orderItem);
        $order->completeCheckout();

        $this->orderRepository->add($order);

        $persisted = $this->orderRepository->findOneByNumber($normalizedNumber);
        Assert::notNull($persisted, sprintf('Order "%s" was not persisted correctly.', $orderNumber));
    }

    private function ensureCountryExists(string $countryCode): void
    {
        $existing = $this->countryRepository->findOneBy(['code' => $countryCode]);
        if (null !== $existing) {
            return;
        }

        /** @var CountryInterface $country */
        $country = new Country();
        $country->setCode($countryCode);
        $country->setEnabled(true);

        $this->countryRepository->add($country);
    }

    private function createDefaultAddress(string $countryCode): AddressInterface
    {
        /** @var AddressInterface $address */
        $address = $this->addressFactory->createNew();
        $address->setFirstName('John');
        $address->setLastName('Doe');
        $address->setStreet('Test Street 1');
        $address->setCity('New York');
        $address->setPostcode('10001');
        $address->setCountryCode($countryCode);
        $address->setPhoneNumber('123456789');

        return $address;
    }
}
