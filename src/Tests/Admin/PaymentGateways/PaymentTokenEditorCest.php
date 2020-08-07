<?php

namespace SkyVerge\Lumiere\Tests\Admin\PaymentGateways;

use Codeception\Scenario;
use SkyVerge\Lumiere\Page\Admin\PaymentTokenEditor;
use SkyVerge\Lumiere\Page\Frontend\Checkout;
use SkyVerge\Lumiere\Page\Frontend\Product;
use SkyVerge\Lumiere\Tests\PaymentGatewaysBase;

abstract class PaymentTokenEditorCest extends PaymentGatewaysBase {


	/** @var string the number of new tokens created during the current test */
	protected $new_token_count = 0;


	/**
	 * Runs before each test.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
	public function _before( $I ) {

		parent::_before( $I );

		$this->new_token_count = 0;

		$this->tester->loginAsAdmin();
		$this->tester->amOnPage( PaymentTokenEditor::route( 1 ) );
	}


	/**
	 * Only tries to add a payment method in the token editor if refreshing through the API is not supported.
	 *
	 * @see SV_WC_Payment_Gateway_Admin_Payment_Token_Editor::get_actions()
	 *
	 * @param \Codeception\Scenario $scenario Test scenario
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object
	 * @param Product $single_product_page Single product page object
	 * @param Checkout $checkout_page Checkout page object
	 */
	public function try_adding_a_new_payment_token( Scenario $scenario, PaymentTokenEditor $token_editor, Product $single_product_page, Checkout $checkout_page ) {

		if ( $this->get_gateway()->get_api()->supports_get_tokenized_payment_methods() ) {

			$scenario->skip( 'This gateway does not support this feature' );
			return;
		}

		$this->add_new_payment_token( $token_editor, $single_product_page, $checkout_page );
	}


	/**
	 * Performs the necessary steps to add a new payment token and save changes.
	 *
	 * Returns the raw token string for the new payment token.
	 *
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object
	 * @param Product $single_product_page Single product page object
	 * @param Checkout $checkout_page Checkout page object
	 * @return string
	 */
	protected function add_new_payment_token( PaymentTokenEditor $token_editor, Product $single_product_page, Checkout $checkout_page ) {

		/**
		 * If refreshing tokens through the API is supported, adding a token in the payment token editor is not,
		 * so we need to add it by placing an order.
		 *
		 * @see SV_WC_Payment_Gateway_Admin_Payment_Token_Editor::get_actions()
		 */
		if ( $this->get_gateway()->get_api()->supports_get_tokenized_payment_methods() ) {

			$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

			$checkout_page->fillBillingDetails();

			// place an order and save the payment method
			$this->place_order_and_tokenize_payment_method( $checkout_page );
			$this->see_order_received();

			$token = $this->get_tokenized_payment_method_token();

			$this->tester->amOnPage( PaymentTokenEditor::route( 1 ) );
			$token_editor->scrollToPaymentTokensTable();
			$token_editor->seePaymentToken( $token );

			$this->saved_cards_count++;

		} else {

			$token_editor->scrollToPaymentTokensTable();
			$token_editor->showNewPaymentTokenFields();

			$data  = $this->get_new_payment_token_data();
			$token = $this->fill_new_payment_token_fields( $data, $token_editor );

			$this->save_payment_token_changes( $token_editor );

			$this->see_payment_token( $token, $data, $token_editor );

			$this->new_token_count++;
		}

		return $token;
	}


	/**
	 * Gets data for a new payment token.
	 *
	 * It uses the $new_token_count counter to return different data for each new payment token created in the same test.
	 *
	 * @return array
	 */
	protected function get_new_payment_token_data() {

		$tokens = $this->get_payment_tokens_data();

		return ( count( $tokens ) > $this->new_token_count ) ? current( array_slice( $tokens, $this->new_token_count ) ) : reset( $tokens );
	}


