<?php

namespace Fantassin\Core\WordPress\Compose;

trait HasTextDomain
{

	/**
	 * @var string
	 */
	protected $textDomain;

	/**
	 * @return string
	 */
	public function getTextDomain(): string
	{
		return $this->textDomain;
	}

    /**
     * @param string $textDomain
     */
	public function setTextDomain(string $textDomain)
	{
		$this->textDomain = $textDomain;

		return $this;
	}

}
