<?php
namespace CNPMB;

/**
 * Module Builder
 *
 * Simply put, this class builds elements in a WordPress-friendly way: the functions include filters to adjust markup
 * output in a granular, flexible way. This will allow us to create recipes for common modules, and adjust either the
 * args before the function processes, or add a filter to adjust the markup while the function is processing.
 *
 * @package  CNP Module Builder
 * @author   Clark Nidkel Powell
 * @link     http://www.clarknikdelpowell.com
 * @version  0.1
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class ModuleBuilder {

	public $name;
	public $args;

	protected $defaults = array(
		'type'       => '',
		'tag'        => '',
		'tag_type'   => '',
		'content'    => '',
		'attributes' => array()
	);

	protected $vars = array();

	protected $attributes = array();
	protected $classes = array();
	protected $id;

	protected $markup;

	public function __construct( $module_name, $module_args ) {

		$this->name = $module_name;
		$this->args = $module_args;

		$this->ConfigureModuleArgs();
		$this->ConfigureModuleAttributes();
		$this->BuildModule();

		return $this->markup;

	}

	protected function ConfigureModuleArgs() {

		$this->vars = wp_parse_args( $this->args, $this->defaults );

		// Usage: add_filter( '$module_name_args', $vars );
		$this->vars = apply_filters( $this->name . '_args', $this->vars );

		return $this;

	}

	protected function ConfigureModuleAttributes() {

		if ( empty( $this->attributes ) ) {
			return false;
		}

		// Get Attributes
		foreach ( $this->attributes as $attribute_name => $raw_attribute_values ) {

			// Reset, just in case.
			$attribute_values = '';

			switch ( $attribute_name ) {

				case 'class':

					$this->attributes['class'] = $this->getClasses( $raw_attribute_values );

					break;

				case 'id':

					$this->attributes['id'] = $this->getID( $raw_attribute_values );

					break;

				default:

					// Determine attribute values. They can be passed in as
					// an array, or a string, whichever is more convenient.
					if ( '' !== $raw_attribute_values ) {

						if ( is_array( $raw_attribute_values ) ) {

							$attribute_values = implode( " ", $raw_attribute_values );

						} elseif ( is_string( $raw_attribute_values ) ) {

							$attribute_values = $raw_attribute_values;

						}

						$this->attributes[] = $attribute_name . '="' . $attribute_values . '"';

					} // Allows us to pass attributes in with no values.
					else {

						$this->attributes[] = $attribute_name;

					}

					break;

			}
		}

		$this->attributes = apply_filters( $this->name . '_attributes', $this->attributes );

		return $this;

	}

	private function getClasses( $raw_classes ) {

		if ( ! is_array( $raw_classes ) || empty( $raw_classes ) ) {
			return false;
		}

		$classes_arr = array();

		if ( is_string( $raw_classes ) && '' !== $raw_classes ) {
			$classes_arr = explode(',', $raw_classes);
		}
		elseif ( is_array( $raw_classes ) ) {
			$classes_arr = $raw_classes;
		}

		foreach ( $classes_arr as $class_index => $class ) {
			$classes_arr[$class_index] = sanitize_html_class( $class );
		}

		$classes_arr = apply_filters( $this->name . '_classes', $classes_arr );

		return array_filter($classes_arr);

	}

	private function getID( $raw_id ) {

		if ( ! is_string( $raw_id ) || '' == $raw_id ) {
			return false;
		}

		$id = '';

		$id_arr = explode( ' ', trim( $raw_id ) );

		// Sanitize the ID
		if ( !empty( $id_arr ) ) {
			$id = sanitize_html_class( $id_arr[0] );
		}

		$id = apply_filters( $this->name . '_id', $id );

		return $id;

	}

	protected function BuildModule() {

		$tag = $this->vars['tag'];

		switch ( $this->vars['tag_type'] ) {

			// Self-closing tags, like <img>, only have attributes.
			case 'self-closing':

				$this->markup = '<' . $tag . ' ' . implode( " ", $this->attributes ) . ' />';

				break;

			// Standard tags return text inside of the tag.
			default:

				$this->markup = '<' . $tag . ' ' . implode( " ", $this->attributes ) . '>' . $this->vars['content'] . '</' . $tag . '>';

				break;
		}

		$this->markup = apply_filters( $this->name . '_markup', $this->markup );

		return $this;

	}
}