	/**
	 * Gets data used to create new payment tokens.
	 *
	 * Subclasses can overwrite this method to return appropriate data for each gateway.
	 *
	 * @return array
	 */
	protected function get_payment_tokens_data() {

		return [
			'4421912014039990' => [
				'token'     => '4421912014039990',
				'card_type' => 'visa',
				'last_four' => '1234',
				'expiry'    => '12/24'
			],
			'4421912014039991' => [
				'token'     => '4421912014039991',
				'card_type' => 'visa',
				'last_four' => '6789',
				'expiry'    => '12/24'
			],
		];
	}


	/**
	 * Fills the fields used to add a new payment token.
	 *
	 * Returns the raw token string of the new payment token.
	 *
	 * @param array $data payment token data
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object.
	 * @return string
	 */
	protected function fill_new_payment_token_fields( array $data, PaymentTokenEditor $token_editor ) {

		$this->tester->fillField( $token_editor->getNewPaymentTokenFieldSelector( 'id' ), $data['token'] );
		$this->tester->selectOption( $token_editor->getNewPaymentTokenFieldSelector( 'card_type' ), $data['card_type'] );
		$this->tester->fillField( $token_editor->getNewPaymentTokenFieldSelector( 'last_four' ), $data['last_four'] );
		$this->tester->fillField( $token_editor->getNewPaymentTokenFieldSelector( 'expiry' ), $data['expiry'] );

		return $data['token'];
	}


	/**
	 * Saves changes in the Payment Token Editor.
	 *
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object
	 */
	protected function save_payment_token_changes( PaymentTokenEditor $token_editor ) {

		$token_editor->saveChanges();
	}


	/**
	 * Checks that a row in the payment tokens table matches the given payment token and its data.
	 *
	 * @param string $token payment method token
	 * @param array $data payment token data
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object
	 */
	protected function see_payment_token( string $token, array $data, PaymentTokenEditor $token_editor ) {

		$token_editor->seePaymentToken( $token );

		$this->tester->seeInField( $token_editor->getPaymentTokenFieldSelector( $token, 'id' ), $data['token'] );
		$this->tester->seeOptionIsSelected( $token_editor->getPaymentTokenFieldSelector( $token, 'card_type' ), $data['card_type'] );
		$this->tester->seeInField( $token_editor->getPaymentTokenFieldSelector( $token, 'last_four' ), $data['last_four'] );
		$this->tester->seeInField( $token_editor->getPaymentTokenFieldSelector( $token, 'expiry' ), $data['expiry'] );
	}



	/**
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object
	 * @param Product $single_product_page Single product page object
	 * @param Checkout $checkout_page Checkout page object
	 */
	public function try_marking_a_payment_token_as_default( PaymentTokenEditor $token_editor, Product  $single_product_page, Checkout $checkout_page ) {

		$first_token  = $this->add_new_payment_token( $token_editor, $single_product_page, $checkout_page );
		$second_token = $this->add_new_payment_token( $token_editor, $single_product_page, $checkout_page );

		// confirm that the first token is automatically set as default
		$token_editor->scrollToPaymentTokensTable();
		$token_editor->seeDefaultPaymentToken( $this->get_gateway()->get_id(), $first_token );

		$this->select_payment_token_as_default( $second_token, $token_editor );
		$this->save_payment_token_changes( $token_editor );

		// confirm t hat the second token is now the default
		$token_editor->scrollToPaymentTokensTable();
		$token_editor->seeDefaultPaymentToken( $this->get_gateway()->get_id(), $second_token );
	}


	/**
	 * Selects the given payment token as the default payment token.
	 *
	 * @param string $token payment method token
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object
	 */
	protected function select_payment_token_as_default( string $token, PaymentTokenEditor $token_editor ) {

		$token_editor->selectPaymentTokenAsDefault( $this->get_gateway()->get_id(), $token );
	}


	/**
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object
	 * @param Product $single_product_page Single product page object
	 * @param Checkout $checkout_page Checkout page object
	 */
	public function try_removing_a_payment_token( PaymentTokenEditor $token_editor, Product $single_product_page, Checkout $checkout_page ) {

		$token = $this->add_new_payment_token( $token_editor, $single_product_page, $checkout_page );

		$token_editor->scrollToPaymentTokensTable();
		$token_editor->deletePaymentToken( $token );
		$token_editor->dontSeePaymentToken( $token );
	}


}
