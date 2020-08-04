<?php

namespace SkyVerge\Lumiere\Tests;

use SkyVerge\Lumiere\Tests\AcceptanceBase;

abstract class PaymentGatewaysBase extends AcceptanceBase {


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


}

