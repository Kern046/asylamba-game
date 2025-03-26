<?php
/**
 * SUJET A MODIF, ALORS ATTENTION A VOUS.
 **/

namespace App\Classes\Container;

class Cookie extends ArrayList
{
	/** @var array * */
	protected $newElements = [];

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	#[\Override]
    public function add($key, $value, $new = false)
	{
		$this->elements[$key] = $value;

		if (true === $new) {
			$this->newElements[$key] = $value;
		}
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	#[\Override]
    public function get($key, $default = null)
	{
		return $this->elements[$key] ?? $default;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	#[\Override]
    public function remove($key)
	{
		if (!isset($this->elements[$key])) {
			return false;
		}
		unset($this->elements[$key]);
	}

	#[\Override]
    public function clear()
	{
		$this->elements = [];
	}

	/**
	 * @return array
	 */
	public function all()
	{
		return $this->elements;
	}

	/**
	 * @return array
	 */
	public function getNewElements()
	{
		return $this->newElements;
	}
}
