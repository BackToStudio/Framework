<?php

namespace Fantassin\Core\WordPress;

use DI\Container as DIContainer;
use DI\ContainerBuilder;

/**
 * @deprecated
 */
class Container {

	/**
	 * @var DIContainer
	 */
	private $container;

	public function __construct() {
		$builder         = new ContainerBuilder();
		$this->container = $builder->build();
		// $this->container->get( GenerateSaltKeys::class );
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

	public function runHooks() {

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
