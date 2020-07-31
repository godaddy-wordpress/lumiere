<?php

namespace SkyVerge\Lumiere\Tests\Frontend\PaymentGateways;

use SkyVerge\Lumiere\Tests\AcceptanceBase;

abstract class PaymentGatewaysBase extends AcceptanceBase {


	/**
	 * Gets the payment gateway instance.
	 *
	 * @return object
	 */
	protected abstract function get_payment_gateway();


	/**
	 * Gets the ID of the payment gateway being tested.
	 *
	 * @return string
	 */
	protected function get_payment_gateway_id() {

		return $this->get_payment_gateway()->get_id();
	}


}

