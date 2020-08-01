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
		$token_id = $this->get_tokenized_payment_method_id();

		$this->tester->amOnPage( PaymentMethods::route() );
		$this->tester->waitForElementVisible( PaymentMethods::SELECTOR_PAYMENT_METHODS_TABLE );

		$this->see_tokenize_payment_method( $token_id, $payment_methods_page );
	}


	/**
	 * Places an order and thicks the Securely Save to Account checkbox.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function place_order_and_tokenize_payment_method( Checkout $checkout_page ) {

		$this->check_tokenize_payment_method_field( $checkout_page );
		$this->place_order( $checkout_page );
	}


	/**
	 * Performs the necessary steps to thicks the Securely Save to Account checkbox for the current gateway.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function check_tokenize_payment_method_field( Checkout $checkout_page ) {

		$this->tester->tryToCheckOption( str_replace( '{gateway_id}', $this->get_gateway_id(), Checkout::FIELD_TOKENIZE_PAYMENT_METHOD ) );
	}


	/**
	 * Gets the ID of a saved payment method.
	 *
	 * @return int
	 */
	protected function get_tokenized_payment_method_id() {

		// TODO: get the admin username from the configuration and make the test user configurable {WV 2020-07-30}
		$user_id = $this->tester->grabUserIdFromDatabase( 'admin' );

		return $this->tester->grabPaymentTokenIdFromDatabase( [ 'user_id' => $user_id, 'gateway_id' => $this->get_gateway_id() ] );
	}


	/**
	 * Confirms that a payment method row is visible the Payment Methods table
	 *
	 * @param int $token_id the payment method ID
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	protected function see_tokenize_payment_method( int $token_id, PaymentMethods $payment_methods_page ) {

		$payment_methods_page->seePaymentMethod( $token_id );
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

		$this->place_order_using_tokenized_payment_method( $this->get_tokenized_payment_method_id(), $checkout_page );
		$this->see_order_received();
	}


	/**
	 * Places an order using a saved payment method.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function place_order_using_tokenized_payment_method( int $token_id, Checkout $checkout_page ) {

		$this->tester->tryToSelectOption( $this->get_saved_payment_method_selector( $token_id ), $token_id );
		$this->tester->tryToClick( Checkout::BUTTON_PLACE_ORDER );
	}


	/**
	 * Gets the selector for a saved payment method.
	 *
	 * @param int $token_id payment token ID
	 */
	protected function get_saved_payment_method_selector( int $token_id ) {

		return str_replace( [ '{gateway_id}', '{token_id}' ], [ $this->get_gateway()->get_id_dasherized(), $token_id ], Checkout::FIELD_SAVED_PAYMENT_METHOD );
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
		$token_id = $this->get_tokenized_payment_method_id();
		$nickname = 'My Saved Card';

		$this->tester->amOnPage( PaymentMethods::route() );

		$payment_methods_page->setPaymentMethodNickname( $token_id, $nickname );
		$payment_methods_page->seePaymentMethodNickname( $token_id, $nickname );

		$this->tester->reloadPage();

		$payment_methods_page->seePaymentMethodNickname( $token_id, $nickname );

		// delete the payment method
		$payment_methods_page->deletePaymentMethod( $token_id );
		$payment_methods_page->dontSeePaymentMethod( $token_id );

		$this->tester->reloadPage();

		$payment_methods_page->dontSeePaymentMethod( $token_id );
	}


}

