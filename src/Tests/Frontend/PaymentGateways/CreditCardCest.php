<?php

namespace SkyVerge\Lumiere\Tests\Frontend\PaymentGateways;

use SkyVerge\Lumiere\Page\Frontend\Product;
use SkyVerge\Lumiere\Page\Frontend\Checkout;
use SkyVerge\Lumiere\Tests\PaymentGatewaysBase;

abstract class CreditCardCest extends PaymentGatewaysBase {


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 */
	public function try_custom_name_is_shown( Product $single_product_page, Checkout $checkout_page ) {

		$this->tester->havePaymentGatewaySettingsInDatabase( $this->get_gateway_id(), [ 'title' => 'My Credit Card' ] );

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->seePaymentMethodTitle( $this->get_gateway_id(), 'My Credit Card' );
	}


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 */
	public function try_successful_transaction_for_shippable_product( Product $single_product_page, Checkout $checkout_page ) {

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		$this->place_order( $checkout_page );
		$this->see_order_received();
	}


	/**
	 * Performs the necessary steps to place a new order from the Checkout page.
	 *
	 * Normally clicking the Place Order button is the only necessary step.
	 * Payment geteways may overwrite this method to perform extra steps, like entering a particular credit card number or test amount.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function place_order( Checkout $checkout_page ) {

		$this->tester->tryToClick( Checkout::BUTTON_PLACE_ORDER );
	}


}
