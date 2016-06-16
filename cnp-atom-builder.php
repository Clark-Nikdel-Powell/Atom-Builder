<?php
namespace CNP;

define( 'CNPATOM_DEBUG_PAGE', false );
define( 'CNPATOM_DEBUG_FILE', false );

/**
 * Class: Atom Builder
 *
 * Simply put, this class builds atomic elements in a WordPress-friendly way: the functions include filters to adjust
 * markup output in a granular, flexible way. This will allow us to create recipes for common modules, and adjust either
 * the args before the function processes, or add a filter to adjust the markup while the function is processing.
 *
 * @package  CNP Atom Builder
 * @author   Clark Nidkel Powell
 * @link     http://www.clarknikdelpowell.com
 * @version  0.6
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class Atom {

	/**
	 * Assemble
	 *
	 * Assembles a filterable atom based on the atom name and arguments provided
	 *
	 * @since 0.1
	 *
	 * @param string $atom_name | A hyphenated atom name.
	 * @param array $atom_args {
	 *      Atom arguments
	 *
	 * @type string $tag The atom's tag. Default: 'div'.
	 * @type string $tag_type Optional. Use 'self-closing' for tags like <img /> or <input />. Use 'split' for tags that
	 *                                  have items nested inside them. Use 'false_without_content' (TODO: refactor name)
	 *                                  to set up an atom that returns nothing without content. Default: ''.
	 * @type string $content The content to insert between the atom tags.
	 * @type array $attributes Optional. Any attributes to include on the atom's tag, like 'class' or 'id'.
	 *                              Name-value pairs become the attributes on the tag, e.g.,
	 *                              'class' => ['class1', 'class2'] becomes class="class1 class2"
	 *                              when the atom markup is returned. Default: '';
	 * }
	 * @return string $atom_markup | Atom markup
	 */
	public static function Assemble( $atom_name, $atom_args = array() ) {

		$atom_vars       = self::ConfigureAtomArgs( $atom_name, $atom_args );
		$atom_attributes = self::ConfigureAtomAttributes( $atom_name, $atom_vars['attributes'], $atom_vars );
		$atom_markup     = self::BuildAtom( $atom_name, $atom_vars, $atom_attributes );

		return $atom_markup;

	}

	/**
	 * ConfigureAtomArgs
	 *
	 * Parses supplied args vs. defaults and returns atom vars.
	 *
	 * @since 0.1
	 * @access public
	 *
	 * @param string $atom_name | A hyphenated atom name.
	 * @param array $atom_args | Refer to Assemble for full arguments.
	 *
	 * @see self::Assemble
	 *
	 * @filter $atomname_args | Use this filter to adjust the atom args.
	 *
	 * @return array $atom_vars | Atom Vars
	 */
	public static function ConfigureAtomArgs( $atom_name, $atom_args ) {

		// Set up defaults args
		$atom_defaults = [
			'tag'                   => 'div',
			'tag_type'              => '',
			'content'               => '',
			'attributes'            => array(),
			'attribute_quote_style' => '"',
			'before'                => '',
			'after'                 => ''
		];

		// Parse the args
		$atom_vars = wp_parse_args( $atom_args, $atom_defaults );

		// Usage: add_filter( $atom_name . '_args', $atom_vars );
		$atom_name_args_filter = $atom_name . '_args';
		$atom_vars             = apply_filters( $atom_name_args_filter, $atom_vars );
		self::AddDebugEntry( 'Filter', $atom_name_args_filter );

		// Return vars, args are no longer used.
		return $atom_vars;

	}

	/**
	 * ConfigureAtomAttributes
	 *
	 * Sets up atom attributes, includes
	 *
	 * @since 0.1
	 *
	 * @param string $atom_name | A hyphenated atom name.
	 * @param array $raw_atom_attributes | Name-value pairs of atom attributes.
	 *
	 * @see self::Assemble, CNP\Molecule::Assemble
	 *
	 * @filter $atomname_attributes | Use this filter to adjust the atom attributes.
	 *
	 * @return array $atom_attributes | Atom Attributes
	 */
	public static function ConfigureAtomAttributes( $atom_name, $raw_atom_attributes, $atom_vars ) {

		// Ensure that a class name is set.
		if ( empty( $raw_atom_attributes ) || ! isset( $raw_atom_attributes['class'] ) ) {
			$raw_atom_attributes['class'] = $atom_name;
		}

		// Set up return variable
		$atom_attributes = array();

		// Loop through each attribute supplied. We don't need an isset check for $raw_atom_attributes b/c of line 107.
		foreach ( $raw_atom_attributes as $attribute_name => $raw_attribute_values ) {

			// Reset, just in case.
			$attribute_values = '';

			// Some attributes have special cases.
			switch ( $attribute_name ) {

				// The class attribute is double-checked against a sanitize function.
				case 'class':

					$atom_attributes['class'] = $attribute_name . '="' . self::getClasses( $atom_name, $raw_attribute_values ) . '"';

					break;

				// The ID attribute is also double-checked against a sanitize function.
				case 'id':

					$atom_attributes['id'] = $attribute_name . '="' . self::getID( $atom_name, $raw_attribute_values ) . '"';;

					break;

				// Default behavior: any other attribute.
				default:

					// Determine attribute values. They can be passed in as
					// an array, or a string, whichever is more convenient.
					if ( '' !== $raw_attribute_values ) {

						if ( is_array( $raw_attribute_values ) ) {

							// Example: 'class' => ['class1', 'class2'] becomes class="class1 class2"
							$attribute_values = implode( " ", $raw_attribute_values );

						} elseif ( is_string( $raw_attribute_values ) ) {

							$attribute_values = $raw_attribute_values;

						}

						// Filter the attribute value. Usage: add_filter( $atom_name_$attribute_name_value );
						$atom_name_attribute_value_filter = $atom_name . '_' . $attribute_name . '_value';
						$attribute_values                 = apply_filters( $atom_name_attribute_value_filter, $attribute_values );
						self::AddDebugEntry( 'Filter', $atom_name_attribute_value_filter );


						// Set up the attribute.
						$atom_attributes[ $attribute_name ] = $attribute_name . '=' . $atom_vars['attribute_quote_style'] . esc_attr($attribute_values) . $atom_vars['attribute_quote_style'];

					} else {

						// Set up a blank attribute
						$atom_attributes[ $attribute_name ] = $attribute_name;

					}

					break;

			}
		}

		// Apply atom attributes filter
		$atom_name_attributes_filter = $atom_name . '_attributes';
		$atom_attributes             = apply_filters( $atom_name_attributes_filter, $atom_attributes );
		self::AddDebugEntry( 'Filter', $atom_name_attributes_filter );

		// Return atom attributes
		return $atom_attributes;

	}

	/**
	 * getClasses
	 *
	 * Sanitizes and returns the provided classes as a strong
	 *
	 * @since 0.1
	 * @access private
	 *
	 * @see ConfigureAtomAttributes
	 *
	 * @param string $atom_name | A hyphenated atom name.
	 * @param string|array $raw_classes | The classes to check.
	 *
	 * @filter $atomname_classes | Use this filter to adjust the atom classes array.
	 *
	 * @return string $classes | A space-delimited string of classes.
	 */
	public static function getClasses( $atom_name, $raw_classes ) {

		$classes_arr = array();

		// Configure the raw classes in an array
		if ( is_string( $raw_classes ) && '' !== $raw_classes ) {
			$classes_arr = explode( ',', $raw_classes );
		} elseif ( is_array( $raw_classes ) && ! empty( $raw_classes ) ) {
			$classes_arr = $raw_classes;
		}

		// Sanitize each class
		foreach ( $classes_arr as $class_index => $class ) {
			$classes_arr[ $class_index ] = sanitize_html_class( $class );
		}

		// Apply any filters
		$atom_name_classes_filter = $atom_name . '_classes';
		$classes_arr              = apply_filters( $atom_name_classes_filter, $classes_arr );
		self::AddDebugEntry( 'Filter', $atom_name_classes_filter );

		// Filter out duplicates
		$classes_arr = array_filter( $classes_arr );

		// Convert to space-delimited string
		$classes = implode( " ", $classes_arr );

		return $classes;

	}

	/**
	 * getID
	 *
	 * Sanitizes and returns the provided ID.
	 *
	 * @since 0.1
	 * @access private
	 *
	 * @see ConfigureAtomAttributes
	 *
	 * @param string $atom_name | A hyphenated atom name.
	 * @param string|array $raw_id | The ID to check.
	 *
	 * @filter $atomname_id | Use this filter to adjust the atom ID string.
	 *
	 * @return string $id | A single ID.
	 */
	public static function getID( $atom_name, $raw_id ) {

		/* @EXIT: sanity check */
		if ( ! is_string( $raw_id ) || '' == $raw_id ) {
			return false;
		}

		// Set up return variable
		$id = '';

		// Check to make sure we only have one ID.
		$id_arr = explode( ' ', trim( $raw_id ) );

		// Sanitize the first entry in the ID array.
		if ( ! empty( $id_arr ) ) {
			$id = sanitize_html_class( $id_arr[0] );
		}

		// Apply ID filter
		$atom_name_id_filter = $atom_name . '_id';
		$id                  = apply_filters( $atom_name_id_filter, $id );
		self::AddDebugEntry( 'Filter', $atom_name_id_filter );

		return $id;

	}

	/**
	 * BuildAtom
	 *
	 * Assembles and returns the atom markup.
	 *
	 * @since 0.1
	 * @access protected
	 *
	 * @see self::Assemble
	 *
	 * @param string $atom_name | A hyphenated atom name.
	 * @param array $atom_vars | Used for the atom tag & content.
	 * @param array $atom_attributes | The formatted array of atom attributes.
	 *
	 * @filter $atom_name_markup | Use this filter to adjust the atom markup
	 *
	 * @return string|array $atom_markup
	 */
	public static function BuildAtom( $atom_name, $atom_vars, $atom_attributes ) {

		$tag = $atom_vars['tag'];

		$atom_name_content_filter = $atom_name . '_content';
		$content                  = apply_filters( $atom_name_content_filter, $atom_vars['content'] );
		self::AddDebugEntry( 'Filter', $atom_name_content_filter );

		$open  = '<' . $tag . ' ' . implode( " ", $atom_attributes ) . '>';
		$close = '</' . $tag . '>';

		$before = $atom_vars['before'];
		$after  = $atom_vars['after'];

		// Handling the atom output differs based on the tag type.
		switch ( $atom_vars['tag_type'] ) {

			// Self-closing tags, like <img>, only have attributes.
			case 'self-closing':

				$atom_markup = $before . '<' . $tag . ' ' . implode( " ", $atom_attributes ) . ' />' . $after;

				break;

			// For complex nesting situations
			case 'split':

				$atom_markup['open']  = $open;
				$atom_markup['close'] = $close;

				break;

			case 'false_without_content':

				if ( ! empty( $content ) ) {
					$atom_markup = $before . $open . $content . $close . $after;
				} else {
					$atom_markup = '';
				}

				break;

			case 'content-only':

				$atom_markup = $before . $content . $after;

				break;

			// Standard tags return text inside of the tag.
			default:

				$atom_markup = $before . $open . $content . $close . $after;

				break;
		}

		$atom_name_markup_filter = $atom_name . '_markup';
		$atom_markup             = apply_filters( $atom_name_markup_filter, $atom_markup );
		self::AddDebugEntry( 'Filter', $atom_name_markup_filter );

		return $atom_markup;

	}

	/**
	 * AddDebugEntry
	 */
	public static function AddDebugEntry( $entry_type, $entry_message ) {

		$log_type = '';

		if ( true === CNPATOM_DEBUG_FILE ) {
			$log_type = 'file';
		}
		if ( true === CNPATOM_DEBUG_PAGE ) {
			$log_type = 'page';
		}

		$entry = "CNP Atom | $entry_type: $entry_message \r\n";

		switch ( $log_type ) {

			case 'file':

				error_log( $entry, 3, WP_CONTENT_DIR . '/debug.log' );

				break;

			case 'page':

				echo( $entry . '<br />' );

				break;
		}
	}
}