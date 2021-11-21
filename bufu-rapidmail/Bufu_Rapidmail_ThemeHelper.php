<?php

require_once 'Bufu_Rapidmail_Form.php';

class Bufu_Rapidmail_ThemeHelper
{
	/**
	 * @var Bufu_Rapidmail_Form
	 */
	private $form;

	/**
	 * Bufu_Rapidmail_ThemeHelper constructor.
	 * @param Bufu_Rapidmail_Form $form
	 */
	public function __construct( Bufu_Rapidmail_Form $form )
	{
		$this->form = $form;
	}

	/**
	 * Render the signup form to OB.
	 * @param $options array
	 * @return void
	 */
	public function echoSignupForm( array $options = [] )
	{
		echo $this->form->getFormHtml( $options );
	}
}