<?php

namespace App\Classes\Library;

class Flashbag
{
	/** @var string * */
	protected $message;
	/** @var string * */
	protected $type;

	public const TYPE_ERROR = 200;
	// alert constantes
	public const TYPE_DEFAULT = 0;

	public const TYPE_STD_INFO = 100;
	public const TYPE_STD_ERROR = 101;
	public const TYPE_SUCCESS = 102;
	public const TYPE_FORM_ERROR = 103;	// error in form filling

	public const TYPE_BUG_INFO = 200;
	public const TYPE_BUG_ERROR = 201;
	public const TYPE_BUG_SUCCESS = 202;

	public const TYPE_GENERATOR_SUCCESS = 300;	// building construction
	public const TYPE_REFINERY_SUCCESS = 301;	// refinery : silo full
	public const TYPE_TECHNOLOGY_SUCCESS = 302;	// new techno
	public const TYPE_DOCK1_SUCCESS = 303;	// ship construction
	public const TYPE_DOCK2_SUCCESS = 304;	// ship construction
	public const TYPE_DOCK3_SUCCESS = 305;	// mothership construction
	public const TYPE_MARKET_SUCCESS = 306;	// new route

	public const TYPE_GAM_NOMORECASH = 307;
	public const TYPE_GAM_RESEARCH = 308;	// reseach found
	public const TYPE_GAM_NOTIF = 309;	// new notif
	public const TYPE_GAM_MESSAGE = 310;	// new message
	public const TYPE_GAM_SPY = 311;	// somebody is attacking you
	public const TYPE_GAM_ATTACK = 312;	// fight
	public const TYPE_GAM_MARKET = 313;	// transaction in the market

	/**
	 * @param string $message
	 * @param string $type
	 */
	public function __construct($message, $type)
	{
		$this->message = $message;
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
}
