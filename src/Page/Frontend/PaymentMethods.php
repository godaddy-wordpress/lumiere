<?php

namespace SkyVerge\Lumiere\Page\Frontend;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;

/**
 * Payment Methods page object.
 */
class PaymentMethods {


	/** @var string default URL for the Checkout page */
	const URL = '/my-account/payment-methods/';

	/** @var string selector for the Payment Methods table */
	const SELECTOR_PAYMENT_METHODS_TABLE = '.woocommerce-MyAccount-paymentMethods';

	/** @var string selector for the row for payment method with ID equal to {token_id} */
	const SELECTOR_PAYMENT_METHOD_ROW = "//tr[contains(concat(' ', normalize-space(@class), ' '), ' payment-method ')][descendant::input[@name = 'token-id' and @value = {token_id}]]";


	/** @var WPWebDriver|Actor our tester */
	protected $tester;


	/**
	 * Constructor.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
	public function __construct( \FrontendTester $I ) {

		$this->tester = $I;
	}


	/**
	 * Returns the URL to the Payment Methods page.
	 *
	 * @return string
	 */
	public static function route() {

		return self::URL;
	}


	/**
	 * Gets the selector for payment method row in the Payment Methods table.
	 *
	 * @param int $token_id the payment method ID
	 * @return string
	 */
	public function getPaymentMethodRowSelector( int $token_id ) {

		return str_replace( '{token_id}', $token_id, self::SELECTOR_PAYMENT_METHOD_ROW );
	}


	/**
	 * Builds a selector for an element inside a payment method row.
	 *
	 * @param int $token_id the payment method ID
	 * @param string $selector child element selector
	 * @return string
	 */
	public function getPaymentMethodElementSelector( int $token_id, $selector ) {

		return sprintf( "%s//%s", $this->getPaymentMethodRowSelector( $token_id ), $selector );
	}


	/**
	 * Checks that a payment method row is visible the Payment Methods table
	 *
	 * @param int $token_id the payment method ID
	 */
	public function seePaymentMethod( int $token_id ) {

		$selector = $this->getPaymentMethodRowSelector( $token_id );

		$this->tester->waitForElementVisible( $selector );
		$this->tester->seeElement( $selector );
	}


}

