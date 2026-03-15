<?php

namespace BackTo\Framework\Compose;

trait TextDomain
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
	public function setTextDomain(string $textDomain): self
	{
		$this->textDomain = $textDomain;

		return $this;
	}

}
