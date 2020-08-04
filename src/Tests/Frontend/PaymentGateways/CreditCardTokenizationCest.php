<?php

namespace SkyVerge\Lumiere\Tests\Frontend\PaymentGateways;

use SkyVerge\Lumiere\Page\Frontend\Product;
use SkyVerge\Lumiere\Page\Frontend\Checkout;
use SkyVerge\Lumiere\Page\Frontend\PaymentMethods;

abstract class CreditCardTokenizationCest extends CreditCardCest {


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	public function try_successful_transaction_for_shippable_product_saving_the_payment_method( Product $single_product_page, Checkout $checkout_page, PaymentMethods $payment_methods_page ) {

		$this->tester->loginAsAdmin();

		// place an order and save the payment method
		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		$this->place_order_and_tokenize_payment_method( $checkout_page );
		$this->see_order_received();

		// confirm the payment method is visible in the Payment Methods page
		$token = $this->get_tokenized_payment_method_token();

		$this->tester->amOnPage( PaymentMethods::route() );
		$this->tester->waitForElementVisible( PaymentMethods::SELECTOR_PAYMENT_METHODS_TABLE );

		$this->see_tokenize_payment_method( $token, $payment_methods_page );
	}


	/**
	 * Places an order and ticks the Securely Save to Account checkbox.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function place_order_and_tokenize_payment_method( Checkout $checkout_page ) {

		$this->check_tokenize_payment_method_field( $checkout_page );
		$this->place_order( $checkout_page );
	}


	/**
	 * Performs the necessary steps to tick the Securely Save to Account checkbox for the current gateway.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function check_tokenize_payment_method_field( Checkout $checkout_page ) {

		$this->tester->tryToCheckOption( str_replace( '{gateway_id}', $this->get_gateway_id(), Checkout::FIELD_TOKENIZE_PAYMENT_METHOD ) );
	}


	/**
	 * Gets the raw token of a saved payment method.
	 *
	 * @return string
	 */
	protected function get_tokenized_payment_method_token() {

		$token = $this->tester->grabPaymentTokenFromDatabase( [
			// TODO: get the admin username from the configuration and make the test user configurable {WV 2020-07-30}
			'user_id'    => $this->tester->grabUserIdFromDatabase( 'admin' ),
			'gateway_id' => $this->get_gateway_id(),
		] );

		return $token ? $token->get_token() : '';
	}


	/**
	 * Confirms that a payment method row is visible in the Payment Methods table
	 *
	 * @param string $token the payment method token
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	protected function see_tokenize_payment_method( string $token, PaymentMethods $payment_methods_page ) {

		$payment_methods_page->seePaymentMethod( $token );
	}


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	public function try_successful_transaction_for_shippable_product_with_saved_payment_method( Product $single_product_page, Checkout $checkout_page, PaymentMethods $payment_methods_page ) {

		$this->tester->loginAsAdmin();

		// place an order and save the payment method
		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		$this->place_order_and_tokenize_payment_method( $checkout_page );
		$this->see_order_received();

		// place an order using the saved payment method
		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		$this->place_order_using_tokenized_payment_method( $this->get_tokenized_payment_method_token(), $checkout_page );
		$this->see_order_received();
	}


	/**
	 * Places an order using a saved payment method.
	 *
	 * @param string $token payment method token
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function place_order_using_tokenized_payment_method( string $token, Checkout $checkout_page ) {

		$this->tester->tryToSelectOption( $this->get_saved_payment_method_selector( $token ), $token );
		$this->tester->tryToClick( Checkout::BUTTON_PLACE_ORDER );
	}


	/**
	 * Gets the selector for a saved payment method.
	 *
	 * @param string $token payment method token
	 */
	protected function get_saved_payment_method_selector( string $token ) {

		return str_replace( [ '{gateway_id}', '{token}' ], [ $this->get_gateway()->get_id_dasherized(), $token ], Checkout::FIELD_SAVED_PAYMENT_METHOD );
	}


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	public function try_editing_a_saved_payment_method( Product $single_product_page, Checkout $checkout_page, PaymentMethods $payment_methods_page ) {

		$this->tester->loginAsAdmin();

		// place an order and save the payment method
		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		$this->place_order_and_tokenize_payment_method( $checkout_page );
		$this->see_order_received();

		// set a nickname for the payment method
		$token = $this->get_tokenized_payment_method_token();
		$nickname = 'My Saved Card';

		$this->tester->amOnPage( PaymentMethods::route() );

		$payment_methods_page->setPaymentMethodNickname( $token, $nickname );
		$payment_methods_page->seePaymentMethodNickname( $token, $nickname );

		$this->tester->reloadPage();

		$payment_methods_page->seePaymentMethodNickname( $token, $nickname );

		// delete the payment method
		$payment_methods_page->deletePaymentMethod( $token );
		$payment_methods_page->dontSeePaymentMethod( $token );

		$this->tester->reloadPage();

		$payment_methods_page->dontSeePaymentMethod( $token );
	}


}
