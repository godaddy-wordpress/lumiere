<?php

namespace SkyVerge\Lumiere\Tests;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;
use SkyVerge\Lumiere\Page\Frontend\Checkout;
use SkyVerge\Lumiere\Page\Frontend\Product;

abstract class PaymentGatewaysBase extends AcceptanceBase {


	/** @var \WC_Product_Simple a shippable product */
	protected $shippable_product;


	/**
	 * Runs before each test.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
	public function _before( $I ) {

		parent::_before( $I );

		// TODO: consider creating these products as a run-once-per-suite action or using WP-CLI in wp-bootstrap.php {WV 2020-03-29}
		$this->shippable_product = $this->tester->haveSimpleProductInDatabase( [ 'name' => 'Shippable 1' ] );
	}


	/**
	 * Gets the payment gateway instance.
	 *
	 * @return object
	 */
	protected abstract function get_gateway();


	/**
	 * Gets the ID of the payment gateway being tested.
	 *
	 * @return string
	 */
	protected function get_gateway_id() {

		return $this->get_gateway()->get_id();
	}


	/**
	 * Adds a shippable product to the cart and redirects to the Checkout page.
	 *
	 * @param Product $single_product_page Product page object
	 */
	protected function add_shippable_product_to_cart_and_go_to_checkout( Product $single_product_page ) {

		$this->tester->amOnPage( Product::route( $this->shippable_product ) );

		$single_product_page->addSimpleProductToCart( $this->shippable_product );

		$this->tester->amOnPage( Checkout::route() );
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
	 * Waits 30 seconds to see the Order received message.
	 */
	protected function see_order_received() {

		$this->tester->waitForElementVisible( '.woocommerce-order-details', 30 );
		$this->tester->see( 'Order received', '.entry-title' );
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


}

