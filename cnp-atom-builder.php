<?php

/**
 * Atom Builder
 *
 * Simply put, this class builds atomic elements in a WordPress-friendly way: the functions include filters to adjust
 * markup output in a granular, flexible way. This will allow us to create recipes for common modules, and adjust either
 * the args before the function processes, or add a filter to adjust the markup while the function is processing.
 *
 * @package  CNP Atom Builder
 * @author   Clark Nidkel Powell
 * @link     http://www.clarknikdelpowell.com
 * @version  0.1
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class CNP_Atom {

	public static function Assemble( $module_name, $module_args ) {

		$name = $module_name;
		$args = $module_args;

		$vars       = self::ConfigureAtomArgs( $name, $args );
		$attributes = self::ConfigureAtomAttributes( $name, $vars['attributes'] );
		$markup     = self::BuildAtom( $name, $vars, $attributes );

		return $markup;

	}

	protected function ConfigureAtomArgs( $name, $args ) {

		$defaults = array(
			'type'       => '',
			'tag'        => '',
			'tag_type'   => '',
			'content'    => '',
			'attributes' => array()
		);

		$vars = wp_parse_args( $args, $defaults );

		// Usage: add_filter( '$module_name_args', $vars );
		$vars = apply_filters( $name . '_args', $vars );

		return $vars;

	}

	protected function ConfigureAtomAttributes( $name, $raw_attributes ) {

		if ( empty( $raw_attributes ) ) {
			return false;
		}

		// Get Attributes
		foreach ( $raw_attributes as $attribute_name => $raw_attribute_values ) {

			// Reset, just in case.
			$attribute_values = '';

			switch ( $attribute_name ) {

				case 'class':

					$attributes['class'] = $attribute_name . '="' . self::getClasses( $name, $raw_attribute_values ) . '"';

					break;

				case 'id':

					$attributes['id'] = $attribute_name . '="' . self::getID( $name, $raw_attribute_values ) . '"';;

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

						$attributes[ $attribute_name ] = $attribute_name . '="' . $attribute_values . '"';

					} // Allows us to pass attributes in with no values.
					else {

						$attributes[ $attribute_name ] = $attribute_name;

					}

					break;

			}
		}

		$attributes = apply_filters( $name . '_attributes', $attributes );

		return $attributes;

	}

	private function getClasses( $name, $raw_classes ) {

		$classes_arr = array();

		if ( is_string( $raw_classes ) && '' !== $raw_classes ) {
			$classes_arr = explode( ',', $raw_classes );
		} elseif ( is_array( $raw_classes ) ) {
			$classes_arr = $raw_classes;
		}

		foreach ( $classes_arr as $class_index => $class ) {
			$classes_arr[ $class_index ] = sanitize_html_class( $class );
		}

		$classes_arr = apply_filters( $name . '_classes', $classes_arr );

		$classes_arr = array_filter( $classes_arr );

		$classes = implode( " ", $classes_arr );

		return $classes;

	}

	private function getID( $name, $raw_id ) {

		if ( ! is_string( $raw_id ) || '' == $raw_id ) {
			return false;
		}

		$id = '';

		$id_arr = explode( ' ', trim( $raw_id ) );

		// Sanitize the ID
		if ( ! empty( $id_arr ) ) {
			$id = sanitize_html_class( $id_arr[0] );
		}

		$id = apply_filters( $name . '_id', $id );

		return $id;

	}

	protected function BuildAtom( $name, $vars, $attributes ) {

		$tag = $vars['tag'];

		switch ( $vars['tag_type'] ) {

			// Self-closing tags, like <img>, only have attributes.
			case 'self-closing':

				$markup = '<' . $tag . ' ' . implode( " ", $attributes ) . ' />';

				break;

			// Standard tags return text inside of the tag.
			default:

				$markup = '<' . $tag . ' ' . implode( " ", $attributes ) . '>' . $vars['content'] . '</' . $tag . '>';

				break;
		}

		$markup = apply_filters( $name . '_markup', $markup );

		return $markup;

	}
}