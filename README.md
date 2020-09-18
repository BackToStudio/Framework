# BZK Core
BZK Core improve WordPress Development Experience

## Installation
Use the package manager [composer](https://getcomposer.org/) to install BZK Core.
```composer require fantassin/core```

## Usage
In `functions.php` file of you WordPress theme you can use Dependency Injection like :

```
use Fantassin\Core\WordPress\Container;
use Fantassin\Core\WordPress\Admin\AddReusableBlockMenu;

$container = new Container();
$container->get( AddReusableBlockMenu::class );
$container->runHooks();
```

## Register custom Post Type and Taxonomy
In `functions.php` file of you WordPress theme you can register custom Post Type like :

```
use Fantassin\Core\WordPress\Container;
use Fantassin\Core\WordPress\PostType\RegisterPostType;

$container = new Container();

/** @var RegisterPostType $post_type */
$post_type = $container->get( RegisterPostType::class );
$post_type->add( 'your_post_type_slug' );

// OR with register_post_type() usual params for more customization
$post_type->add( 'your_another_post_type_slug',
	[
		'labels'              => [
			'name'          => __( 'Your Post Type', 'text-domain' ),
			'singular_name' => __( 'Your Post Type', 'text-domain' ),
		],
		'menu_icon'           => 'dashicons-welcome-learn-more',
		'public'              => true,
		'exclude_from_search' => true,
		'hierarchical'        => true,
		'supports'            => [ 'custom-fields' ],
    ]
);

$container->runHooks();
```

Or register custom Taxonomy with : 

```
use Fantassin\Core\WordPress\Container;
use Fantassin\Core\WordPress\Taxonomy\RegisterTaxonomy;

$container = new Container();

/** @var RegisterTaxonomy $taxonomy */
$taxonomy = $container->get( RegisterTaxonomy::class );
$taxonomy->add( 'your_taxonomy_slug', [ 'your_post_type_slug' ] );

// OR with register_taxonomy() usual params for more customization
$taxonomy->add(
	'your_another_taxonomy_slug',
	[ 'your_post_type_slug', 'your_another_post_type_slug' ],
	[
		'labels' => [
			'name'          => __( 'Your taxonomy', 'text-domain' ),
			'singular_name' => __( 'Your taxonomy', 'text-domain' ),
		]
	]
);

$container->runHooks();
```
