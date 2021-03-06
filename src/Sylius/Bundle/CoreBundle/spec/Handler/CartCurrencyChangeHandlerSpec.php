<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Bundle\CoreBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Bundle\CoreBundle\Handler\CartCurrencyChangeHandler;
use Sylius\Component\Core\Currency\Handler\CurrencyChangeHandlerInterface;
use Sylius\Component\Core\Exception\HandleException;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Updater\OrderUpdaterInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Processor\OrderProcessorInterface;

/**
 * @author Jan Góralski <jan.goralski@lakion.com>
 */
final class CartCurrencyChangeHandlerSpec extends ObjectBehavior
{
    function let(
        CartContextInterface $cartContext,
        OrderUpdaterInterface $orderExchangeRateUpdater,
        OrderProcessorInterface $orderProcessor,
        EntityManagerInterface $orderManager
    ) {
        $this->beConstructedWith(
            $cartContext,
            $orderExchangeRateUpdater,
            $orderProcessor,
            $orderManager
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CartCurrencyChangeHandler::class);
    }

    function it_implements_currency_change_handler_interface()
    {
        $this->shouldImplement(CurrencyChangeHandlerInterface::class);
    }

    function it_throws_handle_exception_when_a_cart_is_not_found(CartContextInterface $cartContext)
    {
        $cartContext->getCart()->willThrow(CartNotFoundException::class);

        $this->shouldThrow(HandleException::class)->during('handle', [Argument::any()]);
    }

    function it_handles_cart_currency_code_change(
        CartContextInterface $cartContext,
        OrderUpdaterInterface $orderExchangeRateUpdater,
        OrderProcessorInterface $orderProcessor,
        EntityManagerInterface $orderManager,
        OrderInterface $cart
    ) {
        $cartContext->getCart()->willReturn($cart);
        $cart->setCurrencyCode('USD')->shouldBeCalled();

        $orderExchangeRateUpdater->update($cart)->shouldBeCalled();

        $orderProcessor->process($cart)->shouldBeCalled();

        $orderManager->persist($cart)->shouldBeCalled();
        $orderManager->flush()->shouldBeCalled();

        $this->handle('USD');
    }
}
