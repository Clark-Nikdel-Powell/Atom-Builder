##Atom Builder

The Atom class builds atomic markup from PHP arguments. For example, if you wanted this output:

```html
<h2 class="section-title">A Section Title</h2>
```

You could use this PHP code to render it:

```php
$section_title_args = [
  'tag' => 'h2',
  'content' => 'A Section Title'
];

$section_title = CNP\Atom::Assemble('section-title', $section_title_args);

echo $section_title;
```

Sure, this is slightly longer, but here's what is included in the Atom class:

###Filters

All the filters in the Atom class are namespaced to the name of the atom. An atom named 'section-title' would have an arguments filter named 'section-title_args'.

1. `$atom_name`_args: filter the arguments array.
1. `$atom_name`_classes: filter the classes array.
1. `$atom_name`_id: filter the ID.
1. `$atom_name``$attribute_name`_value: filter a specific attribute's value.
1. `$atom_name`_attributes: filter the completed attributes array.
1. `$atom_name`_markup: filter the compiled markup.

Filtering the markup means that we can dynamically change the atom output on different areas of a site, without needing to change the atom arguments. 

This also allows us to create generic blueprints that we can reuse from site-to-site, either adjusting arguments or filtering the atom where necessary.
