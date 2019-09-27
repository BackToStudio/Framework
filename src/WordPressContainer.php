<?php

use DI\Container;
use DI\ContainerBuilder;
use Fantassin\Core\HasAdminHooks;
use Fantassin\Core\HasHooks;

class WordPressContainer {

	/**
	 * @var Container
	 */
	private $container;

	public function __construct() {
		$builder         = new ContainerBuilder();
		$this->container = $builder->build();
	}

	public function get( string $name ) {
		return $this->container->get( $name );
	}

	public function has( string $name ) {
		return $this->container->has( $name );
	}

	public function call( $callable, array $parameters = [] ) {
		return $this->container->call( $callable, $parameters );
	}

	public function set( string $name, $value ) {
		return $this->container->set( $name, $value );
	}

	public function build() {

		foreach ( $this->container->getKnownEntryNames() as $entry ) {
			$action = $this->get( $entry );

			if ( $action instanceof HasHooks ) {
				$action->hooks();
			}

			if ( $action instanceof HasAdminHooks && is_admin() ) {
				$action->hooks();
			}
		}
	}
}
